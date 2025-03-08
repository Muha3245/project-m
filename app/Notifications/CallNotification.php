<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CallNotification extends Notification
{
    use Queueable;

    private $senderId;
    private $callType;

    public function __construct($senderId, $callType)
    {
        $this->senderId = $senderId;
        $this->callType = $callType;
    }

    public function via($notifiable)
    {
        return ['database']; // Store in the database
    }

    public function toArray($notifiable)
    {
        return [
            'sender_id' => $this->senderId,
            'call_type' => $this->callType,
            'message' => "You have an incoming " . ucfirst($this->callType) . " call from User ID: " . $this->senderId,
        ];
    }
}
