<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_admin_can_create_an_api_client_and_see_the_secret_once(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.api-clients.store'), [
                'name' => 'Mobile app',
                'client_id' => 'client_hacked',
                'secret' => 'sk_dev_hacked',
            ]);

        $response->assertRedirect(route('admin.api-clients.index'));
        $response->assertSessionHas('api_client_credentials');

        /** @var array{client_id: string, secret: string} $credentials */
        $credentials = session('api_client_credentials');

        $this->assertMatchesRegularExpression('/^client_\d{5}$/', $credentials['client_id']);
        $this->assertStringStartsWith('sk_dev_', $credentials['secret']);
        $this->assertNotSame('client_hacked', $credentials['client_id']);
        $this->assertNotSame('sk_dev_hacked', $credentials['secret']);

        $this->assertDatabaseHas('api_clients', [
            'name' => 'Mobile app',
            'client_id' => $credentials['client_id'],
        ]);
        $this->assertDatabaseMissing('api_clients', [
            'secret' => $credentials['secret'],
        ]);

        $stored = ApiClient::query()->where('client_id', $credentials['client_id'])->first();

        $this->assertNotNull($stored);
        $this->assertSame($credentials['secret'], $stored->secret);
    }

    public function test_production_secret_uses_the_prod_prefix(): void
    {
        config(['app.env' => 'production']);

        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.api-clients.store'), [
                'name' => 'Production app',
            ])
            ->assertSessionHas('api_client_credentials');

        /** @var array{secret: string} $credentials */
        $credentials = session('api_client_credentials');

        $this->assertStringStartsWith('sk_prod_', $credentials['secret']);
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

        /** @var array{secret: string} $credentials */
        $credentials = session('api_client_credentials');

        $apiClient->refresh();

        $this->assertNotSame($originalSecret, $credentials['secret']);
        $this->assertSame($credentials['secret'], $apiClient->secret);
        $this->assertStringStartsWith('sk_dev_', $credentials['secret']);
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

        $this->assertFalse(Cache::has('api-client-secret:'.$apiClient->client_id));

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

        $this->assertFalse(Cache::has('api-client-secret:'.$apiClient->client_id));

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

    public function test_api_client_screens_warn_that_the_secret_is_never_sent(): void
    {
        $admin = Admin::factory()->create();
        $apiClient = ApiClient::factory()->create();
        $warning = 'Store the secret securely and never send it with a request.';

        $this->actingAs($admin, 'admin')
            ->get(route('admin.api-clients.index'))
            ->assertOk()
            ->assertSee($warning);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.api-clients.create'))
            ->assertOk()
            ->assertSee($warning);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.api-clients.edit', $apiClient))
            ->assertOk()
            ->assertSee($warning);
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
