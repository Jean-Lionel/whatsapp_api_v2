<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthentication
{
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $key = $request->header('X-API-Key') ?? $request->query('api_key');

        if (! $key) {
            return response()->json([
                'error' => 'API key required',
                'message' => 'Please provide your API key via X-API-Key header or api_key query parameter',
            ], 401);
        }

        $apiKey = ApiKey::where('key', $key)->first();

        if (! $apiKey || ! $apiKey->isValid()) {
            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid or expired',
            ], 401);
        }

        if ($scope && ! $apiKey->hasScope($scope)) {
            return response()->json([
                'error' => 'Insufficient permissions',
                'message' => "This API key does not have the '{$scope}' scope",
            ], 403);
        }

        $rateLimitKey = 'api_key:'.$apiKey->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $apiKey->rate_limit)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => "Too many requests. Try again in {$seconds} seconds.",
                'retry_after' => $seconds,
            ], 429)->header('Retry-After', $seconds);
        }

        RateLimiter::hit($rateLimitKey, 60);

        Auth::login($apiKey->user);

        $request->attributes->set('api_key', $apiKey);

        $startTime = microtime(true);
        $response = $next($request);
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);

        $apiKey->recordUsage(
            $request->path(),
            $request->method(),
            $response->getStatusCode(),
            $request->ip(),
            $responseTime
        );

        $response->headers->set('X-RateLimit-Limit', (string) $apiKey->rate_limit);
        $response->headers->set('X-RateLimit-Remaining', (string) RateLimiter::remaining($rateLimitKey, $apiKey->rate_limit));

        return $response;
    }
}
