<?php

namespace App\Http\Controllers;

use App\Models\WhatsappConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class WhatsappConfigurationController extends Controller
{
    public function index(): JsonResponse
    {
        $configurations = Auth::user()->whatsappConfigurations()
            ->latest()
            ->get()
            ->map(fn ($config) => [
                'id' => $config->id,
                'name' => $config->name,
                'api_url' => $config->api_url,
                'api_version' => $config->api_version,
                'phone_id' => $config->phone_id,
                'phone_number' => $config->phone_number,
                'business_id' => $config->business_id,
                'is_active' => $config->is_active,
                'is_default' => $config->is_default,
                'created_at' => $config->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $configurations]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_url' => 'string|url|max:500',
            'api_version' => 'string|max:20',
            'api_token' => 'required|string',
            'phone_id' => 'required|string|max:100',
            'phone_number' => 'required|string|max:50',
            'business_id' => 'required|string|max:100',
            'verify_token' => 'nullable|string|max:255',
            'is_default' => 'boolean',
        ]);

        $config = Auth::user()->whatsappConfigurations()->create([
            'name' => $validated['name'],
            'api_url' => $validated['api_url'] ?? 'https://graph.facebook.com/v22.0/',
            'api_version' => $validated['api_version'] ?? 'v22.0',
            'api_token' => $validated['api_token'],
            'phone_id' => $validated['phone_id'],
            'phone_number' => $validated['phone_number'],
            'business_id' => $validated['business_id'],
            'verify_token' => $validated['verify_token'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        if ($validated['is_default'] ?? false) {
            $config->setAsDefault();
        }

        return response()->json([
            'message' => 'Configuration created successfully',
            'data' => [
                'id' => $config->id,
                'name' => $config->name,
                'phone_id' => $config->phone_id,
                'phone_number' => $config->phone_number,
                'business_id' => $config->business_id,
                'is_active' => $config->is_active,
                'is_default' => $config->is_default,
            ],
        ], 201);
    }

    public function show(WhatsappConfiguration $configuration): JsonResponse
    {
        Gate::authorize('view', $configuration);

        return response()->json([
            'data' => [
                'id' => $configuration->id,
                'name' => $configuration->name,
                'api_url' => $configuration->api_url,
                'api_version' => $configuration->api_version,
                'phone_id' => $configuration->phone_id,
                'phone_number' => $configuration->phone_number,
                'business_id' => $configuration->business_id,
                'has_verify_token' => ! empty($configuration->verify_token),
                'is_active' => $configuration->is_active,
                'is_default' => $configuration->is_default,
                'created_at' => $configuration->created_at->toIso8601String(),
                'updated_at' => $configuration->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, WhatsappConfiguration $configuration): JsonResponse
    {
        Gate::authorize('update', $configuration);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'api_url' => 'string|url|max:500',
            'api_version' => 'string|max:20',
            'api_token' => 'string',
            'phone_id' => 'string|max:100',
            'phone_number' => 'string|max:50',
            'business_id' => 'string|max:100',
            'verify_token' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $configuration->update($validated);

        return response()->json([
            'message' => 'Configuration updated successfully',
            'data' => [
                'id' => $configuration->id,
                'name' => $configuration->name,
                'phone_id' => $configuration->phone_id,
                'phone_number' => $configuration->phone_number,
                'is_active' => $configuration->is_active,
                'is_default' => $configuration->is_default,
            ],
        ]);
    }

    public function destroy(WhatsappConfiguration $configuration): JsonResponse
    {
        Gate::authorize('delete', $configuration);

        $configuration->delete();

        return response()->json(['message' => 'Configuration deleted successfully']);
    }

    public function setDefault(WhatsappConfiguration $configuration): JsonResponse
    {
        Gate::authorize('update', $configuration);

        $configuration->setAsDefault();

        return response()->json([
            'message' => 'Configuration set as default successfully',
            'data' => [
                'id' => $configuration->id,
                'name' => $configuration->name,
                'is_default' => true,
            ],
        ]);
    }

    public function testConnection(WhatsappConfiguration $configuration): JsonResponse
    {
        Gate::authorize('view', $configuration);

        $service = new \App\Services\WhatsAppService($configuration->toServiceConfig());
        $result = $service->getAvaliableTemplate();

        if ($result['success']) {
            return response()->json([
                'message' => 'Connection successful',
                'data' => [
                    'templates_count' => count($result['templates']),
                ],
            ]);
        }

        return response()->json([
            'message' => 'Connection failed',
            'error' => $result['error'] ?? 'Unknown error',
        ], 400);
    }
}
