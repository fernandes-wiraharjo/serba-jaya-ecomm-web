<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }

    /**
     * @param  array<string, list<string>>|null  $errors
     */
    protected function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        return ApiResponse::error($message, $status, $errors);
    }
}
