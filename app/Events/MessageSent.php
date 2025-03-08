<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // Use a public channel
        return new Channel('chat.' . $this->message->to_id);
    }

    public function broadcastWith()
{
    return [
        'message' => [
            'id' => $this->message->id,
            'from_id' => $this->message->from_id,
            'to_id' => $this->message->to_id,
            'message' => $this->message->message,
            'file_path' => $this->message->file_path ? asset('storage/' . $this->message->file_path) : null,
            'voice_message_path' => $this->message->voice_message_path ? asset('storage/' . $this->message->voice_message_path) : null,
            'created_at' => $this->message->created_at,
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
                'avatar' => $this->message->user->getFirstMediaUrl('default', 'preview') ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->message->user->name),
            ],
        ],
    ];
}

    public function broadcastAs()
    {
        // Customize the event name
        return 'message.sent';
    }
}