<?php

namespace App\Http\Controllers;

use App\Models\ClientWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ClientWebhookController extends Controller
{
    public function index(): JsonResponse
    {
        $webhooks = Auth::user()->clientWebhooks()
            ->withCount('logs')
            ->latest()
            ->get()
            ->map(fn ($webhook) => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'is_active' => $webhook->is_active,
                'failure_count' => $webhook->failure_count,
                'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                'logs_count' => $webhook->logs_count,
                'created_at' => $webhook->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $webhooks]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'in:message.received,message.sent,message.failed,contact.created,contact.updated,*',
        ]);

        $secret = ClientWebhook::generateSecret();

        $webhook = Auth::user()->clientWebhooks()->create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'secret' => $secret,
            'events' => $validated['events'],
        ]);

        return response()->json([
            'message' => 'Webhook created successfully',
            'data' => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'secret' => $secret,
                'events' => $webhook->events,
            ],
            'warning' => 'Save this secret now! It will not be shown again.',
        ], 201);
    }

    public function show(ClientWebhook $webhook): JsonResponse
    {
        Gate::authorize('view', $webhook);

        return response()->json([
            'data' => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'is_active' => $webhook->is_active,
                'failure_count' => $webhook->failure_count,
                'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                'created_at' => $webhook->created_at->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, ClientWebhook $webhook): JsonResponse
    {
        Gate::authorize('update', $webhook);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'url' => 'url|max:500',
            'events' => 'array|min:1',
            'events.*' => 'in:message.received,message.sent,message.failed,contact.created,contact.updated,*',
            'is_active' => 'boolean',
        ]);

        $webhook->update($validated);

        if (isset($validated['is_active']) && $validated['is_active']) {
            $webhook->update(['failure_count' => 0]);
        }

        return response()->json([
            'message' => 'Webhook updated successfully',
            'data' => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'is_active' => $webhook->is_active,
            ],
        ]);
    }

    public function destroy(ClientWebhook $webhook): JsonResponse
    {
        Gate::authorize('delete', $webhook);

        $webhook->delete();

        return response()->json(['message' => 'Webhook deleted successfully']);
    }

    public function regenerateSecret(ClientWebhook $webhook): JsonResponse
    {
        Gate::authorize('update', $webhook);

        $secret = ClientWebhook::generateSecret();
        $webhook->update(['secret' => $secret]);

        return response()->json([
            'message' => 'Webhook secret regenerated successfully',
            'data' => [
                'secret' => $secret,
            ],
            'warning' => 'Save this secret now! It will not be shown again.',
        ]);
    }

    public function logs(ClientWebhook $webhook, Request $request): JsonResponse
    {
        Gate::authorize('view', $webhook);

        $logs = $webhook->logs()
            ->latest('created_at')
            ->take($request->input('limit', 50))
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'event' => $log->event,
                'status_code' => $log->status_code,
                'success' => $log->success,
                'response_time_ms' => $log->response_time_ms,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $logs]);
    }

    public function test(ClientWebhook $webhook): JsonResponse
    {
        Gate::authorize('update', $webhook);

        $log = $webhook->trigger('test', [
            'event' => 'test',
            'message' => 'This is a test webhook delivery',
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'message' => $log->success ? 'Test webhook delivered successfully' : 'Test webhook delivery failed',
            'data' => [
                'status_code' => $log->status_code,
                'response_time_ms' => $log->response_time_ms,
                'success' => $log->success,
            ],
        ], $log->success ? 200 : 502);
    }
}
