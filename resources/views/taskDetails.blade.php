@extends('layouts.app')

@section('page-styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/rowreorder/1.4.1/css/rowReorder.dataTables.min.css">


    <style>
        .suggestion-box {

            position: absolute;

            z-index: 1000;

            background: #fff;

            border: 1px solid #ccc;

            width: 20%;

            max-height: 200px;

            overflow-y: auto;

        }



        .suggestion-list {

            list-style-type: none;

            padding: 0;

            margin: 0;

        }



        .suggestion-item {

            padding: 10px;

            cursor: pointer;

        }



        .suggestion-item:hover {

            background-color: #f0f0f0;

        }
    </style>
@endsection

@php
    $taskStatuses = [
        'To Do',
        'In Progress',
        'Done',
        'Blocked',
        'Ready for Testing',
        'Testing in Progress',
        'Failed Testing',
        'Ready for Deployment',
        'Deployed',
        'Reopened',
    ];

    $taskPriorities = ['Low', 'Medium', 'High', 'Critical'];

    $taskTypes = ['Bug', 'Feature', 'Task', 'Improvement', 'Epic', 'Sub-task'];
@endphp

@section('page-content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- Project Header --}}
        <div class="d-flex pt-3">
            {{-- Project Details  --}}
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('project.show', $project->id) }}">{{ $project->name }}</a>
                    </li>
                    <li class="breadcrumb-item"><a href="javascript:void(0)">{{ $task->board->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $task->title }}</li>
                </ol>
            </nav>
            {{-- End Project Detials --}}

            {{-- Project Actions --}}
            <div class="ms-auto">
                {{-- Dropdown --}}
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary btn-icon dropdown-toggle hide-arrow"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal"
                                data-bs-target="#editModal"><span class="bx bx-edit-alt"></span> Edit</a></li>
                        </li>

                        @if (Auth::user()->getProjectRole($project->id) === $project::ROLE_ADMIN)
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li>
                                <form action="{{ route('task.delete', $task->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')

                                    <button class="dropdown-item text-danger">
                                        <span class="bx bx-trash"></span> Delete
                                    </button>
                                </form>
                            </li>
                        @endif
                    </ul>
                </div>
                {{-- End Dropdown --}}
            </div>
            {{-- End Project Actions --}}
        </div>
        {{-- End Project Header --}}

        {{-- Page Code --}}

        <h5 class="mt-3">{{ $task->board->project->name }} - Task {{ $task->id }}

            {{-- ----------------------- URL to redirect to same page ------------------------  --}}
            {{-- <span class="text-primary bx bx-link"></span> --}}



            <div id="toast"
                style="
                  position: fixed;
                  bottom: 20px;
                  right: 20px;
                  background-color: #4caf50;
                  color: white;
                  padding: 10px 20px;
                  border-radius: 5px;
                  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                  display: none;
                  z-index: 1000;
                  font-family: Arial, sans-serif;
                  font-size: 14px;">
                URL Copied to Clipboard!
            </div>

            <button style="margin-top: -6px" class="btn btn-icon btn-sm btn-simple" onclick="copyURL()">
                <span class="text-primary bx bx-link"></span>
            </button>

            <button class="btn btn-icon btn-sm btn-simple" onclick="shareContent()">
                <span class="text-primary bx bx-share-alt"></span>
            </button>


            <script>
                function copyURL() {
                    const pageURL = window.location.href; // Get the current page URL
                    navigator.clipboard.writeText(pageURL)
                        .then(() => {
                            const toast = document.getElementById('toast');
                            toast.style.display = 'block'; // Show the toast
                            toast.style.opacity = '1'; // Ensure it is fully visible
                            setTimeout(() => {
                                toast.style.transition = 'opacity 0.5s'; // Add fade-out effect
                                toast.style.opacity = '0'; // Fade out
                                setTimeout(() => {
                                    toast.style.display = 'none'; // Hide the toast after fading
                                }, 500); // Delay for fade-out duration
                            }, 2000); // Show toast for 2 seconds
                        })
                        .catch(err => {
                            console.error("Failed to copy: ", err);
                        });
                }

                function shareContent() {
                    if (navigator.share) {
                        navigator.share({
                                title: document.title, // Current page title
                                text: 'Check out this awesome content!', // Custom text to share
                                url: window.location.href // Current page URL
                            })
                            .then(() => console.log('Content shared successfully!'))
                            .catch((error) => console.error('Error sharing:', error));
                    } else {
                        alert('Sharing is not supported by your browser.');
                    }
                }
            </script>

            {{-- ----------------------- URL to redirect to same page ------------------------  --}}


        </h5>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header" bis_skin_checked="1">
                        <h5 class="card-tile mb-0">Task Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- Left --}}
                            <div class="col-md-8 col-lg-7">
                                <div class="row">
                                    <div class="col mb-3">
                                        <label for="edit-task-title" class="form-label">Title</label>
                                        <p>{{ $task->title }}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col mb-3">
                                        <label class="form-label">Description</label>
                                        <div>{!! $task->description !!}</div>

                                    </div>
                                </div>
                            </div>
                            {{-- End Left --}}

                            {{-- Right --}}
                            <div class="col-md-4 col-lg-5">
                                <div class="row g-2">
                                    <div class="col mb-3">
                                        <label for="edit-status-select" class="form-label">Select Status</label>
                                        <p>{{ $task->status }}</p>
                                    </div>
                                    <div class="col mb-3">
                                        <label for="edit-priority-select" class="form-label">Select Priority</label>
                                        <p>{{ $task->priority }}</p>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col mb-3">
                                        <label for="edit-task-type" class="form-label">Task Type</label>
                                        <p>{{ $task->type }}</p>
                                    </div>
                                    <div class="col mb-3">
                                        <label for="edit-due-date" class="form-label">Due Date</label>
                                        <p>{{ $task->due_date->format('Y-m-d') }}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <!-- <div class="col mb-3">
                        <label for="assigned-to" class="form-label">Assigned To</label>
                        <p>
                          @foreach ($task->users as $u)
    {{ $u->name }}
                            @if (!$loop->last)
    ,
    @endif
    @endforeach
                        </p>
                      </div> -->
                                    <div class="col mb-3">

                                        <label for="assigned-to" class="form-label">Assigned To</label>

                                        <div class="d-flex align-items-center">

                                            <!-- Avatar for each user -->

                                            @foreach ($task->users as $u)
                                                <div class="avatar avatar-sm me-2">

                                                    <img src="{{ !empty($u->getFirstMediaUrl()) ? $u->getFirstMediaUrl('default', 'preview') : 'https://ui-avatars.com/api/?name=' . urlencode($u->name) }}"
                                                        alt="user-avatar" class="d-block rounded-circle"
                                                        style="object-fit: cover" height="30" width="30" />

                                                </div>

                                                <!-- User name -->

                                                <p class="mb-0">

                                                    {{ $u->name }}

                                                </p>

                                                @if (!$loop->last)
                                                    <!-- If there are multiple users, add a comma between names -->

                                                    <span>, </span>
                                                @endif
                                            @endforeach

                                        </div>

                                    </div>
                                </div>
                            </div>
                            {{-- End Right --}}
                        </div>

                        <div class="clearfix"></div>
                    </div>
                </div>

                <div class="nav-align-top mb-4" bis_skin_checked="1">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                data-bs-target="#navs-top-home" aria-controls="navs-top-home" aria-selected="true">
                                <i class="tf-icons bx bx-message-square"></i> Messages
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                data-bs-target="#navs-top-profile" aria-controls="navs-top-profile" aria-selected="false"
                                tabindex="-1">
                                <i class="tf-icons bx bx-home"></i> History
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content" bis_skin_checked="1">
                        {{-- Messages / Comments --}}
                        <div class="tab-pane fade active show" id="navs-top-home" role="tabpanel" bis_skin_checked="1">
                            <form action="{{ route('task.storeComment') }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $task->id }}">
                                <div class="mb-3">
                                    <label class="form-label">Write a comment message</label>
                                    {{-- <textarea class="form-control" name="comment" aria-label="Add comment" aria-describedby="task's comment box">{{ old('comment') }}</textarea>
                </div>
                <div class="mb-3">
                  <input class="form-control" type="file" name="attachments[]" multiple />
                </div>

                <button class="btn btn-sm btn-primary">Add comment</button>
              </form> --}}

                                    <textarea class="form-control comment-input" name="comment" placeholder="Type @ to mention users"
                                        aria-label="Add comment" data-suggestion-url="{{ route('user.suggestions') }}">{{ old('comment') }}</textarea>



                                    <div class="suggestion-box d-none">

                                        <ul class="list-group suggestion-list">

                                        </ul>

                                    </div>

                                </div>



                                <div class="mb-3">

                                    <input class="form-control" type="file" name="attachments[]" multiple />

                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Add comment</button>
                            </form>
                            {{-- ------------------------ Updated --}}


                            @if (is_countable($comments) && count($comments) > 0)
                                <div class="mt-5">
                                    @foreach ($comments as $comment)
                                        <div class="comments">
                                            <div class="user-icon">
                                                <a href="javascript:void(0)">
                                                    <img src="{{ !empty($comment->user->getFirstMediaUrl()) ? $comment->user->getFirstMediaUrl('default', 'preview') : 'https://ui-avatars.com/api/?name=' . urlencode($comment->user->name) }}"
                                                        alt="user-avatar" class="d-block rounded-circle"
                                                        style="object-fit: cover" height="30" width="30" />
                                                </a>
                                            </div>
                                            <div class="comment-body">
                                                <div class="comment-title">
                                                    <a href="javascript:void(0)">{{ $comment->user->name }}</a>
                                                    <span>{{ $comment->created_at->diffForHumans() }}</span>
                                                    @if ($comment->user_id == Auth::id())
                                                        <form action="{{ route('task.deleteComment', $comment->id) }}"
                                                            method="POST" class="me-1 d-inline-block">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-icon btn-sm btn-simple">
                                                                <span class="tf-icons bx bx-trash"></span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                                <p>
                                                    {!! nl2br($comment->comment) !!}
                                                </p>
                                                <div class="replies ms-4">

                                                    @foreach ($comment->replies as $reply)
                                                        <div class="reply mb-2">

                                                            <div class="d-flex align-items-start">

                                                                <div class="user-icon me-2">

                                                                    <img src="{{ !empty($reply->user->getFirstMediaUrl()) ? $reply->user->getFirstMediaUrl('default', 'preview') : 'https://ui-avatars.com/api/?name=' . urlencode($reply->user->name) }}"
                                                                        alt="user-avatar" class="d-block rounded-circle"
                                                                        style="object-fit: cover" height="30"
                                                                        width="30" />

                                                                </div>

                                                                <div class="reply-body">

                                                                    <div class="reply-title">

                                                                        <strong>{{ $reply->user->name }}</strong>

                                                                        <span
                                                                            class="text-muted">{{ $reply->created_at->diffForHumans() }}</span>



                                                                        @if ($reply->user_id == Auth::id())
                                                                            {{-- Check if the reply belongs to the authenticated user --}}

                                                                            <form
                                                                                action="{{ route('reply.destroy', $reply->id) }}"
                                                                                method="POST"
                                                                                class="d-inline-block ms-2">

                                                                                @csrf

                                                                                @method('DELETE')

                                                                                <button type="submit"
                                                                                    class="btn btn-icon btn-sm btn-simple"
                                                                                    title="Delete Reply">

                                                                                    <span
                                                                                        class="tf-icons bx bx-trash"></span>

                                                                                </button>

                                                                            </form>
                                                                        @endif

                                                                    </div>

                                                                    <p>{!! nl2br(e($reply->comment)) !!}</p> {{-- Escape HTML content for security --}}

                                                                </div>

                                                            </div>

                                                        </div>
                                                    @endforeach

                                                </div>



                                                <button class="btn btn-sm btn-link text-primary reply-toggle"
                                                    data-comment-id="{{ $comment->id }}">

                                                    Reply

                                                </button>

                                                {{-- Reply Form (Hidden by Default) --}}

                                                <div class="reply-form d-none" id="reply-form-{{ $comment->id }}">

                                                    <form action="{{ route('task.storeReply') }}" method="POST">

                                                        @csrf

                                                        <input type="hidden" name="task_comment_id"
                                                            value="{{ $comment->id }}">

                                                        <textarea class="form-control" name="reply" placeholder="Write your reply..." required></textarea>

                                                        <button type="submit" class="btn btn-primary btn-sm">Submit
                                                            Reply</button>

                                                    </form>

                                                </div>


                                                {{-- Updated --}}
                                                @if ($comment->getMedia('attachments')->count() > 0)
                                                    <div class="attachments">
                                                        <small class="text-light fw-semibold">Attachments</small>
                                                        <div class="demo-inline-spacing mt-3">
                                                            <div class="list-group">
                                                                @foreach ($comment->getMedia('attachments') as $ca)
                                                                    <a href="{{ $ca->getUrl() }}" target="_blank"
                                                                        class="list-group-item list-group-item-action">{{ $ca->name }}</a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                        </div>
                        {{-- End Messages Comments --}}

                        {{-- History --}}
                        <div class="tab-pane fade" id="navs-top-profile" role="tabpanel" bis_skin_checked="1">
                            <div>
                                @foreach ($task->history as $h)
                                    <div class="comments history mb-4">
                                        <div class="user-icon">
                                            <a href="javascript:void(0)">
                                                <img src="{{ !empty($h->user->getFirstMediaUrl()) ? $h->user->getFirstMediaUrl('default', 'preview') : 'https://ui-avatars.com/api/?name=' . urlencode($h->user->name) }}"
                                                    alt="user-avatar" class="d-block rounded-circle"
                                                    style="object-fit: cover" height="30" width="30" />
                                            </a>
                                        </div>
                                        <div class="comment-body">
                                            <div class="comment-title">
                                                <a href="javacript:void(0)">{{ $h->user->name }}</a>
                                                <span>{{ $h->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="mb-1">
                                                {{ $h->type }}
                                            </p>
                                            @foreach (json_decode($h->data) ?? [] as $key => $value)
                                                <p class="m-0 text-sm"> {{ $key }} >>> {{ $value }}</p>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        {{-- End History --}}

                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-tile mb-0">{{ $task->board->name }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group" bis_skin_checked="1">
                            @forelse ($task->board->tasks as $t)
                                <a href="{{ route('task.show', ['id' => $t->id]) }}"
                                    class="list-group-item list-group-item-action d-flex justify-content-between">
                                    <div class="li-wrapper d-flex justify-content-start align-items-center"
                                        bis_skin_checked="1">
                                        <div class="avatar avatar-sm me-3" bis_skin_checked="1">
                                            @foreach ($t->users as $u)
                                                <img src="{{ !empty($u->getFirstMediaUrl()) ? $u->getFirstMediaUrl('default', 'preview') : 'https://ui-avatars.com/api/?name=' . urlencode($u->name) }}"
                                                    alt="user-avatar" class="d-block rounded-circle"
                                                    style="object-fit: cover" height="30" width="30" />
                                            @endforeach

                                        </div>
                                        <div class="list-content" bis_skin_checked="1">
                                            <h6 class="mb-1">{{ $t->title }}</h6>
                                            <small class="text-muted">{{ $t->status }}</small>
                                        </div>
                                    </div>
                                    <small>{{ $t->created_at->diffForHumans() }}</small>
                                </a>
                            @empty
                                <div class="alert alert-primary">No other tasks in this sprint</div>
                            @endforelse
                        </div>




                    </div>
                </div>
            </div>
        </div>
        {{-- End Page Code --}}

        {{-- Edit Task Modal --}}
        <div class="modal fade" id="editModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Task</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form action="{{ route('task.update', $task->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="row">
                                <div class="col mb-3">
                                    <label for="task-title" class="form-label">Title</label>
                                    <input type="text" id="task-title"
                                        class="form-control @error('title', 'task') is-invalid @enderror"
                                        placeholder="Write a task title" name="title"
                                        value="{{ old('title', $task->title) }}" />
                                    @error('title', 'task')
                                        <div class="form-text text-danger">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea id="editor" name="description">{{ old('description', $task->description) }}</textarea>
                                    @error('description', 'task')
                                        <div class="form-text text-danger">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col mb-3">
                                    <label for="status-select" class="form-label">Select Status</label>
                                    <select class="form-select @error('status', 'task') is-invalid @enderror"
                                        id="status-select" aria-label="select task status" name="status">
                                        @foreach ($taskStatuses as $status)
                                            <option value="{{ $status }}" class="text-capitalize"
                                                @selected($task->status == $status)>
                                                {{ $status }}</option>
                                        @endforeach
                                    </select>
                                    @error('status', 'task')
                                        <div class="form-text text-danger">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col mb-3">
                                    <label for="priority-select" class="form-label">Select Priority</label>
                                    <select class="form-select @error('priority', 'task') is-invalid @enderror"
                                        id="priority-select" aria-label="select task priority" name="priority">
                                        @foreach ($taskPriorities as $priority)
                                            <option value="{{ $priority }}" class="text-capitalize"
                                                @selected($task->priority == $priority)>
                                                {{ $priority }}</option>
                                        @endforeach
                                    </select>
                                    @error('priority', 'task')
                                        <div class="form-text text-danger">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col mb-3">
                                    <label for="task-type" class="form-label">Task Type</label>
                                    <select class="form-select @error('type', 'task') is-invalid @enderror" id="task-type"
                                        aria-label="select task type" name="type">
                                        @foreach ($taskTypes as $type)
                                            <option value="{{ $type }}" @selected($task->type == $type)>
                                                {{ $type }}</option>
                                        @endforeach
                                    </select>
                                    @error('type', 'task')
                                        <div class="form-text text-danger">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col mb-3">
                                    <label for="due-date" class="form-label">Due Date</label>
                                    <input class="form-control @error('due_date', 'task') is-invalid @enderror"
                                        type="date" value="{{ old('due_date', $task->due_date->format('Y-m-d')) }}"
                                        name="due_date" />
                                    @error('due_date', 'task')
                                        <div class="form-text text-danger">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col mb-3">
                                    <label for="assigned-to" class="form-label">Assigned To</label>
                                    <select class="form-select @error('task_user', 'task') is-invalid @enderror"
                                        id="assigned-to" aria-label="assign task" name="assigned_to">
                                        @foreach ($project->users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('task_user', 'task')
                                        <div class="form-text text-danger">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Close
                            </button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
        {{-- End Create Task Modal --}}

    </div>
@endsection


@section('page-scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/40.2.0/classic/ckeditor.js"></script>

    <script>
        ClassicEditor
            .create(document.querySelector('#editor'))
            .catch(error => {
                console.error(error);
            });
    </script>

    <script>
        $(document).ready(function() {

            // Open Board Edit Modal
            $(document).on('click', '.edit_board_btn', function(e) {
                e.preventDefault();
                var updateRoute = $(this).attr('data-board-update-route');
                var boardName = $(this).attr('data-board-name');
                var boardSorting = $(this).attr('data-board-sorting');

                $('#editBoardForm').attr('action', updateRoute);
                $('#edit-board-name').val(boardName);
                $('#edit-board-sorting').val(boardSorting);

                $('#editBoardModal').modal('show');
            });
            // End Open Board Edit Modal

            // Open Add Task Modal
            $(document).on('click', '.add_task_btn', function(e) {
                e.preventDefault();
                var boardId = $(this).attr('data-board-id');
                $('#add-task-board-id').val(boardId);

                $('#addTaskModal').modal('show');
            });
            // End Open Add Task Modal

        });
    </script>

    {{-- We are using script tag as this page will be loaded via Ajax Request --}}
    <script>
        $(document).ready(function() {
            ClassicEditor
                .create(document.querySelector('#editor-edit'))
                .catch(error => {
                    console.error(error);
                });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('.reply-toggle').forEach(button => {

                button.addEventListener('click', function() {

                    const commentId = this.dataset.commentId;

                    const replyForm = document.getElementById(`reply-form-${commentId}`);

                    replyForm.classList.toggle('d-none');

                });

            });

        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const commentInput = document.querySelector('.comment-input');

            const suggestionBox = document.querySelector('.suggestion-box');

            const suggestionList = suggestionBox.querySelector('.suggestion-list');



            let queryTimeout;



            commentInput.addEventListener('input', function() {

                const cursorPosition = commentInput.selectionStart;

                const text = commentInput.value;



                // Detect `@` and get the current query

                const atIndex = text.lastIndexOf('@', cursorPosition - 1);

                if (atIndex !== -1) {

                    const query = text.substring(atIndex + 1, cursorPosition).trim();

                    var item = fetchSuggestions(query);

                    // alert(item);









                    // Fetch suggestions if query is not empty

                    if (query.length > 0) {

                        clearTimeout(queryTimeout);

                        queryTimeout = setTimeout(() => fetchSuggestions(query), 300); // Add debounce

                    } else {

                        suggestionBox.classList.add('d-none');

                    }

                } else {

                    suggestionBox.classList.add('d-none');

                }

            });



            function fetchSuggestions(query) {

                const url = commentInput.getAttribute('data-suggestion-url');

                fetch(`${url}?query=${query}`)

                    .then(response => response.json())

                    .then(users => renderSuggestions(users))

                    .catch(error => console.error('Error fetching suggestions:', error));

            }



            function renderSuggestions(users) {

                suggestionList.innerHTML = '';

                if (users.length > 0) {

                    users.forEach(user => {

                        const li = document.createElement('li');

                        li.className = 'list-group-item suggestion-item';

                        li.textContent = user.name;

                        li.dataset.id = user.id;



                        li.addEventListener('click', function() {

                            insertUserMention(user.name);

                        });



                        suggestionList.appendChild(li);

                    });



                    suggestionBox.classList.remove('d-none');

                } else {

                    suggestionBox.classList.add('d-none'); // Hide box if no results

                }

            }



            function insertUserMention(username) {

                const cursorPosition = commentInput.selectionStart;

                const text = commentInput.value;

                const atIndex = text.lastIndexOf('@', cursorPosition - 1);



                if (atIndex !== -1) {

                    commentInput.value =

                        text.substring(0, atIndex) +

                        `@${username} ` +

                        text.substring(cursorPosition);

                }



                commentInput.focus();

                suggestionBox.classList.add('d-none');

            }

        });
    </script>
@endsection
