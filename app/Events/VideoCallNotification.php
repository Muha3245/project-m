<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class VideoCallNotification implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $fromUser;
    public $toId;
    public $toName;

    public function __construct($toId, $fromUser, $toName)
    {
        $this->toId = $toId;
        $this->fromUser = $fromUser;
        $this->toName = $toName;
    }

    public function broadcastOn()
    {
        return new Channel("user.{$this->toId}");
    }

    public function broadcastAs()
    {
        return 'video-call';
    }
}
