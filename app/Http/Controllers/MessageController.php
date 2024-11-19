<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // Ambil daftar chat/percakapan
    public function getConversations()
    {
        $currentUserId = Auth::id();

        $conversations = Message::where('sender_id', $currentUserId)
            ->orWhere('receiver_id', $currentUserId)
            ->select('sender_id', 'receiver_id')
            ->with(['sender:id,name', 'receiver:id,name'])
            ->orderBy('created_at', 'DESC')
            ->get()
            ->map(function ($message) use ($currentUserId) {
                $otherUserId = $message->sender_id == $currentUserId 
                    ? $message->receiver_id 
                    : $message->sender_id;

                $lastMessage = Message::where(function($query) use ($currentUserId, $otherUserId) {
                    $query->where('sender_id', $currentUserId)
                          ->where('receiver_id', $otherUserId);
                })->orWhere(function($query) use ($currentUserId, $otherUserId) {
                    $query->where('sender_id', $otherUserId)
                          ->where('receiver_id', $currentUserId);
                })
                ->latest()
                ->first();

                $unreadCount = Message::where('sender_id', $otherUserId)
                    ->where('receiver_id', $currentUserId)
                    ->where('is_read', false)
                    ->count();

                $otherUser = User::select('id', 'name')
                    ->find($otherUserId);

                return [
                    'user' => $otherUser,
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount,
                    'updated_at' => $lastMessage->created_at
                ];
            })
            ->unique('user.id')
            ->values();

        return response()->json([
            'conversations' => $conversations
        ]);
    }

    // Ambil pesan dari specific chat
    public function getMessages($userId)
    {
        $currentUserId = Auth::id();
        
        $messages = Message::where(function($query) use ($userId, $currentUserId) {
            $query->where('sender_id', $currentUserId)
                  ->where('receiver_id', $userId);
        })->orWhere(function($query) use ($userId, $currentUserId) {
            $query->where('sender_id', $userId)
                  ->where('receiver_id', $currentUserId);
        })
        ->with(['sender:id,name', 'receiver:id,name'])
        ->orderBy('created_at', 'ASC')
        ->get();

        // Mark messages as read
        Message::where('sender_id', $userId)
              ->where('receiver_id', $currentUserId)
              ->where('is_read', false)
              ->update(['is_read' => true]);

        return response()->json([
            'messages' => $messages
        ]);
    }

    // Kirim pesan
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string'
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false
        ]);

        $message->load(['sender:id,name', 'receiver:id,name']);

        return response()->json([
            'message' => $message
        ], 201);
    }

    // Tandai pesan sudah dibaca
    public function markAsRead($messageId)
    {
        $message = Message::findOrFail($messageId);
        
        if ($message->receiver_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $message->update(['is_read' => true]);

        return response()->json([
            'message' => 'Message marked as read'
        ]);
    }
}

