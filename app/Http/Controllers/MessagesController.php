<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use App\Models\Message;
use Illuminate\Support\Facades\Storage;

use App\Events\MessageSent;

class MessagesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $chattedUserIds = Message::where('from_id', auth()->id())
            ->orWhere('to_id', auth()->id())
            ->pluck('from_id', 'to_id')
            ->keys()
            ->merge(
                Message::where('from_id', auth()->id())
                    ->orWhere('to_id', auth()->id())
                    ->pluck('to_id', 'from_id')
                    ->keys()
            )
            ->unique();

        $users = User::whereIn('id', $chattedUserIds)
            ->where('id', '!=', auth()->id())
            ->when($search, function ($query, $search) {
                $query->where('name', 'LIKE', "%{$search}%");
            })
            ->get();

            $groups = Group::whereHas('users', function ($query) {
                $query->where('user_id', auth()->id());
            })->get();
        $activeChat = null;
        $messages = collect();

        if ($request->has('to_id')) {
            $activeChat = User::find($request->to_id);

            if ($activeChat) {
                Message::where('to_id', auth()->id())
                    ->where('from_id', $request->to_id)
                    ->whereNull('is_read')
                    ->update(['is_read' => now()]);

                $messages = Message::where(function ($query) use ($request) {
                    $query->where('from_id', auth()->id())
                        ->where('to_id', $request->to_id);
                })
                    ->orWhere(function ($query) use ($request) {
                        $query->where('from_id', $request->to_id)
                            ->where('to_id', auth()->id());
                    })
                    ->orderBy('created_at')
                    ->get();
            }
        } elseif ($request->has('group_id')) {
            $activeChat = Group::find($request->group_id);

            if ($activeChat) {
                $messages = Message::where('group_id', $request->group_id)
                    ->orderBy('created_at')
                    ->get();
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'users' => $users,
                'groups' => $groups,
                'activeChat' => $activeChat,
                'messages' => $messages,
            ]);
        }

        return view('chat', compact('users', 'groups', 'activeChat', 'messages'));
    }

    public function search(Request $request)
    {
        $search = $request->get('search');
        $data = User::where('name', 'LIKE', "%{$search}%")
            ->orWhere('email', 'LIKE', "%{$search}%")
            ->get();

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        $output = '';
        if (count($data) > 0) {
            $output = '
            <table class="table">
                <thead>
                    <tr><th>name</th></tr>
                </thead>
                <tbody>';
            foreach ($data as $row) {
                $output .= '
                <tr>
                    <th scope="row">
                        <a href="' . route('one-to-one.index', ['to_id' => $row->id]) . '" class="d-flex align-items-center">
                            <img src="' . ($row->getFirstMediaUrl('default', 'preview') ?: 'https://ui-avatars.com/api/?name=' . urlencode($row->name)) . '" 
                                 alt="user-avatar" class="rounded-circle me-2" height="30" width="30">
                            <span class="user-name">' . $row->name . '</span>
                        </a>
                    </th>
                </tr>';
            }
            $output .= '</tbody></table>';
        } else {
            $output .= 'No results';
        }

        return $output;
    }

    public function send(Request $request)
    {
        $request->validate([
            'to_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp3,wav,webm|max:10240',
            'voice' => 'nullable|file|mimes:webm,mp3,wav|max:10240',
        ]);

        $message = new Message();
        $message->from_id = auth()->id();
        $message->to_id = $request->to_id;
        $message->message = $request->message;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('chat_files', 'public');
            $message->file_path = $filePath;
        }

        if ($request->hasFile('voice')) {
            $voicePath = $request->file('voice')->store('chat_voice', 'public');
            $message->voice_message_path = $voicePath;
        }

        $message->save();

        broadcast(new \App\Events\MessageSent($message))->toOthers();
        return back()->with('success','message send successfully');

        // return response()->json([
        //     'success' => true,
        //     'message' => [
        //         'message' => $message->message,
        //         'file_path' => $message->file_path,
        //         'file_url' => $message->file_path ? Storage::url($message->file_path) : null,
        //         'voice_message_path' => $message->voice_message_path,
        //         'voice_url' => $message->voice_message_path ? Storage::url($message->voice_message_path) : null,
        //         'created_at' => $message->created_at->format('d M Y, h:i A'),
        //     ]
        // ]);
    }

    public function markAsRead(Request $request)
    {
        if ($request->has('to_id')) {
            Message::where('to_id', auth()->id())
                ->where('from_id', $request->to_id)
                ->whereNull('is_read')
                ->update(['is_read' => now()]);
        } elseif ($request->has('group_id')) {
            Message::where('group_id', $request->group_id)
                ->whereNull('is_read')
                ->update(['is_read' => now()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Messages marked as read.');
    }

    public function createGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $group = Group::create([
            'name' => $validated['name'],
            'created_by' => auth()->id(),
        ]);

        $group->users()->attach($validated['user_ids']);

        if ($request->wantsJson()) {
            return response()->json(['group' => $group, 'success' => true]);
        }

        return redirect()->route('chat.index')->with('success', 'Group created successfully!');
    }

    public function delete($id)
    {
        $message = Message::findOrFail($id);

        if ($message->from_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $message->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Message deleted successfully.');
    }

    public function addUsers(Request $request, Group $group)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $group->users()->attach($request->user_ids);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Users added to the group successfully.');
    }

    public function removeUser(Group $group, User $user)
    {
        if (!$group->users()->where('users.id', $user->id)->exists()) {
            abort(404, 'User not found in this group.');
        }

        $group->users()->detach($user->id);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'User removed from the group.');
    }
}
