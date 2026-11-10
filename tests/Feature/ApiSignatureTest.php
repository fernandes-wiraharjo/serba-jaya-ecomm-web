<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_without_client_id_returns_401(): void
    {
        $this->getJson(route('api.server-time'), [
            'X-Nonce' => (string) now()->getTimestamp(),
            'X-Api-Signature' => hash('sha256', 'unused'),
        ])->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'The X-Client-ID header is required.',
                'data' => null,
                'errors' => [
                    'api_signature' => ['The X-Client-ID header is required.'],
                ],
            ]);
    }

    public function test_request_without_nonce_returns_401(): void
    {
        $client = ApiClient::factory()->create();

        $this->getJson(route('api.server-time'), [
            'X-Client-ID' => $client->client_id,
            'X-Api-Signature' => hash('sha256', 'unused'),
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'The X-Nonce header is required.');
    }

    public function test_request_without_signature_returns_401(): void
    {
        $client = ApiClient::factory()->create();

        $this->getJson(route('api.server-time'), [
            'X-Client-ID' => $client->client_id,
            'X-Nonce' => (string) now()->getTimestamp(),
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'The X-Api-Signature header is required.');
    }

    public function test_string_nonce_is_accepted(): void
    {
        $client = ApiClient::factory()->create();
        $nonce = 'k3p9Qx';

        $this->getJson(route('api.server-time'), [
            'X-Client-ID' => $client->client_id,
            'X-Nonce' => $nonce,
            'X-Api-Signature' => hash('sha256', $nonce.$client->secret),
        ])->assertOk();
    }

    public function test_unknown_client_returns_401(): void
    {
        $nonce = (string) now()->getTimestamp();

        $this->getJson(route('api.server-time'), [
            'X-Client-ID' => 'client_0000000000000000',
            'X-Nonce' => $nonce,
            'X-Api-Signature' => hash('sha256', $nonce.'sk_dev_missing'),
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'The API signature is invalid.');
    }

    public function test_wrong_signature_returns_401(): void
    {
        $client = ApiClient::factory()->create();
        $nonce = (string) now()->getTimestamp();

        $this->getJson(route('api.server-time'), [
            'X-Client-ID' => $client->client_id,
            'X-Nonce' => $nonce,
            'X-Api-Signature' => hash('sha256', $nonce.'wrong-secret'),
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'The API signature is invalid.');
    }

    public function test_signed_payload_ignores_nulls_and_sorts_values(): void
    {
        $client = ApiClient::factory()->create();
        $nonce = (string) now()->getTimestamp();
        $signature = hash('sha256', 'betazebra'.$nonce.$client->secret);

        $this->call(
            'GET',
            route('api.server-time'),
            server: [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CLIENT_ID' => $client->client_id,
                'HTTP_X_NONCE' => $nonce,
                'HTTP_X_API_SIGNATURE' => $signature,
            ],
            content: json_encode([
                'zebra' => 'zebra',
                'alpha' => null,
                'beta' => 'beta',
            ], JSON_THROW_ON_ERROR),
        )->assertOk();
    }

    public function test_reused_signature_is_rejected_until_the_hour_passes(): void
    {
        $this->freezeTime();

        $client = ApiClient::factory()->create();
        $nonce = 'k3p9Qx';
        $signature = hash('sha256', $nonce.$client->secret);
        $headers = [
            'X-Client-ID' => $client->client_id,
            'X-Nonce' => $nonce,
            'X-Api-Signature' => strtoupper($signature),
        ];

        $this->getJson(route('api.server-time'), $headers)->assertOk();

        $this->getJson(route('api.server-time'), [
            'X-Client-ID' => $client->client_id,
            'X-Nonce' => $nonce,
            'X-Api-Signature' => $signature,
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'The API signature has already been used.')
            ->assertJsonPath('errors.api_signature.0', 'The API signature has already been used.');

        $this->travel(61)->minutes();

        $this->getJson(route('api.server-time'), $headers)->assertOk();
    }

    public function test_invalid_signature_is_not_recorded(): void
    {
        $client = ApiClient::factory()->create();
        $nonce = 'k3p9Qx';
        $headers = [
            'X-Client-ID' => $client->client_id,
            'X-Nonce' => $nonce,
            'X-Api-Signature' => hash('sha256', $nonce.'wrong-secret'),
        ];

        $this->getJson(route('api.server-time'), $headers)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'The API signature is invalid.');

        $this->getJson(route('api.server-time'), $headers)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'The API signature is invalid.');
    }

    public function test_cached_secret_survives_until_the_database_row_is_needed_again(): void
    {
        $this->freezeTime();

        $client = ApiClient::factory()->create();
        $secret = $client->secret;
        $clientId = $client->client_id;

        $this->getJson(route('api.server-time'), $this->signatureHeaders($clientId, $secret, 'first'))
            ->assertOk();

        $cached = Cache::get('api-client-plain-secret:'.$clientId);

        $this->assertSame($secret, $cached);

        DB::table('api_clients')->where('client_id', $clientId)->delete();

        $this->getJson(route('api.server-time'), $this->signatureHeaders($clientId, $secret, 'second'))
            ->assertOk();

        $this->travel(61)->minutes();

        $this->getJson(route('api.server-time'), $this->signatureHeaders($clientId, $secret, 'third'))
            ->assertUnauthorized()
            ->assertJsonPath('message', 'The API signature is invalid.');
    }

    /**
     * @return array<string, string>
     */
    private function signatureHeaders(string $clientId, string $secret, string $nonce): array
    {
        return [
            'X-Client-ID' => $clientId,
            'X-Nonce' => $nonce,
            'X-Api-Signature' => hash('sha256', $nonce.$secret),
        ];
    }
}
