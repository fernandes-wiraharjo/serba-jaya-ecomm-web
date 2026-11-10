<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class ApiSignature
{
    /**
     * Hash payload values, the nonce, and the client secret.
     *
     * Values are taken from keys in alphabetical order. Null values are omitted.
     * With no remaining values the material is the nonce followed by the secret.
     *
     * @param  array<string, mixed>  $payload
     */
    public function hash(array $payload, string $nonce, string $secret): string
    {
        return hash('sha256', $this->values($payload).$nonce.$secret);
    }

    /**
     * Record an accepted signature for one hour.
     *
     * Returns false when that signature was already accepted within the hour.
     */
    public function claim(string $signature): bool
    {
        return Cache::add('api-signature:'.strtolower($signature), true, now()->addHour());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function values(array $payload): string
    {
        $values = [];

        foreach ($payload as $key => $value) {
            if ($value === null) {
                continue;
            }

            $values[(string) $key] = $value;
        }

        ksort($values, SORT_STRING);

        $material = '';

        foreach ($values as $value) {
            $material .= $this->stringify($value);
        }

        return $material;
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value) || is_string($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            $encoded = json_encode(
                $this->normalize($value),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );

            return $encoded === false ? '' : $encoded;
        }

        return '';
    }

    /**
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private function normalize(array $value): array
    {
        if (array_is_list($value)) {
            $items = [];

            foreach ($value as $item) {
                if ($item === null) {
                    continue;
                }

                $items[] = is_array($item) ? $this->normalize($item) : $item;
            }

            return $items;
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            if ($item === null) {
                continue;
            }

            $normalized[(string) $key] = is_array($item) ? $this->normalize($item) : $item;
        }

        ksort($normalized, SORT_STRING);

        return $normalized;
    }
}
