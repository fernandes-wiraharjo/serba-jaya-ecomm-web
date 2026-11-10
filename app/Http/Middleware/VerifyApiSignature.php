<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\ApiClient;
use App\Support\ApiSignature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiSignature
{
    public function __construct(private ApiSignature $apiSignature) {}

    /**
     * Require X-Client-ID, X-Nonce, and X-Api-Signature on every API request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->header('X-Client-ID');
        $nonce = $request->header('X-Nonce');
        $signature = $request->header('X-Api-Signature');

        if (! is_string($clientId) || $clientId === '') {
            return $this->reject('The X-Client-ID header is required.');
        }

        if (! is_string($nonce) || $nonce === '') {
            return $this->reject('The X-Nonce header is required.');
        }

        if (! is_string($signature) || $signature === '') {
            return $this->reject('The X-Api-Signature header is required.');
        }

        $secret = ApiClient::cachedSecret($clientId);

        if ($secret === null) {
            return $this->reject('The API signature is invalid.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->input();
        $provided = strtolower($signature);
        $expected = $this->apiSignature->hash($payload, $nonce, $secret);

        if (! hash_equals($expected, $provided)) {
            return $this->reject('The API signature is invalid.');
        }

        if (! $this->apiSignature->claim($provided)) {
            return $this->reject('The API signature has already been used.');
        }

        return $next($request);
    }

    /**
     * Reject the request with the signature failure on the api_signature field.
     */
    private function reject(string $message): Response
    {
        return ApiResponse::error($message, 401, [
            'api_signature' => [$message],
        ]);
    }
}
