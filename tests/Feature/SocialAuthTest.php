<?php

namespace Tests\Feature;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_sign_in_creates_a_user_and_returns_a_sanctum_token(): void
    {
        $this->mockDriver('google', 'userFromToken', 'valid-id-token', $this->socialUser(
            'google-sub-1',
            'Buyer',
            'buyer@example.com',
            true,
        ));

        $response = $this->signedApi('POST', '/api/auth/google', [
            'identity_token' => 'valid-id-token',
            'device_name' => 'iPhone 16',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Signed in.')
            ->assertJsonPath('data.user.name', 'Buyer')
            ->assertJsonPath('data.user.email', 'buyer@example.com')
            ->assertJsonPath('errors', null);

        $token = $response->json('data.token');
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^\d+\|.+/', $token);

        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_id' => 'google-sub-1',
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'iPhone 16',
            'tokenable_id' => $response->json('data.user.id'),
        ]);

        $this->withToken($token)
            ->signedApi('GET', '/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'buyer@example.com');
    }

    public function test_repeat_google_sign_in_returns_a_token_for_the_same_user(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('userFromToken')->twice()->andReturn(
            $this->socialUser('google-sub-1', 'Buyer', 'buyer@example.com', true),
            $this->socialUser('google-sub-1', 'Someone Else', 'changed@example.com', true),
        );
        Socialite::shouldReceive('driver')->twice()->with('google')->andReturn($driver);

        $this->signedApi('POST', '/api/auth/google', [
            'identity_token' => 'valid-id-token',
        ])->assertOk();

        $this->travel(1)->seconds();

        $this->signedApi('POST', '/api/auth/google', [
            'identity_token' => 'valid-id-token',
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'buyer@example.com')
            ->assertJsonPath('data.user.name', 'Buyer');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'mobile',
        ]);
    }

    public function test_google_sign_in_links_an_existing_user_with_the_same_email(): void
    {
        $existing = User::factory()->unverified()->create([
            'name' => 'Existing',
            'email' => 'buyer@example.com',
        ]);

        $this->mockDriver('google', 'userFromToken', 'valid-id-token', $this->socialUser(
            'google-sub-1',
            'Buyer',
            'buyer@example.com',
            true,
        ));

        $this->signedApi('POST', '/api/auth/google', [
            'identity_token' => 'valid-id-token',
        ])->assertOk()
            ->assertJsonPath('data.user.id', $existing->id)
            ->assertJsonPath('data.user.name', 'Existing');

        $existing->refresh();

        $this->assertNotNull($existing->email_verified_at);
        $this->assertTrue(Hash::check('password', $existing->password));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $existing->id,
            'provider' => 'google',
            'provider_id' => 'google-sub-1',
        ]);
    }

    public function test_google_sign_in_returns_401_when_the_identity_token_is_invalid(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('userFromToken')->once()->andThrow(new Exception('Failed to verify Google JWT token'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $this->signedApi('POST', '/api/auth/google', [
            'identity_token' => 'not-a-token',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'The identity token is invalid.')
            ->assertJsonPath('errors.identity_token.0', 'The identity token is invalid.')
            ->assertJsonPath('data', null);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_sign_in_returns_422_when_the_email_is_not_verified(): void
    {
        $this->mockDriver('google', 'userFromToken', 'valid-id-token', $this->socialUser(
            'google-sub-1',
            'Buyer',
            'buyer@example.com',
            false,
        ));

        $this->signedApi('POST', '/api/auth/google', [
            'identity_token' => 'valid-id-token',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'The identity token email is not verified.')
            ->assertJsonPath('errors.identity_token.0', 'The identity token email is not verified.');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_sign_in_returns_422_when_the_identity_token_is_missing(): void
    {
        $this->signedApi('POST', '/api/auth/google')
            ->assertUnprocessable()
            ->assertJsonPath('errors.identity_token.0', 'The identity token is required.');
    }

    public function test_google_sign_in_returns_422_when_the_device_name_is_too_long(): void
    {
        $this->signedApi('POST', '/api/auth/google', [
            'identity_token' => 'valid-id-token',
            'device_name' => str_repeat('a', 256),
        ])->assertUnprocessable()
            ->assertJsonPath('errors.device_name.0', 'The device name field must not be greater than 255 characters.');
    }

    public function test_google_sign_in_returns_401_without_an_api_signature(): void
    {
        $this->postJson('/api/auth/google', [
            'identity_token' => 'valid-id-token',
        ])->assertUnauthorized()
            ->assertJsonPath('errors.api_signature.0', 'The X-Client-ID header is required.');
    }

    public function test_apple_sign_in_hashes_the_raw_nonce_and_returns_a_sanctum_token(): void
    {
        $this->markTestSkipped('Apple sign-in is disabled.');
        $driver = Mockery::mock();
        $driver->shouldReceive('userByIdentityToken')
            ->once()
            ->with('apple-token', hash('sha256', 'raw-nonce'))
            ->andReturn($this->socialUser('apple-sub-1', null, 'Buyer@privaterelay.appleid.com', null));
        Socialite::shouldReceive('driver')->once()->with('apple')->andReturn($driver);

        $this->signedApi('POST', '/api/auth/apple', [
            'identity_token' => 'apple-token',
            'nonce' => 'raw-nonce',
        ])->assertOk()
            ->assertJsonPath('data.user.name', 'Buyer')
            ->assertJsonPath('data.user.email', 'Buyer@privaterelay.appleid.com');

        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'apple',
            'provider_id' => 'apple-sub-1',
        ]);
    }

    public function test_repeat_apple_sign_in_without_an_email_returns_the_same_user(): void
    {
        $this->markTestSkipped('Apple sign-in is disabled.');
        $driver = Mockery::mock();
        $driver->shouldReceive('userByIdentityToken')->twice()->andReturn(
            $this->socialUser('apple-sub-1', 'Buyer', 'buyer@example.com', 'true'),
            $this->socialUser('apple-sub-1', null, null, null),
        );
        Socialite::shouldReceive('driver')->twice()->with('apple')->andReturn($driver);

        $this->signedApi('POST', '/api/auth/apple', [
            'identity_token' => 'apple-token',
            'nonce' => 'raw-nonce',
        ])->assertOk();

        $this->travel(1)->seconds();

        $this->signedApi('POST', '/api/auth/apple', [
            'identity_token' => 'apple-token-2',
            'nonce' => 'another-nonce',
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'buyer@example.com');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
    }

    public function test_apple_sign_in_returns_422_when_the_first_token_has_no_email(): void
    {
        $this->markTestSkipped('Apple sign-in is disabled.');
        $this->mockDriver('apple', 'userByIdentityToken', 'apple-token', $this->socialUser(
            'apple-sub-1',
            null,
            null,
            null,
        ), hash('sha256', 'raw-nonce'));

        $this->signedApi('POST', '/api/auth/apple', [
            'identity_token' => 'apple-token',
            'nonce' => 'raw-nonce',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'The identity token did not include an email address.')
            ->assertJsonPath('errors.identity_token.0', 'The identity token did not include an email address.');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_apple_sign_in_returns_401_for_an_unverified_identity_token(): void
    {
        $this->markTestSkipped('Apple sign-in is disabled.');
        $this->signedApi('POST', '/api/auth/apple', [
            'identity_token' => 'not-a-token',
            'nonce' => 'raw-nonce',
        ])->assertUnauthorized()
            ->assertJsonPath('errors.identity_token.0', 'The identity token is invalid.');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_apple_sign_in_returns_422_when_the_nonce_is_missing(): void
    {
        $this->markTestSkipped('Apple sign-in is disabled.');
        $this->signedApi('POST', '/api/auth/apple', [
            'identity_token' => 'apple-token',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.nonce.0', 'The nonce is required.');
    }

    private function mockDriver(string $driverName, string $method, string $token, SocialiteUser $user, ?string $nonce = null): void
    {
        $driver = Mockery::mock();
        $expectation = $driver->shouldReceive($method)->once();

        if ($nonce === null) {
            $expectation->with($token);
        } else {
            $expectation->with($token, $nonce);
        }

        $expectation->andReturn($user);

        Socialite::shouldReceive('driver')->once()->with($driverName)->andReturn($driver);
    }

    private function socialUser(string $id, ?string $name, ?string $email, mixed $emailVerified): SocialiteUser
    {
        $raw = ['sub' => $id];

        if ($emailVerified !== null) {
            $raw['email_verified'] = $emailVerified;
        }

        return (new SocialiteUser)->setRaw($raw)->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ]);
    }
}
