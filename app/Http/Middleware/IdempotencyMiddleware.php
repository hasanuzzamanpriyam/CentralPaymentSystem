<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (! $idempotencyKey) {
            return response()->json([
                'error' => 'Idempotency-Key header is required'
            ], 400);
        }

        // Include the authenticated user ID in the cache key to prevent cross-merchant key collisions
        $userId = $request->user() ? $request->user()->id : 'guest';
        $cacheKey = 'idempotency:' . $userId . ':' . $idempotencyKey;

        // If the response is already cached, return it
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            return response($cachedResponse['content'], $cachedResponse['status'], $cachedResponse['headers']);
        }

        // Process the request
        $response = $next($request);

        // Only cache successful responses (2xx) or specific responses you want to be idempotent
        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
            ], now()->addHours(24));
        }

        return $response;
    }
}
