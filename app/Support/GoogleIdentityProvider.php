<?php

namespace App\Support;

use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Laravel\Socialite\Two\GoogleProvider;

class GoogleIdentityProvider extends GoogleProvider
{
    /**
     * Accept an ID token whose audience is the configured client id or any extra mobile client id.
     *
     * @param  string  $idToken
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    protected function getUserFromJwtToken($idToken)
    {
        try {
            $user = (array) JWT::decode(
                $idToken, JWK::parseKeySet($this->getGoogleJwks())
            );

            if (($user['iss'] ?? null) !== 'https://accounts.google.com') {
                throw new Exception('Invalid ID token issuer.');
            }

            if (! isset($user['aud']) || ! in_array($user['aud'], $this->audiences(), true)) {
                throw new Exception('Invalid ID token audience.');
            }

            return $user;
        } catch (Exception $exception) {
            throw new Exception('Failed to verify Google JWT token: '.$exception->getMessage());
        }
    }

    /**
     * @return list<string>
     */
    private function audiences(): array
    {
        $extra = config('services.google.client_ids');

        return array_values(array_unique(array_filter(
            array_merge([$this->clientId], is_array($extra) ? $extra : []),
            fn (mixed $audience): bool => is_string($audience) && $audience !== '',
        )));
    }
}
