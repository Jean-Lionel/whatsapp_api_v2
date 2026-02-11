<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ApiKeyController extends Controller
{
    public function index(): JsonResponse
    {
        $keys = Auth::user()->apiKeys()
            ->withCount('usageLogs')
            ->latest()
            ->get()
            ->map(fn ($key) => [
                'id' => $key->id,
                'name' => $key->name,
                'key_preview' => substr($key->key, 0, 12).'...',
                'scopes' => $key->scopes,
                'rate_limit' => $key->rate_limit,
                'is_active' => $key->is_active,
                'last_used_at' => $key->last_used_at?->toIso8601String(),
                'expires_at' => $key->expires_at?->toIso8601String(),
                'usage_count' => $key->usage_logs_count,
                'created_at' => $key->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $keys]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'array',
            'scopes.*' => 'in:read,write,send_messages',
            'rate_limit' => 'integer|min:10|max:1000',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        $key = ApiKey::generateKey();

        $apiKey = Auth::user()->apiKeys()->create([
            'name' => $validated['name'],
            'key' => $key,
            'scopes' => $validated['scopes'] ?? ['read', 'write', 'send_messages'],
            'rate_limit' => $validated['rate_limit'] ?? 100,
            'expires_at' => isset($validated['expires_in_days'])
                ? now()->addDays($validated['expires_in_days'])
                : null,
        ]);

        return response()->json([
            'message' => 'API key created successfully',
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $key,
                'scopes' => $apiKey->scopes,
                'rate_limit' => $apiKey->rate_limit,
                'expires_at' => $apiKey->expires_at?->toIso8601String(),
            ],
            'warning' => 'Save this key now! It will not be shown again.',
        ], 201);
    }

    public function show(ApiKey $apiKey): JsonResponse
    {
        Gate::authorize('view', $apiKey);

        return response()->json([
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key_preview' => substr($apiKey->key, 0, 12).'...',
                'scopes' => $apiKey->scopes,
                'rate_limit' => $apiKey->rate_limit,
                'is_active' => $apiKey->is_active,
                'last_used_at' => $apiKey->last_used_at?->toIso8601String(),
                'expires_at' => $apiKey->expires_at?->toIso8601String(),
                'created_at' => $apiKey->created_at->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, ApiKey $apiKey): JsonResponse
    {
        Gate::authorize('update', $apiKey);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'scopes' => 'array',
            'scopes.*' => 'in:read,write,send_messages',
            'rate_limit' => 'integer|min:10|max:1000',
        ]);

        $apiKey->update($validated);

        return response()->json([
            'message' => 'API key updated successfully',
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'scopes' => $apiKey->scopes,
                'rate_limit' => $apiKey->rate_limit,
            ],
        ]);
    }

    public function revoke(ApiKey $apiKey): JsonResponse
    {
        Gate::authorize('delete', $apiKey);

        $apiKey->update(['is_active' => false]);

        return response()->json(['message' => 'API key revoked successfully']);
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        Gate::authorize('delete', $apiKey);

        $apiKey->delete();

        return response()->json(['message' => 'API key deleted successfully']);
    }

    public function usage(ApiKey $apiKey, Request $request): JsonResponse
    {
        Gate::authorize('view', $apiKey);

        $days = $request->input('days', 7);

        $usage = $apiKey->usageLogs()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as requests, AVG(response_time_ms) as avg_response_time')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalRequests = $apiKey->usageLogs()
            ->where('created_at', '>=', now()->subDays($days))
            ->count();

        $successRate = $apiKey->usageLogs()
            ->where('created_at', '>=', now()->subDays($days))
            ->whereIn('status_code', [200, 201, 204])
            ->count();

        return response()->json([
            'data' => [
                'daily_usage' => $usage,
                'total_requests' => $totalRequests,
                'success_rate' => $totalRequests > 0 ? round(($successRate / $totalRequests) * 100, 2) : 0,
            ],
        ]);
    }
}
