<?php

namespace Tests\Feature;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'google-client',
            'services.google.client_secret' => 'google-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
            'services.google.mobile_scheme' => 'serbajaya',
            'services.google.mobile_package' => 'com.serbajaya.app',
        ]);
    }

    public function test_google_login_url_includes_the_oauth_client_and_callback(): void
    {
        $response = $this->signedApi('GET', '/api/auth/google/url');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Google login URL generated.');

        $url = $response->json('data.url');
        $this->assertIsString($url);

        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('https', parse_url($url, PHP_URL_SCHEME));
        $this->assertSame('accounts.google.com', parse_url($url, PHP_URL_HOST));
        $this->assertSame('/o/oauth2/auth', parse_url($url, PHP_URL_PATH));
        $this->assertSame('google-client', $query['client_id']);
        $this->assertSame('http://localhost/auth/google/callback', $query['redirect_uri']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('openid profile email', $query['scope']);
        $this->assertIsString($query['state']);
        $this->assertSame(40, strlen($query['state']));
    }

    public function test_google_login_url_returns_401_without_an_api_signature(): void
    {
        $this->getJson('/api/auth/google/url')
            ->assertUnauthorized()
            ->assertJsonPath('errors.api_signature.0', 'The X-Client-ID header is required.');
    }

    public function test_google_login_url_returns_500_when_google_is_not_configured(): void
    {
        config([
            'services.google.client_id' => '',
            'services.google.client_secret' => '',
            'services.google.redirect' => '',
        ]);

        $this->signedApi('GET', '/api/auth/google/url')
            ->assertServerError()
            ->assertJsonPath('message', 'Google sign-in is not configured.');
    }

    public function test_google_callback_redirects_to_the_app_intent_with_a_sanctum_token(): void
    {
        $state = $this->stateFromLoginUrl();
        $this->mockGoogleUser($this->socialUser('google-sub-1', 'Buyer', 'buyer@example.com', true));

        $response = $this->get('/auth/google/callback?code=auth-code&state='.$state);

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertIsString($location);

        $parts = parse_url($location);
        $this->assertIsArray($parts);
        $this->assertSame('intent', $parts['scheme']);
        $this->assertSame('callback', $parts['host']);
        $this->assertSame('Intent;scheme=serbajaya;package=com.serbajaya.app;end', $parts['fragment']);

        $query = [];
        parse_str((string) $parts['query'], $query);
        $this->assertIsString($query['token']);
        $this->assertMatchesRegularExpression('/^\d+\|.+/', $query['token']);
        $this->assertArrayNotHasKey('error', $query);

        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_id' => 'google-sub-1',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'buyer@example.com',
            'name' => 'Buyer',
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'google',
        ]);
    }

    public function test_google_callback_redirects_with_invalid_state_when_the_state_is_reused(): void
    {
        $state = $this->stateFromLoginUrl();
        $this->mockGoogleUser($this->socialUser('google-sub-1', 'Buyer', 'buyer@example.com', true));

        $this->get('/auth/google/callback?code=auth-code&state='.$state)->assertRedirect();

        $response = $this->get('/auth/google/callback?code=auth-code&state='.$state);

        $response->assertRedirect();
        $query = $this->intentQuery($response);
        $this->assertSame('invalid_state', $query['error']);
        $this->assertArrayNotHasKey('token', $query);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_callback_redirects_with_invalid_state_when_the_state_is_unknown(): void
    {
        Socialite::shouldReceive('driver')->never();

        $response = $this->get('/auth/google/callback?code=auth-code&state='.str_repeat('a', 40));

        $response->assertRedirect();
        $query = $this->intentQuery($response);
        $this->assertSame('invalid_state', $query['error']);
        $this->assertArrayNotHasKey('token', $query);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_callback_redirects_with_access_denied_when_google_returns_an_error(): void
    {
        $state = $this->stateFromLoginUrl();
        Socialite::shouldReceive('driver')->never();

        $response = $this->get('/auth/google/callback?error=access_denied&state='.$state);

        $response->assertRedirect();
        $query = $this->intentQuery($response);
        $this->assertSame('access_denied', $query['error']);
        $this->assertArrayNotHasKey('token', $query);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_callback_redirects_with_sign_in_failed_when_the_provider_error_is_unexpected(): void
    {
        Socialite::shouldReceive('driver')->never();

        $response = $this->get('/auth/google/callback?error='.urlencode('<script>alert(1)</script>'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertIsString($location);
        $this->assertStringNotContainsString('<script>', $location);
        $this->assertSame('sign_in_failed', $this->intentQuery($response)['error']);
    }

    public function test_google_callback_redirects_with_sign_in_failed_when_the_code_exchange_fails(): void
    {
        $state = $this->stateFromLoginUrl();

        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->once()->andReturnSelf();
        $driver->shouldReceive('user')->once()->andThrow(new Exception('rejected'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $response = $this->get('/auth/google/callback?code=auth-code&state='.$state);

        $response->assertRedirect();
        $query = $this->intentQuery($response);
        $this->assertSame('sign_in_failed', $query['error']);
        $this->assertArrayNotHasKey('token', $query);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_callback_redirects_with_sign_in_failed_when_the_email_is_not_verified(): void
    {
        $state = $this->stateFromLoginUrl();
        $this->mockGoogleUser($this->socialUser('google-sub-1', 'Buyer', 'buyer@example.com', false));

        $response = $this->get('/auth/google/callback?code=auth-code&state='.$state);

        $response->assertRedirect();
        $query = $this->intentQuery($response);
        $this->assertSame('sign_in_failed', $query['error']);
        $this->assertArrayNotHasKey('token', $query);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_callback_returns_500_when_the_app_intent_is_not_configured(): void
    {
        config([
            'services.google.mobile_scheme' => '',
            'services.google.mobile_package' => '',
        ]);

        $this->get('/auth/google/callback?code=auth-code&state='.str_repeat('b', 40))
            ->assertServerError();

        $this->assertDatabaseCount('users', 0);
    }

    private function stateFromLoginUrl(): string
    {
        $response = $this->signedApi('GET', '/api/auth/google/url');
        $response->assertOk();

        $url = $response->json('data.url');
        $this->assertIsString($url);

        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertIsString($query['state']);

        return $query['state'];
    }

    private function mockGoogleUser(SocialiteUser $user): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->once()->andReturnSelf();
        $driver->shouldReceive('user')->once()->andReturn($user);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);
    }

    private function socialUser(string $id, ?string $name, ?string $email, mixed $emailVerified): SocialiteUser
    {
        return (new SocialiteUser)->setRaw([
            'sub' => $id,
            'email_verified' => $emailVerified,
        ])->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function intentQuery(TestResponse $response): array
    {
        $location = $response->headers->get('Location');
        $this->assertIsString($location);

        $query = [];
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return $query;
    }
}
