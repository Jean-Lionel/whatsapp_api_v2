<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Message;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function messagePhone($phone)
    {
        $contact = Contact::where('user_id', Auth::id())
            ->get()
            ->first(fn ($c) => $c->full_phone === $phone);

        $searchPhone = $contact ? $contact->full_phone : $phone;

        // Marquer tous les messages entrants comme lus
        $unreadMessages = Message::where('from_number', $searchPhone)
            ->where('direction', 'in')
            ->where('status', '!=', 'read')
            ->get();

        foreach ($unreadMessages as $message) {
            // Marquer comme lu via l'API WhatsApp
            if ($message->wa_message_id) {
                $this->whatsAppService->markAsRead($message->wa_message_id);
                
            }

            // Mettre à jour en base de données
            $message->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
        }

        // Récupérer tous les messages de cette conversation
        $messages = Message::where(function ($query) use ($searchPhone) {
            $query->where('from_number', $searchPhone)
                ->orWhere('to_number', $searchPhone);
        })
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        // Formater les messages pour le frontend
        $messages->getCollection()->transform(function ($message) {
            return [
                'id' => $message->id,
                'text' => $message->body,
                'time' => $message->created_at->format('H:i'),
                'date' => $message->created_at->format('Y-m-d'),
                'isMine' => $message->direction === 'out',
                'status' => $message->status,
                'type' => $message->type,
            ];
        });

        return response()->json([
            'contact' => $contact,
            'phone' => $searchPhone,
            'messages' => $messages,
        ]);
    }

    /**
     * Envoyer un message WhatsApp
     */
    public function sendWhatsApp(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|string',
            'type' => 'required|in:text,template,image,document',
            'message' => 'nullable|string',
            'template_name' => 'required_if:type,template|string|nullable',
            'language' => 'required_if:type,template|string|nullable',
            'parameters' => 'sometimes|array|nullable',
            'filename' => 'sometimes|string|nullable',
            'caption' => 'sometimes|string|nullable',
            'file' => 'sometimes|file|max:65000', // ~64MB
        ]);

        try {
            $type = $validated['type'];
            $to = $validated['to'];

            if ($request->hasFile('file')) {
                // Upload media to WhatsApp
                $mediaId = $this->whatsAppService->uploadMedia($request->file('file'));

                if (!$mediaId) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to upload media to WhatsApp',
                    ], 500);
                }

                // If file is present, 'message' field is treated as caption
                $caption = $validated['caption'] ?? $validated['message'] ?? null;

                $result = match ($type) {
                    'image' => $this->whatsAppService->sendImage(
                        $to,
                        $mediaId,
                        $caption,
                        false // isUrl = false
                    ),
                    'document' => $this->whatsAppService->sendDocument(
                        $to,
                        $mediaId,
                        $validated['filename'] ?? $request->file('file')->getClientOriginalName(),
                        $caption,
                        false // isUrl = false
                    ),
                    default => throw new \Exception('File upload is not supported for this message type'),
                };
            } else {
                // Handle text or URL-based media
                $result = match ($type) {
                    'text' => $this->whatsAppService->sendTextMessage(
                        $to,
                        $validated['message'] ?? ''
                    ),
                    'template' => $this->sendTemplateMessage(
                        $to,
                        $validated['template_name'],
                        $validated['language'],
                        $validated['parameters'] ?? []
                    ),
                    'image' => $this->whatsAppService->sendImage(
                        $to,
                        $validated['message'], // URL
                        $validated['caption'] ?? null
                    ),
                    'document' => $this->whatsAppService->sendDocument(
                        $to,
                        $validated['message'], // URL
                        $validated['filename'] ?? null,
                        $validated['caption'] ?? null
                    ),
                    default => throw new \Exception('Invalid message type'),
                };
            }

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => [
                        'id' => $result['message']->id,
                        'text' => $result['message']->body,
                        'time' => $result['message']->created_at->format('H:i'),
                        'date' => $result['message']->created_at->format('Y-m-d'),
                        'isMine' => true,
                        'status' => $result['message']->status,
                        'type' => $result['message']->type,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Envoyer un message template avec paramètres
     */
    private function sendTemplateMessage(string $to, string $templateName, string $language, array $parameters = []): array
    {
        // Construire les composants pour le template
        $components = [];

        if (!empty($parameters)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn($param) => ['type' => 'text', 'text' => $param], $parameters),
            ];
        }

        // Appeler la méthode sendTemplate du service
        return $this->whatsAppService->sendTemplate($to, $templateName, $language, $components);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $messages = Message::latest()->paginate(15);

        return response()->json($messages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'wa_message_id' => 'nullable|string|unique:messages,wa_message_id',
            'conversation_id' => 'nullable|string',
            'direction' => 'required|in:in,out',
            'from_number' => 'required|string',
            'to_number' => 'required|string',
            'type' => 'required|in:text,image,video,audio,document,location,template,interactive',
            'body' => 'nullable|string',
            'payload' => 'nullable|array',
            'status' => 'in:sent,delivered,read,failed',
            'sent_at' => 'nullable|date',
            'read_at' => 'nullable|date',
        ]);

        $message = Message::create($validated);

        return response()->json($message, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Message $message)
    {
        return response()->json($message);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Message $message)
    {
        $validated = $request->validate([
            'wa_message_id' => 'nullable|string',
            'conversation_id' => 'nullable|string',
            'direction' => 'sometimes|in:in,out',
            'from_number' => 'sometimes|string',
            'to_number' => 'sometimes|string',
            'type' => 'sometimes|in:text,image,video,audio,document,location,template,interactive',
            'body' => 'nullable|string',
            'payload' => 'nullable|array',
            'status' => 'sometimes|in:sent,delivered,read,failed',
            'sent_at' => 'nullable|date',
            'read_at' => 'nullable|date',
        ]);

        $message->update($validated);

        return response()->json($message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Message $message)
    {
        $message->delete();

        return response()->json(['message' => 'Message deleted successfully']);
    }
}
