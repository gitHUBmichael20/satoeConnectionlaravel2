<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\NewMessage;

class messageController extends Controller
{
    // Ambil daftar chat/percakapan
    public function getConversations()
    {
        $currentUserId = Auth::id();

        // Ambil percakapan yang dimiliki oleh pengguna
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

                // Ambil pesan terakhir dari percakapan
                $lastMessage = Message::where(function($query) use ($currentUserId, $otherUserId) {
                    $query->where('sender_id', $currentUserId)
                          ->where('receiver_id', $otherUserId);
                })->orWhere(function($query) use ($currentUserId, $otherUserId) {
                    $query->where('sender_id', $otherUserId)
                          ->where('receiver_id', $currentUserId);
                })
                ->latest()
                ->first();

                // Hitung jumlah pesan belum dibaca
                $unreadCount = Message::where('sender_id', $otherUserId)
                    ->where('receiver_id', $currentUserId)
                    ->where('is_read', false)
                    ->count();

                // Ambil data pengguna lain
                $otherUser = User::select('id', 'name')
                    ->find($otherUserId);

                return [
                    'user' => $otherUser,
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount,
                    'updated_at' => $lastMessage->created_at
                ];
            })
            ->groupBy(function($conversation) {
                return $conversation['user']->id; // Mengelompokkan percakapan berdasarkan ID user
            })
            ->values();

        return response()->json([
            'conversations' => $conversations
        ]);
    }

    // Ambil pesan dari percakapan spesifik
    public function getMessages($userId)
    {
        $currentUserId = Auth::id();
        
        // Ambil pesan dengan paginasi (misal 20 pesan per halaman)
        $messages = Message::where(function($query) use ($userId, $currentUserId) {
            $query->where('sender_id', $currentUserId)
                  ->where('receiver_id', $userId);
        })->orWhere(function($query) use ($userId, $currentUserId) {
            $query->where('sender_id', $userId)
                  ->where('receiver_id', $currentUserId);
        })
        ->with(['sender:id,name', 'receiver:id,name'])
        ->orderBy('created_at', 'ASC')
        ->paginate(20); // Ganti jumlah halaman sesuai kebutuhan

        // Tandai pesan sebagai sudah dibaca
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

        // Membuat pesan baru
        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false
        ]);

        // Memicu event broadcast untuk pesan baru
        broadcast(new NewMessage($message));

        // Memuat data pengirim dan penerima untuk respons
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

        // Update status pesan menjadi dibaca
        $message->update(['is_read' => true]);

        return response()->json([
            'message' => 'Message marked as read'
        ]);
    }

    // Menambahkan metode untuk verifikasi ID pengguna
    public function verifyUser($id)
    {
        $user = User::find($id);
    
        if ($user) {
            return response()->json([
                'message' => 'User found',
                'id' => $user->id,
                'username' => $user->name
            ], 200);
        } else {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
    }
}
