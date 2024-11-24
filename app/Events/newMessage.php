<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;   

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('chat.'.$this->message->sender_id.'.'.$this->message->receiver_id);
    }

    public function broadcastAs()
    {
        return 'new-message'; // Nama event yang diterima oleh Pusher
    }
}
