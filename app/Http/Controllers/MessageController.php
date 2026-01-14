<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{

    public function messagePhone($phone){
        $contact = Contact::where('user_id', Auth::id())
            ->get()
            ->first(fn ($c) => $c->full_phone === $phone);

        if ($contact) {
            $messages = Message::where('to_number', $contact->full_phone)
                ->orWhere('from_number', $contact->full_phone)
                ->latest()
                ->paginate(15);
        } else {
            $messages = Message::where('to_number', $phone)
                ->orWhere('from_number', $phone)
                ->latest()
                ->paginate(15);
        }

        return response()->json([
            'contact' => $contact,
            'messages' => $messages,
        ]);
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
