<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Retrieve conversations for the authenticated user
        // Eager load 'users' to get the partner's name
        // Eager load 'messages' (ordered by latest) to get the chat history/last message
        $conversations = $user->conversations()
            ->with(['users', 'messages' => function ($query) {
                $query->latest()->limit(50); // Fetch recent messages
            }])
            ->get()
            ->sortByDesc(function ($chat) {
                // Sort chats by the very last message time
                $lastMsg = $chat->messages->first(); 
                return $lastMsg ? $lastMsg->created_at : $chat->created_at;
            });

        $response = $conversations->values()->map(function ($chat) use ($user) {
            // Determine Chat Name
            if ($chat->is_group) {
                $name = $chat->name ?? 'Group Chat';
            } else {
                // For private chats, find the other user
                $otherUser = $chat->users->firstWhere('id', '!=', $user->id);
                $name = $otherUser ? $otherUser->name : 'Unknown';
            }

            // Get Last Message Details
            $lastMsg = $chat->messages->first(); // Since we queried latest()
            $lastMessageText = $lastMsg ? $lastMsg->text : '';
            $lastMessageTime = $lastMsg ? $lastMsg->created_at->format('H:i') : '';

            // Generate Avatar
            $avatar = "https://ui-avatars.com/api/?name=" . urlencode($name);

            // Format Messages (Reverse to show oldest first in the array, as usually expected by chat UIs like the example)
            $formattedMessages = $chat->messages->reverse()->values()->map(function ($msg) use ($user) {
                return [
                    "id" => $msg->id,
                    "text" => $msg->text,
                    "time" => $msg->created_at->format('H:i'),
                    "isMine" => $msg->user_id === $user->id
                ];
            });

            return [
                "id" => $chat->id,
                "name" => $name,
                "lastMessage" => $lastMessageText,
                "lastMessageTime" => $lastMessageTime,
                "unreadCount" => $chat->pivot->unread_count ?? 0,
                "avatar" => $avatar,
                "messages" => $formattedMessages
            ];
        });

        return response()->json($response);
    }
}
