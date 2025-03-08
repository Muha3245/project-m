<?php

namespace App\Http\Controllers;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use App\Models\Reply;
use App\Models\TaskComment;
use App\Models\TaskHistory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use Illuminate\Contracts\Validation\Rule;









class SubTaskController extends Controller
{
    public function store(Request $request)
    {
        // dd($request);
        // $validData = $request->validateWithBag('subtask', [
        //     'task_id' => ['required', 'integer', 'exists:tasks,id'],
        //     'title' => ['required', 'string', 'max:400'],
        //     'description' => ['nullable', 'present'],
        //     'status' => ['required', 'string', 'max: 191'],
        //     'priority' => ['required', 'string', 'max: 191'],
        //     'type' => ['required', 'string', 'max: 191'],
        //     'due_date' => ['required', 'string', 'max: 191'],
        //     'assigned_to' => ['required', 'integer', 'min:0', 'exists:users,id'],
        // ]);

        // dd($validData);
        $validData=$request->all();

        $subtask = Subtask::create($validData);

        // Assign User
        $subtask->users()->attach($validData['assigned_to'], ['role' => Subtask::ROLE_MEMBER]);

        // Add History
        // $subtask->history()->create(['user_id' => Auth::id(), 'type' => SubtaskHistory::TYPE_TASK_CREATED, 'data' => json_encode(['title' => $task->title])]);

        return back()->withSuccess('subTask has been added');
    }

    // ----------------- Copied from taskController -------------------


    public function update(Request $request, $id)
    {
        // Validate the request data
        $validData = $request->validateWithBag('subtask', [
            'title' => ['required', 'string', 'max:400'],
            'description' => ['nullable', 'present'],
            'status' => ['required', 'string', 'max:191'],
            'priority' => ['required', 'string', 'max:191'],
            'type' => ['required', 'string', 'max:191'],
            'due_date' => ['required', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);
    
        try {
            // Find the subtask or throw a 404 error
            $subtask = Subtask::findOrFail($id);
    
            // Update the subtask
            $subtask->update([
                'title' => $validData['title'],
                'description' => $validData['description'],
                'status' => $validData['status'],
                'priority' => $validData['priority'],
                'type' => $validData['type'],
                'due_date' => $validData['due_date'],
            ]);
    
            // Assign or unassign users
            if (!empty($validData['assigned_to'])) {
                // Assign a user and set role
                $subtask->users()->sync([
                    $validData['assigned_to'] => ['role' => Subtask::ROLE_MEMBER]
                ]);
            } else {
                // If no user is assigned, detach all existing users
                $subtask->users()->detach();
            }
    
            return back()->withSuccess('SubTask has been updated successfully.');
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->withErrors(['subtask' => 'The specified subtask was not found.']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'An unexpected error occurred: ' . $e->getMessage()]);
        }
    }
    


    
    public function show(Request $request)
{
    // Validate the 'id' from the query string
    $request->validate([
        'id' => ['required', 'integer', 'min:1'],
    ]);

    $id = $request->query('id'); // Get 'id' from the query string

    // Retrieve the subtask by ID, including its relationships
    $subtask = Subtask::with('users', 'board.tasks', 'board.tasks.users', 'history', 'history.user')
                      ->findOrFail($id);

    // Retrieve the associated project from the subtask's board
    $project = $subtask->board->project;

    // Retrieve comments specific to this subtask
    $comments = TaskComment::with('user')
                           ->where('task_id', $subtask->task_id)
                           ->latest()
                           ->get();

    // Pass only subtask-related data to the view
    return view('subtaskDetails', compact('subtask', 'project', 'comments'));
}



    public function storeComment(Request $request)
{
    // Validate the input
    $validated = $request->validate([
        'task_id' => ['required', 'integer', 'exists:tasks,id'],
        'comment' => ['required', 'string', 'max:5000'],
        'attachments' => ['nullable', 'array'],
        'attachments.*' => [
            'file',
            'mimes:jpg,jpeg,png,pdf',
            'max:10240', // 10 MB
        ],
    ]);

    // Check if the user pressed the "Show Users" button
    if ($request->has('show_users') && $request->input('show_users') == '1') {
        preg_match_all('/@(\w+)/', $validated['comment'], $matches);
        $mentions = $matches[1];

        // Fetch users whose names match the mention
        $users = DB::table('users')
            ->where(function ($query) use ($mentions) {
                foreach ($mentions as $mention) {
                    $query->orWhere('name', 'like', "%{$mention}%");
                }
            })
            ->limit(10)
            ->get(['id', 'name']);

        // Pass the suggestions back to the form
        return redirect()->back()->with('user_suggestions', $users);
    }

    // Otherwise, store the comment
    $subtask = Subtask::findOrFail($validated['task_id']);
    $comment = $subtask->comments()->create([
        'user_id' => Auth::id(),
        'comment' => $validated['comment'],
    ]);

    // Handle attachments
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $attachment) {
            $comment->addMedia($attachment)->toMediaCollection('attachments');
        }
    }

    return back()->withSuccess('Comment has been added.');
}



    public function storeReply(Request $request)
    {
        $validData = $request->validate([
            'task_comment_id' => ['required', 'integer', 'exists:task_comments,id'], // Updated field name
            'reply' => ['required', 'string', 'max:5000'],
        ]);

        $comment = TaskComment::findOrFail($validData['task_comment_id']); // Updated field name

        // Save the reply
        $reply = $comment->replies()->create([
            'user_id' => Auth::id(),
            'comment' => $validData['reply'],
        ]);


        $subtask = $comment->subtask; // Assuming Comment has a relationship with Task
    $subtask->history()->create([
        'user_id' => Auth::id(),
        'type' => TaskHistory::TYPE_REPLIED, // Assuming TYPE_REPLIED exists in your TaskHistory model
        'data' => json_encode(['reply' => $reply->comment]),
    ]);

        return back()->withSuccess('Reply has been added.');
    }



    public function destroyComment($id)
    {
        $comment = TaskComment::where('user_id', Auth::id())->findOrFail($id);

        // Add History
        $comment->subtask->history()->create(['user_id' => Auth::id(), 'type' => TaskHistory::TYPE_DELETED_COMMENTED]);

        // Delete
        $comment->delete();
        return back()->withSuccess('Comment has been deleted');
    }
    public function destroyReply($id)
    {
        // Retrieve the reply that belongs to the authenticated user
        $reply = Reply::where('user_id', Auth::id())->findOrFail($id);

        // Access the comment associated with the reply
        // $subtask = $reply->subtask;
        // // Add History
        // $subtask->history()->create([
        //     'user_id' => Auth::id(),
        //     'type' => TaskHistory::TYPE_DELETED_REPLIED, // Ensure this constant is defined
        //     'data' => json_encode(['reply' => $reply->comment]), // Log the reply content
        // ]);

        // Delete the reply
        $reply->delete();

        return back()->withSuccess('Reply has been deleted.');
    }




    public function destroy($id)
    {
        $subtask = Subtask::findOrFail($id);
        $subtask->delete();

        return redirect()->back()->withSuccess('SubTask has been deleted');
    }
    public function details($id)
    {
        $subtask = subtask::findOrFail($id); // Fetch subtask details by ID
        $project = Project::findOrFail($id); // Fetch task details by ID
        $comments = TaskComment::where('user_id', Auth::id())->findOrFail($id);

        return view('taskDetails', compact('task','project','comments'));
    }

    public function getSuggestions(Request $request)
{
    $query = $request->input('query', '');

    // Fetch users whose names match the query
    $users = DB::table('users')
        ->where('name', 'like', "%{$query}%")
        // ->limit(10)
        ->get(['id', 'name']); // Only return necessary fields

    return response()->json($users);
}






}
