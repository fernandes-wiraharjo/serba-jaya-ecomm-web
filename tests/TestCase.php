<?php

namespace Tests;

use App\Models\ApiClient;
use App\Support\ApiSignature;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Send a JSON request signed with the client's secret and the current nonce.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function signedApi(string $method, string $uri, array $payload = [], ?ApiClient $client = null): TestResponse
    {
        $client ??= ApiClient::factory()->create();
        $nonce = (string) now()->getTimestamp();
        $signature = app(ApiSignature::class)->hash($payload, $nonce, $client->secret);

        return $this->json($method, $uri, $payload, [
            'X-Client-ID' => $client->client_id,
            'X-Nonce' => $nonce,
            'X-Api-Signature' => $signature,
        ]);
    }
}
