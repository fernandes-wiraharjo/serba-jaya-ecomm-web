<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Build the standard successful API payload.
     */
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return self::make(true, $message, $data, null, $status);
    }

    /**
     * Build the standard failed API payload.
     *
     * @param  array<string, list<string>>|null  $errors
     */
    public static function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        return self::make(false, $message, null, $errors, $status);
    }

    /**
     * @param  array<string, list<string>>|null  $errors
     */
    private static function make(bool $success, string $message, mixed $data, ?array $errors, int $status): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $status);
    }
}
