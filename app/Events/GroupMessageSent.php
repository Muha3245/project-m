<?php
namespace App\Events;

use App\Models\GroupMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $groupId;

    public function __construct(GroupMessage $message)
    {
        $this->message = $message;
        $this->groupId = $message->group_id;
    }

    public function broadcastOn()
{
    return new Channel('group.' . $this->groupId); // Ensure this matches the Blade file
}

    public function broadcastAs()
    {
        return 'GroupMessageSenting'; // Event name matches the Blade file
    }

    public function broadcastWith()
{
    return [
        'id' => $this->message->id,
        'user_id' => $this->message->user_id,
        'user' => [
            'id' => $this->message->user->id,
            'name' => $this->message->user->name,
            'avatar' => $this->message->user->getFirstMediaUrl('default', 'preview') ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->message->user->name),
        ],
        'message' => $this->message->message, // Ensure this is included
        'file_path' => $this->message->file_path ? asset('storage/' . $this->message->file_path) : null,
        'voice_message_path' => $this->message->voice_message_path ? asset('storage/' . $this->message->voice_message_path) : null,
        'created_at' => $this->message->created_at->toDateTimeString(),
    ];
}
}