<?php

use App\Http\Middleware\HandleAdminAccess;
use App\Http\Middleware\VerifyApiSignature;
use App\Http\Responses\ApiResponse;
use App\Models\Admin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.access' => HandleAdminAccess::class,
        ]);

        $middleware->appendToGroup('api', VerifyApiSignature::class);

        $middleware->redirectGuestsTo(function () {
            return Admin::query()->exists()
                ? route('admin.login')
                : route('admin.setup');
        });

        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (! $request->is('api/*') || ! $response instanceof JsonResponse) {
                return $response;
            }

            /** @var array<string, mixed>|null $payload */
            $payload = json_decode($response->getContent(), true);

            if (is_array($payload) && array_key_exists('success', $payload)) {
                return $response;
            }

            $message = is_array($payload) && is_string($payload['message'] ?? null)
                ? $payload['message']
                : 'Server error.';

            /** @var array<string, list<string>>|null $errors */
            $errors = is_array($payload) && is_array($payload['errors'] ?? null)
                ? $payload['errors']
                : null;

            return ApiResponse::error($message, $response->getStatusCode(), $errors);
        });
    })->create();
