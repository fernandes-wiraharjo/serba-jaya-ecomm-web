<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AppleSignInRequest;
use App\Http\Requests\Api\GoogleSignInRequest;
use App\Models\SocialAccount;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class SocialAuthController extends ApiController
{
    /**
     * SDK Login: Exchange a Google identity token for a Sanctum API token.
     *
     * This endpoint is used when the frontend manages sign-in with the SDK. For web-based login, see [Google login URL](/docs/api#/operations/auth.google.url).
     */
    public function google(GoogleSignInRequest $request): JsonResponse
    {
        try {
            $socialUser = Socialite::driver('google')->userFromToken($request->validated('identity_token'));
        } catch (Exception) {
            return $this->invalidIdentityToken();
        }

        if (! $socialUser instanceof SocialiteUser) {
            return $this->invalidIdentityToken();
        }

        return $this->issueToken('google', $socialUser, $request->validated('device_name'));
    }

    /**
     * Web-based Login: Get the Google authorization URL.
     *
     * Open the URL in the browser. After Google redirects back, the callback opens the Android app with an intent link.
     */
    public function googleUrl(): JsonResponse
    {
        if (! $this->googleOAuthIsConfigured()) {
            return $this->error('Google sign-in is not configured.', 500);
        }

        $state = Str::random(40);
        Cache::put($this->googleStateKey($state), true, now()->addMinutes(10));

        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        $url .= (str_contains($url, '?') ? '&' : '?').'state='.rawurlencode($state);

        return $this->success(
            data: [
                /**
                 * @example https://accounts.google.com/o/oauth2/auth?client_id=example.apps.googleusercontent.com&redirect_uri=http%3A%2F%2Flocalhost%3A8000%2Fauth%2Fgoogle%2Fcallback&scope=openid%20profile%20email&response_type=code&state=exampleState012345678901234567890123456789
                 */
                'url' => $url,
            ],
            message: 'Google login URL generated.',
        );
    }

    /**
     * Finish the Google browser sign-in and open the Android app.
     *
     * Google redirects the browser here. This route is not API-signed. A successful sign-in redirects to intent://callback?token=... so Android opens the app.
     */
    public function googleCallback(Request $request): RedirectResponse
    {
        $this->ensureGoogleAppIntentIsConfigured();

        $oauthError = $request->string('error')->toString();

        if ($oauthError !== '') {
            $this->pullGoogleState($request->string('state')->toString());

            return $this->redirectToApp(null, $this->safeOAuthError($oauthError));
        }

        if (! $this->pullGoogleState($request->string('state')->toString())) {
            return $this->redirectToApp(null, 'invalid_state');
        }

        try {
            $socialUser = Socialite::driver('google')->stateless()->user();
        } catch (Exception) {
            return $this->redirectToApp(null, 'sign_in_failed');
        }

        if (! $socialUser instanceof SocialiteUser) {
            return $this->redirectToApp(null, 'sign_in_failed');
        }

        $user = $this->resolveUser('google', $socialUser);

        if ($user instanceof JsonResponse) {
            return $this->redirectToApp(null, 'sign_in_failed');
        }

        return $this->redirectToApp($user->createToken('google')->plainTextToken, null);
    }

    /**
     * Exchange an Apple identity token for a Sanctum token.
     *
     * Apple sign-in is a frontend task. The mobile app signs the user in with Apple and sends only the identity token it already received, plus the raw nonce it created. This API hashes that nonce with SHA-256 and does not redirect to Apple.
     */
    public function apple(AppleSignInRequest $request): JsonResponse
    {
        try {
            $socialUser = Socialite::driver('apple')->userByIdentityToken(
                $request->validated('identity_token'),
                hash('sha256', $request->validated('nonce')),
            );
        } catch (Exception) {
            return $this->invalidIdentityToken();
        }

        if (! $socialUser instanceof SocialiteUser) {
            return $this->invalidIdentityToken();
        }

        return $this->issueToken('apple', $socialUser, $request->validated('device_name'));
    }

    private function issueToken(string $provider, SocialiteUser $socialUser, mixed $deviceName): JsonResponse
    {
        $user = $this->resolveUser($provider, $socialUser);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $tokenName = is_string($deviceName) && $deviceName !== '' ? $deviceName : 'mobile';

        return $this->success(
            data: [
                /**
                 * @example 1|s3rbaJayaExampleToken
                 */
                'token' => $user->createToken($tokenName)->plainTextToken,
                'user' => [
                    /**
                     * @example 1
                     */
                    'id' => $user->id,
                    /**
                     * @example Buyer
                     */
                    'name' => $user->name,
                    /**
                     * @example buyer@example.com
                     */
                    'email' => $user->email,
                ],
            ],
            message: 'Signed in.',
        );
    }

    private function resolveUser(string $provider, SocialiteUser $socialUser): User|JsonResponse
    {
        $providerId = $socialUser->getId();

        if (! is_string($providerId) && ! is_int($providerId)) {
            return $this->invalidIdentityToken();
        }

        $providerId = (string) $providerId;

        if ($providerId === '') {
            return $this->invalidIdentityToken();
        }

        $account = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        $user = $account?->user;

        if ($user !== null) {
            return $user;
        }

        $email = $socialUser->getEmail();

        if (! is_string($email) || $email === '') {
            return $this->identityTokenError('The identity token did not include an email address.');
        }

        if (! $this->emailIsVerified($socialUser, $provider)) {
            return $this->identityTokenError('The identity token email is not verified.');
        }

        return $this->findOrCreateUser($provider, $providerId, $email, $socialUser->getName());
    }

    private function findOrCreateUser(string $provider, string $providerId, string $email, mixed $name): User
    {
        return DB::transaction(function () use ($provider, $providerId, $email, $name): User {
            $account = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_id', $providerId)
                ->lockForUpdate()
                ->first();

            if ($account?->user !== null) {
                return $account->user;
            }

            $displayName = is_string($name) ? trim($name) : '';

            if ($displayName === '') {
                $displayName = Str::before($email, '@');
            }

            if ($displayName === '') {
                $displayName = 'Customer';
            }

            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $displayName,
                    'password' => Str::password(),
                    'email_verified_at' => now(),
                ],
            );

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => $providerId,
            ]);

            return $user;
        });
    }

    private function emailIsVerified(SocialiteUser $socialUser, string $provider): bool
    {
        $raw = $socialUser->getRaw();

        if (! is_array($raw)) {
            return $provider === 'apple';
        }

        $verified = $raw['email_verified'] ?? $raw['verified_email'] ?? null;

        if ($verified === null) {
            return $provider === 'apple';
        }

        return $verified === true || $verified === 'true' || $verified === 1 || $verified === '1';
    }

    private function identityTokenError(string $message, int $status = 422): JsonResponse
    {
        return $this->error($message, $status, [
            'identity_token' => [$message],
        ]);
    }

    private function invalidIdentityToken(): JsonResponse
    {
        return $this->identityTokenError('The identity token is invalid.', 401);
    }

    private function googleOAuthIsConfigured(): bool
    {
        foreach (['client_id', 'client_secret', 'redirect'] as $key) {
            $value = config('services.google.'.$key);

            if (! is_string($value) || $value === '') {
                return false;
            }
        }

        return true;
    }

    private function ensureGoogleAppIntentIsConfigured(): void
    {
        $scheme = config('services.google.mobile_scheme');
        $package = config('services.google.mobile_package');

        $schemeIsValid = is_string($scheme) && preg_match('/^[a-z][a-z0-9+.-]*$/', $scheme) === 1;
        $packageIsValid = is_string($package) && preg_match('/^[A-Za-z][A-Za-z0-9_]*(\.[A-Za-z][A-Za-z0-9_]*)+$/', $package) === 1;

        if (! $schemeIsValid || ! $packageIsValid) {
            abort(500, 'Google app intent is not configured.');
        }
    }

    private function redirectToApp(?string $token, ?string $error): RedirectResponse
    {
        $scheme = config('services.google.mobile_scheme');
        $package = config('services.google.mobile_package');

        $query = [];

        if (is_string($token) && $token !== '') {
            $query['token'] = $token;
        }

        if (is_string($error) && $error !== '') {
            $query['error'] = $error;
        }

        $intent = 'intent://callback';

        if ($query !== []) {
            $intent .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $intent .= '#Intent;scheme='.$scheme.';package='.$package.';end';

        return redirect()->away($intent);
    }

    private function pullGoogleState(string $state): bool
    {
        if (preg_match('/^[A-Za-z0-9]{40}$/', $state) !== 1) {
            return false;
        }

        return Cache::pull($this->googleStateKey($state)) === true;
    }

    private function googleStateKey(string $state): string
    {
        return 'google-oauth-state:'.$state;
    }

    private function safeOAuthError(string $error): string
    {
        if (preg_match('/^[a-z_]{1,40}$/', $error) === 1) {
            return $error;
        }

        return 'sign_in_failed';
    }
}
