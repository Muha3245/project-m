<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\User;
use App\Models\Group;
use AgoraIO\Token\RtcTokenBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http; // Add missing import
use App\Notifications\CallNotification;
use App\Notifications\GroupCallNotification;

class CallController extends Controller
{
    public function initiateCall(Request $request)
    {
        $receiver = User::find($request->receiver_id);
        $callType = $request->call_type;
        $senderId = auth()->id();

        if (!$receiver) {
            return response()->json(['error' => 'Receiver not found'], 404);
        }

        // Send real-time notification
        Notification::send($receiver, new CallNotification($senderId, $callType));

        return response()->json(['success' => true, 'message' => 'Call notification sent']);
    }

    public function showCallPage($receiver_id, $type)
    {
        $receiver = User::find($receiver_id);
        $sender = Auth::user();

        if (!$receiver) {
            abort(404, "Receiver not found");
        }

        $call = Call::create([
            'caller_id' => Auth::id(),
            'receiver_id' => $receiver_id,
            'call_type' => $type,
            'status' => 1
        ]);

        Notification::send($receiver, new CallNotification($sender->id, $type));

        return view('call', compact('call', 'receiver', 'type', 'sender'));
    }

    public function sendCallNotification(Request $request)
    {
        $receiver = User::find($request->receiver_id);

        if ($receiver) {
            $receiver->notify(new CallNotification(Auth::id(), $request->call_type));
            return response()->json(['success' => 'Call notification sent']);
        }

        return response()->json(['error' => 'Receiver not found'], 404);
    }

    public function fetchNotifications()
    {
        $notifications = Auth::user()->unreadNotifications;
        return response()->json($notifications);
    }

    public function markNotificationRead(Request $request)
    {
        Auth::user()->notifications()->where('id', $request->notification_id)->update(['read_at' => now()]);
        return response()->json(['success' => 'Notification marked as read']);
    }

    public function initiateGroupCall(Request $request)
    {
        $groupId = $request->group_id;
        $callType = $request->call_type;
        $senderId = auth()->id();

        $group = Group::findOrFail($groupId);
        $members = $group->users()->where('users.id', '!=', $senderId)->get();

        $call = Call::create([
            'caller_id' => $senderId,
            'group_id' => $groupId,
            'call_type' => $callType,
            'status' => 1, // Active
        ]);

        foreach ($members as $member) {
            Notification::send($member, new GroupCallNotification($senderId, $groupId, $callType));
        }

        return response()->json(['success' => true, 'message' => 'Group call notification sent']);
    }

    public function showGroupCallPage($groupId, $type)
    {
        $group = Group::findOrFail($groupId);
        $sender = Auth::user();

        $call = Call::create([
            'caller_id' => Auth::id(),
            'group_id' => $groupId,
            'call_type' => $type,
            'status' => 1, // Active
        ]);

        return view('group-call', compact('call', 'group', 'type', 'sender'));
    }

    public function generateAgoraToken(Request $request)
{
    $appId = '8e7c103dab4d44e0995a874e3f8a266a';  // Your Agora App ID
    $appCertificate = '3c9fddd9884e447ea6a81de5607f41df';  // Your Agora App Certificate
    $channelName = $request->channel_name;
    $uid = $request->uid ?? 0;  // User ID (0 for dynamic)

    $role = RtcTokenBuilder::ROLE_PUBLISHER; // Define the role

    $expireTimeInSeconds = 3600; // Token valid for 1 hour
    $currentTimestamp = now()->timestamp;
    $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

    // Generate the RTC Token
    $token = RtcTokenBuilder::buildTokenWithUid($appId, $appCertificate, $channelName, $uid, $role, $privilegeExpiredTs);

    return response()->json(['token' => $token]);
}

}
