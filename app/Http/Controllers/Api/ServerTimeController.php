<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class ServerTimeController extends ApiController
{
    /**
     * Return the current server time in the application's configured timezone.
     */
    public function show(): JsonResponse
    {
        $timezone = (string) config('app.timezone');
        $now = now()->timezone($timezone);

        return $this->success(
            data: [
                /**
                 * @example 2026-11-10T07:00:00+07:00
                 */
                'iso8601' => $now->toIso8601String(),
                /**
                 * @example Asia/Jakarta
                 */
                'timezone' => $timezone,
                /**
                 * @example +07:00
                 */
                'offset' => $now->format('P'),
                /**
                 * @example 1794268800
                 */
                'timestamp' => (int) $now->getTimestamp(),
            ],
            message: 'Server time retrieved.',
        );
    }
}
