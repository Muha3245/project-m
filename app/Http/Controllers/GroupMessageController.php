<?php

namespace App\Http\Controllers;

use App\Events\GroupMessageSent;
use App\Models\Group;
use App\Models\GroupMessage;
use Illuminate\Http\Request;

class GroupMessageController extends Controller
{
    public function store(Request $request, Group $group)
    {
        $validated = $request->validate([
            'message' => 'nullable|string',
            'file' => 'nullable|file|max:5120',
            'voice' => 'nullable|file|mimes:webm,mp3,wav'
        ]);

        $message = new GroupMessage();
        $message->user_id = auth()->id();
        $message->group_id = $group->id;
        $message->message = $request->message;

        if ($request->hasFile('file')) {
            $message->file_path = $request->file('file')->store('files', 'public');
        }

        if ($request->hasFile('voice')) {
            $message->voice_message_path = $request->file('voice')->store('voice-messages', 'public');
        }

        $message->save();

        // Broadcast with complete message data
        broadcast(new GroupMessageSent(
            $message->load('user'), // Eager load the user relationship
            $group->id,
            auth()->id()
        ))->toOthers();

        return response()->json([
            'status' => 'success',
            'message' => $message->load('user')
        ]);
    }
} 