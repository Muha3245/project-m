<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\GroupMessage;
use App\Models\User;
use App\Events\GroupMessageSent;

class GroupChatController extends Controller
{
    public function index(Request $request)
{
    $messages = collect();
    $group = null;

    if ($request->has('group_id')) {
        $group = Group::whereHas('users', function ($query) {
            $query->where('user_id', auth()->id());
        })->where('id', $request->group_id)
          ->with(['users', 'messages'])
          ->first();

        if (!$group) {
            return response()->json(['error' => 'You are no longer a member of this group.'], 403);
        }

        $messages = $group->messages()->with('user')->latest()->get();
    }

    $users = User::whereHas('groups')->where('id', '!=', auth()->id())->get();
    $groups = Group::whereHas('users', function ($query) {
        $query->where('user_id', auth()->id());
    })->with(['users', 'messages'])->get();

    return view('groupchat', compact('groups', 'users', 'messages', 'group'));
}


    public function show(Group $group)
    {
        $groups = Group::with(['users', 'messages'])->get();
        $users = User::where('id', '!=', auth()->id())->get();
        $messages = $group->messages()->with('user')->orderBy('created_at', 'asc')->get();

        // Mark messages as read
        $group->messages()
            ->whereNull('is_read')
            ->where('user_id', '!=', auth()->id())
            ->update(['is_read' => now()]);

        return view('groupchat', compact('group', 'groups', 'users', 'messages'));
    }

    public function createGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $group = Group::create([
            'name' => $validated['name'],
            'created_by' => auth()->id(),
        ]);

        // Add creator to the group
        $userIds = array_unique(array_merge($validated['user_ids'], [auth()->id()]));
        $group->users()->attach($userIds);

        return redirect()->route('groups.show', $group)
                        ->with('success', 'Group created successfully!');
    }

    public function sendMessage(Request $request, Group $group)
    {
        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp3,wav,webm|max:10240',
            'voice' => 'nullable|file|mimes:webm,mp3,wav|max:10240',
        ]);
    
        $filePath = null;
        $voicePath = null;
    
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('chat_files', 'public');
        }
    
        if ($request->hasFile('voice')) {
            $voicePath = $request->file('voice')->store('chat_voice', 'public');
        }
    
        // Create the message after processing the file paths
        $message = $group->messages()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'file_path' => $filePath,
            'voice_message_path' => $voicePath,
        ]);
    
        // Trigger the Pusher event
        broadcast(new GroupMessageSent($message))->toOthers();
    
        return back()->with('success','message send successfully');
    }
    

    public function deleteGroupMessage(Group $group, GroupMessage $message)
    {
        if ($message->user_id !== auth()->id()) {
            return back()->with('error', 'Unauthorized action.');
        }

        $message->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Message deleted successfully.');
    }

    public function addUsersToGroup(Request $request, Group $group)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $group->users()->syncWithoutDetaching($validated['user_ids']);

        return back()->with('success', 'Users added successfully.');
    }

    public function removeUser(Group $group, User $user)
    {
        if ($group->created_by === $user->id) {
            return back()->with('error', 'Cannot remove the group creator.');
        }

        $group->users()->detach($user->id);

        return back()->with('success', 'User removed successfully.');
    }
    public function removeGroup(Group $group)
    {
        if ($group->created_by !== auth()->id()) {
            return back()->with('error', 'You are not authorized to delete this group.');
        }

        $group->messages()->delete();

        $group->users()->detach();

        $group->delete();

        return redirect()->back()->with('success', 'Group deleted successfully.');
    }
}
