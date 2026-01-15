<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\GroupMessage;
use App\Models\WhatsappGroup;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsappGroupController extends Controller
{
    public function __construct(protected WhatsAppService $whatsAppService) {}

    public function index()
    {
        $groups = WhatsappGroup::query()
            ->withCount('contacts as member_count')
            ->with('lastMessage')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'contact_ids' => 'sometimes|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $group = DB::transaction(function () use ($validated) {
            $group = WhatsappGroup::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            if (! empty($validated['contact_ids'])) {
                $group->contacts()->attach($validated['contact_ids']);
            }

            return $group;
        });

        $group->loadCount('contacts as member_count');
        $group->load('contacts');

        return response()->json($group, 201);
    }

    public function show(WhatsappGroup $group)
    {
        $group->load('contacts');
        $group->loadCount('contacts as member_count');

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
            'description' => $group->description,
            'member_count' => $group->member_count,
            'members' => $group->contacts->map(fn ($contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->full_phone,
                'is_admin' => $contact->pivot->is_admin,
            ]),
            'created_at' => $group->created_at,
            'updated_at' => $group->updated_at,
        ]);
    }

    public function update(Request $request, WhatsappGroup $group)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $group->update($validated);

        return response()->json($group);
    }

    public function destroy(WhatsappGroup $group)
    {
        $group->delete();

        return response()->json(['message' => 'Group deleted']);
    }

    public function addContacts(Request $request, WhatsappGroup $group)
    {
        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $group->contacts()->syncWithoutDetaching($validated['contact_ids']);

        $group->load('contacts');
        $group->loadCount('contacts as member_count');

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
            'description' => $group->description,
            'member_count' => $group->member_count,
            'members' => $group->contacts->map(fn ($contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->full_phone,
                'is_admin' => $contact->pivot->is_admin,
            ]),
        ]);
    }

    public function removeContact(WhatsappGroup $group, Contact $contact)
    {
        $group->contacts()->detach($contact->id);

        $group->load('contacts');
        $group->loadCount('contacts as member_count');

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
            'description' => $group->description,
            'member_count' => $group->member_count,
            'members' => $group->contacts->map(fn ($contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->full_phone,
                'is_admin' => $contact->pivot->is_admin,
            ]),
        ]);
    }

    public function messages(WhatsappGroup $group)
    {
        $messages = $group->messages()
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json($messages);
    }

    public function sendMessage(Request $request, WhatsappGroup $group)
    {
        $validated = $request->validate([
            'type' => 'required|in:text,template',
            'message' => 'required_if:type,text|string|nullable',
            'template_name' => 'required_if:type,template|string|nullable',
            'language' => 'sometimes|string',
            'parameters' => 'sometimes|array|nullable',
        ]);

        $contacts = $group->contacts;

        if ($contacts->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'Le groupe n\'a pas de membres',
            ], 422);
        }

        $deliveryStatus = [];
        $deliveredCount = 0;
        $failedCount = 0;

        foreach ($contacts as $contact) {
            $phone = $contact->full_phone;

            if (! $phone) {
                $deliveryStatus[$contact->id] = 'failed';
                $failedCount++;

                continue;
            }

            if ($validated['type'] === 'text') {
                $result = $this->whatsAppService->sendTextMessage(
                    $phone,
                    $validated['message']
                );
            } else {
                $components = [];
                if (! empty($validated['parameters'])) {
                    $components[] = [
                        'type' => 'body',
                        'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => $p], $validated['parameters']),
                    ];
                }

                $result = $this->whatsAppService->sendTemplate(
                    $phone,
                    $validated['template_name'],
                    $validated['language'] ?? 'fr',
                    $components
                );
            }

            $deliveryStatus[$contact->id] = $result['success'] ? 'sent' : 'failed';
            $result['success'] ? $deliveredCount++ : $failedCount++;

            Log::info('Group message sent to contact', [
                'group_id' => $group->id,
                'contact_id' => $contact->id,
                'success' => $result['success'],
            ]);
        }

        $groupMessage = GroupMessage::create([
            'whatsapp_group_id' => $group->id,
            'type' => $validated['type'],
            'body' => $validated['message'] ?? null,
            'template_name' => $validated['template_name'] ?? null,
            'template_parameters' => $validated['parameters'] ?? null,
            'delivery_status' => $deliveryStatus,
            'total_recipients' => $contacts->count(),
            'delivered_count' => $deliveredCount,
            'failed_count' => $failedCount,
        ]);

        $group->touch();

        return response()->json([
            'success' => true,
            'message' => $groupMessage,
            'summary' => [
                'total' => $contacts->count(),
                'delivered' => $deliveredCount,
                'failed' => $failedCount,
            ],
        ]);
    }
}
