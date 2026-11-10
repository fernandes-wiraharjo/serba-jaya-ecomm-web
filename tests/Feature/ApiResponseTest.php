<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_api_route_returns_404_in_the_standard_envelope(): void
    {
        $this->getJson('/api/missing-endpoint')
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'message' => 'The route api/missing-endpoint could not be found.',
                'data' => null,
                'errors' => null,
            ]);
    }

    public function test_unauthenticated_api_user_returns_401_in_the_standard_envelope(): void
    {
        $this->getJson(route('api.user'))
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
                'errors' => null,
            ]);
    }

    public function test_authenticated_api_user_is_returned_in_the_standard_envelope(): void
    {
        $user = User::factory()->create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.user'));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'OK');
        $response->assertJsonPath('errors', null);
        $response->assertJsonPath('data.email', 'buyer@example.com');
        $response->assertJsonPath('data.name', 'Buyer');
        $response->assertJsonMissingPath('data.password');
    }
}
