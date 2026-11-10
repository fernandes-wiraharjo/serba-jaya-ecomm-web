<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_api_clients(): void
    {
        Admin::factory()->create();

        $this->get(route('admin.api-clients.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_create_an_api_client_and_keep_seeing_the_secret(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.api-clients.store'), [
                'name' => 'Mobile app',
                'client_id' => 'client_hacked',
                'secret' => 'sk_dev_hacked',
            ]);

        $response->assertRedirect(route('admin.api-clients.index'));
        $response->assertSessionMissing('api_client_credentials');

        $stored = ApiClient::query()->where('name', 'Mobile app')->first();

        $this->assertNotNull($stored);
        $this->assertMatchesRegularExpression('/^client_\d{5}$/', $stored->client_id);
        $this->assertStringStartsWith('sk_dev_', $stored->secret);
        $this->assertNotSame('client_hacked', $stored->client_id);
        $this->assertNotSame('sk_dev_hacked', $stored->secret);
        $this->assertDatabaseHas('api_clients', [
            'name' => 'Mobile app',
            'client_id' => $stored->client_id,
            'secret' => $stored->secret,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.api-clients.index'))
            ->assertOk()
            ->assertSee($stored->secret)
            ->assertDontSee('It will not be shown again.');
    }

    public function test_legacy_encrypted_secret_is_still_readable(): void
    {
        $secret = 'sk_dev_legacysecretlegacysecretlegacysecret12';
        $apiClient = ApiClient::factory()->create([
            'secret' => $secret,
        ]);

        DB::table('api_clients')->where('id', $apiClient->id)->update([
            'secret' => Crypt::encryptString($secret),
        ]);

        $apiClient->refresh();

        $this->assertSame($secret, $apiClient->secret);
    }

    public function test_production_secret_uses_the_prod_prefix(): void
    {
        config(['app.env' => 'production']);

        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.api-clients.store'), [
                'name' => 'Production app',
            ])
            ->assertSessionMissing('api_client_credentials');

        $stored = ApiClient::query()->where('name', 'Production app')->first();

        $this->assertNotNull($stored);
        $this->assertStringStartsWith('sk_prod_', $stored->secret);
    }

    public function test_create_api_client_requires_a_name(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.api-clients.create'))
            ->post(route('admin.api-clients.store'), [])
            ->assertRedirect(route('admin.api-clients.create'))
            ->assertSessionHasErrors(['name']);
    }

    public function test_admin_can_rename_an_api_client_without_changing_its_id(): void
    {
        $admin = Admin::factory()->create();
        $apiClient = ApiClient::factory()->create([
            'name' => 'Old name',
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.api-clients.update', $apiClient), [
                'name' => 'New name',
                'client_id' => 'client_hacked',
            ])
            ->assertRedirect(route('admin.api-clients.index'));

        $apiClient->refresh();

        $this->assertSame('New name', $apiClient->name);
        $this->assertNotSame('client_hacked', $apiClient->client_id);
    }

    public function test_admin_can_regenerate_a_secret(): void
    {
        $admin = Admin::factory()->create();
        $apiClient = ApiClient::factory()->create();
        $originalSecret = $apiClient->secret;

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.api-clients.regenerate', $apiClient));

        $response->assertRedirect(route('admin.api-clients.index'));
        $response->assertSessionMissing('api_client_credentials');

        $apiClient->refresh();

        $this->assertNotSame($originalSecret, $apiClient->secret);
        $this->assertStringStartsWith('sk_dev_', $apiClient->secret);
        $this->assertDatabaseHas('api_clients', [
            'id' => $apiClient->id,
            'secret' => $apiClient->secret,
        ]);
    }

    public function test_regenerating_a_secret_drops_the_cached_secret(): void
    {
        $admin = Admin::factory()->create();
        $apiClient = ApiClient::factory()->create();
        $originalSecret = $apiClient->secret;

        $this->getJson(route('api.server-time'), [
            'X-Client-ID' => $apiClient->client_id,
            'X-Nonce' => 'warm',
            'X-Api-Signature' => hash('sha256', 'warm'.$originalSecret),
        ])->assertOk();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.api-clients.regenerate', $apiClient))
            ->assertRedirect(route('admin.api-clients.index'));

        $this->assertFalse(Cache::has('api-client-plain-secret:'.$apiClient->client_id));

        $this->getJson(route('api.server-time'), [
            'X-Client-ID' => $apiClient->client_id,
            'X-Nonce' => 'after',
            'X-Api-Signature' => hash('sha256', 'after'.$originalSecret),
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'The API signature is invalid.');
    }

    public function test_deleting_a_client_drops_the_cached_secret(): void
    {
        $admin = Admin::factory()->create();
        $apiClient = ApiClient::factory()->create();
        $secret = $apiClient->secret;

        $this->getJson(route('api.server-time'), [
            'X-Client-ID' => $apiClient->client_id,
            'X-Nonce' => 'warm',
            'X-Api-Signature' => hash('sha256', 'warm'.$secret),
        ])->assertOk();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.api-clients.destroy', $apiClient))
            ->assertRedirect(route('admin.api-clients.index'));

        $this->assertFalse(Cache::has('api-client-plain-secret:'.$apiClient->client_id));

        $this->getJson(route('api.server-time'), [
            'X-Client-ID' => $apiClient->client_id,
            'X-Nonce' => 'after',
            'X-Api-Signature' => hash('sha256', 'after'.$secret),
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'The API signature is invalid.');
    }

    public function test_admin_can_delete_an_api_client(): void
    {
        $admin = Admin::factory()->create();
        $apiClient = ApiClient::factory()->create();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.api-clients.destroy', $apiClient))
            ->assertRedirect(route('admin.api-clients.index'));

        $this->assertModelMissing($apiClient);
    }

    public function test_api_client_screens_leave_signature_instructions_to_the_docs(): void
    {
        $admin = Admin::factory()->create();
        $apiClient = ApiClient::factory()->create();
        $wording = 'Store the secret securely and never send it with a request.';

        $this->actingAs($admin, 'admin')
            ->get(route('admin.api-clients.index'))
            ->assertOk()
            ->assertDontSee($wording)
            ->assertDontSee('Every API request sends');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.api-clients.create'))
            ->assertOk()
            ->assertDontSee($wording);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.api-clients.edit', $apiClient))
            ->assertOk()
            ->assertDontSee($wording);
    }

    public function test_api_client_name_is_escaped(): void
    {
        $admin = Admin::factory()->create();
        $name = "<script>alert('xss')</script>";

        ApiClient::factory()->create([
            'name' => $name,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.api-clients.index'));

        $response->assertOk();
        $response->assertSee($name);
        $response->assertDontSee($name, false);
    }
}
