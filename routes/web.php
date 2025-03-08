<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;
use App\Services\AgoraService;


use App\Http\Controllers\BoardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GroupChatController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SubTaskController;
use App\Http\Controllers\AgoraController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('homepage');
})->name('homepage');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::view('settings/notifications', 'profile.settingNotifications')->name('settingNotifications');

    Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces');
    Route::post('/workspaces/store', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::get('/workspaces/{id}/show', [WorkspaceController::class, 'show'])->name('workspaces.show');
    Route::delete('/workspaces/{id}/delete', [WorkspaceController::class, 'destroy'])->name('workspaces.delete');
    Route::put('/workspaces/{id}/update-cover', [WorkspaceController::class, 'updateCover'])->name('workspaces.updateCover');
    Route::put('/workspaces/{id}/update', [WorkspaceController::class, 'update'])->name('workspaces.update');
    Route::post('/workspaces/{id}/star', [WorkspaceController::class, 'starToggle'])->name('workspaces.starToggle');
    Route::delete('/workspaces/{id}/revoke/{userId}/user', [WorkspaceController::class, 'revokeAccess'])->name('workspaces.revokeAccess');

    // Invitation
    Route::post('/invitation/store', [InvitationController::class, 'store'])->name('invitations.store');
    Route::post('/invitation/{id}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
    Route::delete('/invitation/{id}/delete', [InvitationController::class, 'destroy'])->name('invitations.delete');
    // End Invitation

    // Project Routes
    Route::post('/project/store', [ProjectController::class, 'store'])->name('project.store');
    Route::get('/project/{id}/details', [ProjectController::class, 'show'])->name('project.show');
    Route::delete('/project/{id}/delete', [ProjectController::class, 'destroy'])->name('project.delete');
    Route::put('/project/{id}/update', [ProjectController::class, 'update'])->name('project.update');
    Route::post('/project/{id}/star', [ProjectController::class, 'starToggle'])->name('project.starToggle');
    Route::delete('/project/{id}/revoke/{userId}/user', [ProjectController::class, 'revokeAccess'])->name('project.revokeAccess');

    // Board Routes
    Route::post('/board/store', [BoardController::class, 'store'])->name('board.store');
    Route::put('/board/{id}/update', [BoardController::class, 'update'])->name('board.update');
    Route::get('/project/{id}/boards-history', [BoardController::class, 'history'])->name('board.history');
    Route::delete('/board/{id}/delete', [BoardController::class, 'destroy'])->name('board.delete');
    // End Board Routes

    // Task Routes
 Route::post('/task/store', [TaskController::class, 'store'])->name('task.store');
 Route::get('/task/show', [TaskController::class, 'show'])->name('task.show');
 Route::put('/task/{id}/update', [TaskController::class, 'update'])->name('task.update');
 Route::delete('/task/{id}/delete', [TaskController::class, 'destroy'])->name('task.delete');
 Route::post('/task/comment/store', [TaskController::class, 'storeComment'])->name('task.storeComment');
 Route::delete('/task/comment/{id}/delete', [TaskController::class, 'destroyComment'])->name('task.deleteComment');
 Route::delete('/replies/{id}', [TaskController::class, 'destroyReply'])->name('reply.destroy');
 Route::get('/task-details/{id}', [TaskController::class, 'details'])->name('task.details');
 Route::post('/tasks/store-reply', [TaskController::class, 'storeReply'])->name('task.storeReply');
 Route::get('/user-suggestions', [TaskController::class, 'getSuggestions'])->name('user.suggestions');

    // End Task Routes

    // End Project Routes


    Route::get('/permission', [PermissionController::class, 'create'])->name('permission.create');
    Route::post('/permission/store', [PermissionController::class, 'store'])->name('permission.store');
    Route::get('/permission/index', [PermissionController::class, 'index'])->name('permission.index');
    Route::get('/permission/edit{id}', [PermissionController::class, 'edit'])->name('permission.edit');
    Route::put('/permission/{id}', [PermissionController::class, 'update'])->name('permission.update');
    Route::delete('/permission/{id}', [PermissionController::class, 'destroy'])->name('permission.destroy');

    Route::get('/role', [RoleController::class, 'create'])->name('role.create');
    Route::post('/role/store', [RoleController::class, 'store'])->name('role.store');
    Route::get('/role/index', [RoleController::class, 'index'])->name('role.index');
    Route::get('/role/edit{id}', [RoleController::class, 'edit'])->name('role.edit');
    Route::put('/role/{id}', [RoleController::class, 'update'])->name('role.update');
    Route::delete('/role/{id}', [RoleController::class, 'destroy'])->name('role.destroy');

    Route::resource('user', UserController::class);
    Route::post('/subtask/store', [SubTaskController::class, 'store'])->name('subtask.store');
    Route::get('/subtask/show', [SubTaskController::class, 'show'])->name('subtask.show');
    Route::put('/subtask/{id}/update', [SubTaskController::class, 'update'])->name('subtask.update');
    Route::delete('/subtask/{id}/delete', [SubTaskController::class, 'destroy'])->name('subtask.delete');
    Route::post('/subtask/comment/store', [SubTaskController::class, 'storeComment'])->name('subtask.storeComment');
    Route::delete('/subtask/comment/{id}/delete', [SubTaskController::class, 'destroyComment'])->name('subtask.deleteComment');
    Route::delete('/replies/{id}', [SubTaskController::class, 'destroyReply'])->name('reply.destroy');
    Route::get('/subtask-details/{id}', [SubTaskController::class, 'details'])->name('subtask.details');
    Route::post('/subtask/store-reply', [SubTaskController::class, 'storeReply'])->name('subtask.storeReply');
    Route::get('/user-suggestions', [SubTaskController::class, 'getSuggestions'])->name('user.suggestions');
    // chat and group chat
    Route::get('/one-to-one-chat', [MessagesController::class, 'index'])->name('one-to-one.index');
    Route::post('/one-to-one-chat/send', [MessagesController::class, 'send'])->name('one-to-one.send');

    
Route::get('/groups', [GroupChatController::class, 'index'])->name('groups.index');

Route::post('/groups', [GroupChatController::class, 'createGroup'])->name('groups.create');

// Add Users to a Group
Route::post('/groups/{group}/users', [GroupChatController::class, 'addUsersToGroup'])->name('groups.addUsers');

// Send a Message to a Group
Route::post('/groups/{group}/send-message', [GroupChatController::class, 'sendMessage'])->name('groups.sendMessage');Route::get('/groups/{group}', [GroupChatController::class, 'show'])->name('groups.show');
Route::delete('/groups/{group}/remove', [GroupChatController::class, 'removeGroup'])
    ->name('groups.remove');
// Fetch Messages for a Group
Route::get('/groups/{group}/messages', [GroupChatController::class, 'fetchGroupMessages'])->name('groups.fetchMessages');
Route::delete('/groups/{group}/messages/{message}', [GroupChatController::class, 'deleteGroupMessage'])->name('groups.messages.delete');
    Route::post('/mark-as-read', [MessagesController::class, 'markAsRead'])->name('chat.markAsRead');
    Route::get('search', [MessagesController::class, 'search'])->name('user.search');
    Route::post('/group/create', [MessagesController::class, 'createGroup'])->name('group.create');
    Route::delete('/message/{id}/delete', [MessagesController::class, 'delete'])->name('message.delete');
    Route::post('/group/{group}/add-users', [MessagesController::class, 'addUsers'])->name('group.addUsers');
Route::delete('/group/{group}/remove-user/{user}', [MessagesController::class, 'removeUser'])->name('group.removeUser');

// call handle
Route::get('/fetch-notifications', [CallController::class, 'fetchNotifications'])->name('fetch.notifications');
Route::post('/generate-agora-token', [CallController::class, 'generateToken']);
Route::post('/send-call-notification', [CallController::class, 'initiateCall']);
Route::get('/call/{reciever_id}/{type}', [CallController::class, 'showCallPage'])->name('call.page');
Route::post('/send-call-notificationss', [CallController::class, 'sendCallNotification']);
Route::get('/fetch-notifications', [CallController::class, 'fetchNotifications']);
Route::post('/mark-notification-read', [CallController::class, 'markNotificationRead']);
Route::post('/initiate-group-call', [CallController::class, 'initiateGroupCall'])->name('initiate.group.call');
Route::get('/group-call/{groupId}/{type}', [CallController::class, 'showGroupCallPage'])->name('group.call');

Route::get('/get-agora-token', [AgoraController::class, 'getToken']);
});

Route::post('/save-editor-data', function (Request $request) {
    $content = $request->input('content');

    // Save the content to the database or perform other actions
    Log::info('Editor Content:', ['content' => $content]);

    return response()->json(['message' => 'Data saved successfully!']);
});


Route::get('/collaborate', [InvitationController::class, 'joinTeam']);

require __DIR__ . '/auth.php';

Route::delete('/chat/messages/{message}', 'ChatController@destroy')->name('one-to-one.delete');

Broadcast::routes(['middleware' => ['web', 'auth']]);
