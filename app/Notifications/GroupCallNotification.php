<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class GroupCallNotification extends Notification
{
    use Queueable;

    protected $senderId;
    protected $groupId;
    protected $callType;

    public function __construct($senderId, $groupId, $callType)
    {
        $this->senderId = $senderId;
        $this->groupId = $groupId;
        $this->callType = $callType;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'sender_id' => $this->senderId,
            'group_id' => $this->groupId, // Ensure group_id is included
            'call_type' => $this->callType,
            'message' => 'You have a new group call.',
        ];
    }
}