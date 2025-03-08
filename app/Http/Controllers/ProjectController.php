<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\Request;
use App\Models\Subtask;
use App\Models\User;
use App\Models\Task;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;

class ProjectController extends Controller
{
    /**
     * The store function in PHP validates and stores workspace data, then returns to the previous page
     * with a success message.
     * 
     * @param Request request The  parameter is an instance of the Request class, which
     * represents an HTTP request made to the server. It contains all the data and information sent by
     * the client in the request.
     * 
     * @return The code is returning a redirect back to the previous page with a success message.
     */
    public function store(Request $request)
    {
        $validData = $request->validateWithBag('project', [
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'name' => ['required', 'string', 'max:60'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        $project = Project::create($validData);

        // Make the user who created the Project a Project Admin
        $project->users()->attach(Auth::id(), ['role' => Project::ROLE_ADMIN]);

        return back()->withSuccess('Project has been created');
    }

    /**
     * The function updates a workspace's name and icon based on the request data and returns a success
     * message.
     * 
     * @param Request request The `` parameter is an instance of the `Illuminate\Http\Request`
     * class. It represents the HTTP request made to the server and contains all the data sent with the
     * request, such as form inputs, query parameters, and uploaded files.
     * @param id The "id" parameter is the identifier of the workspace that needs to be updated. It is
     * used to find the specific workspace record in the database.
     * 
     * @return a redirect back to the previous page with a success message.
     */
    public function update(Request $request, $id)
    {
        $request->validateWithBag('project', [
            'name' => ['required', 'string', 'max:60'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        $project = Project::findOrFail($id);
        $project->name = $request->name;
        $project->description = $request->description;
        $project->save();

        return back()->withSuccess('Project has been updated');
    }

    /**
     * The show function retrieves a workspace by its ID and returns a view with the workspace details.
     * 
     * @param id The "id" parameter is used to identify the specific workspace that needs to be shown.
     * It is typically an integer value that corresponds to the unique identifier of the workspace in
     * the database.
     * 
     * @return a view called 'workspaceDetails' and passing the 'workspace' variable to the view.
     */
    public function show($id)
    {
        $subtasks=Subtask::where('task_id',$id)->with('users')->get();
        $users = User::all();
        $task = Task::where('board_id', $id)->get();
        $project = Project::with('users', 'invites', 'boards', 'boards.tasks')->findOrFail($id);
        return view('projectDetails', compact('project','subtasks','task','users'));
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return redirect()->route('dashboard')->withSuccess('Project has been deleted');
    }

    /**
     * The starToggle function toggles the star status of a workspace and returns a success message
     * indicating whether the workspace has been marked or unmarked.
     * 
     * @param id The "id" parameter is the identifier of the workspace that needs to be toggled.
     * 
     * @return a redirect back to the previous page with a success message. The success message
     * indicates whether the workspace has been marked or unmarked as a star.
     */
    public function starToggle(int $id)
    {
        $project = Auth::user()->projects()->findOrFail($id);
        $project->pivot->star = !$project->pivot->star;
        $project->pivot->save();

        return back()->withSuccess('Project has been ' . ($project->pivot->star ? 'marked' : 'unmarked'));
    }

    public function markedProjects(int $userId = null)
    {
        $query = DB::table('project_users')->select('projects.id AS id', 'projects.name AS name')->join('projects', 'project_users.project_id', '=', 'projects.id')->where('star', true);
        if (!is_null($userId)) {
            $query->where('user_id', $userId);
        }

        $projects = $query->get();
        return $projects;
    }

    public function revokeAccess($id, $userId)
    {
        $project = Project::findOrFail($id);
        $project->users()->detach($userId);
        return back()->withSuccess('Project access has been revoked');
    }
}
