<?php

namespace App\Models;

use Database\Factories\ApiClientFactory;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

#[Fillable(['name', 'client_id', 'secret'])]
#[Hidden(['secret'])]
class ApiClient extends Model
{
    /** @use HasFactory<ApiClientFactory> */
    use HasFactory;

    /**
     * Read a legacy encrypted secret until the plaintext migration has run.
     *
     * @return Attribute<string, string>
     */
    protected function secret(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): string {
                if (! is_string($value) || $value === '') {
                    return '';
                }

                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException) {
                    return $value;
                }
            },
            set: fn (string $value): string => $value,
        );
    }

    public static function newClientId(): string
    {
        do {
            $clientId = 'client_'.random_int(10000, 99999);
        } while (static::query()->where('client_id', $clientId)->exists());

        return $clientId;
    }

    public static function newSecret(): string
    {
        $prefix = config('app.env') === 'production' ? 'sk_prod_' : 'sk_dev_';

        return $prefix.Str::random(40);
    }

    /**
     * Resolve the secret, using the value cached for one hour.
     */
    public static function cachedSecret(string $clientId): ?string
    {
        $cacheKey = static::secretCacheKey($clientId);
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $client = static::query()->where('client_id', $clientId)->first();

        if ($client === null || $client->secret === '') {
            return null;
        }

        Cache::put($cacheKey, $client->secret, now()->addHour());

        return $client->secret;
    }

    public static function forgetCachedSecret(string $clientId): void
    {
        Cache::forget(static::secretCacheKey($clientId));
    }

    protected static function booted(): void
    {
        static::updated(function (ApiClient $client): void {
            if ($client->wasChanged('secret')) {
                static::forgetCachedSecret($client->client_id);
            }
        });

        static::deleted(function (ApiClient $client): void {
            static::forgetCachedSecret($client->client_id);
        });
    }

    private static function secretCacheKey(string $clientId): string
    {
        return 'api-client-plain-secret:'.$clientId;
    }
}
