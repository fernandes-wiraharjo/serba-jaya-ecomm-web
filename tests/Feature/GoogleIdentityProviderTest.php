<?php

namespace Tests\Feature;

use App\Support\GoogleIdentityProvider;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Tests\TestCase;

class GoogleIdentityProviderTest extends TestCase
{
    public function test_sdk_client_id_is_accepted_without_the_web_client_secret(): void
    {
        config([
            'services.google.client_ids' => ['sdk-client.apps.googleusercontent.com'],
        ]);

        [$privateKey, $jwks] = $this->rsaKeyAndJwks();
        $provider = $this->provider($jwks);

        $sdkUser = $provider->userFromIdToken($this->identityToken($privateKey, 'sdk-client.apps.googleusercontent.com'));
        $webUser = $provider->userFromIdToken($this->identityToken($privateKey, 'web-client.apps.googleusercontent.com'));

        $this->assertSame('google-sub-1', $sdkUser['sub']);
        $this->assertSame('buyer@example.com', $sdkUser['email']);
        $this->assertSame('google-sub-1', $webUser['sub']);
    }

    public function test_identity_token_for_an_unknown_client_id_is_rejected(): void
    {
        config([
            'services.google.client_ids' => ['sdk-client.apps.googleusercontent.com'],
        ]);

        [$privateKey, $jwks] = $this->rsaKeyAndJwks();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid ID token audience.');

        $this->provider($jwks)->userFromIdToken($this->identityToken($privateKey, 'other-client.apps.googleusercontent.com'));
    }

    /**
     * @param  array<string, mixed>  $jwks
     */
    private function provider(array $jwks): GoogleIdentityProviderProbe
    {
        return new GoogleIdentityProviderProbe(
            Request::create('/'),
            'web-client.apps.googleusercontent.com',
            'web-secret',
            'http://localhost/callback',
            $jwks,
        );
    }

    /**
     * @return array{0: string, 1: array{keys: list<array<string, string>>}}
     */
    private function rsaKeyAndJwks(): array
    {
        $openSslConfig = $this->openSslConfig();
        $key = openssl_pkey_new($openSslConfig);

        $this->assertNotFalse($key);

        $privateKey = '';
        $this->assertTrue(openssl_pkey_export($key, $privateKey, null, $openSslConfig));

        $details = openssl_pkey_get_details($key);
        $this->assertIsArray($details);

        return [$privateKey, [
            'keys' => [[
                'kty' => 'RSA',
                'alg' => 'RS256',
                'use' => 'sig',
                'kid' => 'test-key',
                'n' => $this->base64Url($details['rsa']['n']),
                'e' => $this->base64Url($details['rsa']['e']),
            ]],
        ]];
    }

    private function identityToken(string $privateKey, string $audience): string
    {
        return JWT::encode([
            'iss' => 'https://accounts.google.com',
            'aud' => $audience,
            'sub' => 'google-sub-1',
            'email' => 'buyer@example.com',
            'email_verified' => true,
            'iat' => time(),
            'exp' => time() + 3600,
        ], $privateKey, 'RS256', 'test-key');
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * @return array{private_key_bits: int, private_key_type: int, config?: string}
     */
    private function openSslConfig(): array
    {
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        if ($this->openSslCanCreateKey($config)) {
            return $config;
        }

        $configured = getenv('OPENSSL_CONF');
        $candidates = is_string($configured) && $configured !== '' ? [$configured] : [];

        foreach ([dirname(base_path(), 2), dirname(PHP_BINDIR), dirname(PHP_BINDIR, 2)] as $root) {
            foreach ([
                'apache'.DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'openssl.cnf',
                'php'.DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
                'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
                'extras'.DIRECTORY_SEPARATOR.'openssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
            ] as $relative) {
                $candidates[] = $root.DIRECTORY_SEPARATOR.$relative;
            }
        }

        foreach (array_unique($candidates) as $candidate) {
            if (! is_file($candidate)) {
                continue;
            }

            $withConfig = $config;
            $withConfig['config'] = $candidate;

            if ($this->openSslCanCreateKey($withConfig)) {
                return $withConfig;
            }
        }

        return $config;
    }

    /**
     * @param  array{private_key_bits: int, private_key_type: int, config?: string}  $config
     */
    private function openSslCanCreateKey(array $config): bool
    {
        while (openssl_error_string() !== false) {
        }

        return openssl_pkey_new($config) !== false;
    }
}

class GoogleIdentityProviderProbe extends GoogleIdentityProvider
{
    /**
     * @param  array<string, mixed>  $jwks
     */
    public function __construct(
        Request $request,
        string $clientId,
        string $clientSecret,
        string $redirectUrl,
        public array $jwks,
    ) {
        parent::__construct($request, $clientId, $clientSecret, $redirectUrl);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getGoogleJwks()
    {
        return $this->jwks;
    }

    /**
     * @return array<string, mixed>
     */
    public function userFromIdToken(string $idToken): array
    {
        return $this->getUserFromJwtToken($idToken);
    }
}
