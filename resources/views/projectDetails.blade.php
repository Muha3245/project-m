@extends('layouts.app')

@section('page-styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/rowreorder/1.4.1/css/rowReorder.dataTables.min.css">
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
            <div>
                <div class="d-flex mb-2">
                    <h4 class="fw-bol m-0 me-2">{{ $project->name }}</h4>
                    <button type="button" class=" me-1 btn btn-icon btn-sm btn-simple" data-bs-toggle="modal"
                        data-bs-target="#projectInfoModal">
                        <span class="tf-icons bx bx-info-circle"></span>
                    </button>
                    <form action="{{ route('project.starToggle', $project->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-icon btn-sm btn-simple ms-2">
                            <span
                                class="tf-icons bx bxs-star {{ in_array($project->id, $markedProjects->pluck('id')->toArray()) ? 'text-warning' : '' }}"></span>
                        </button>
                    </form>
                </div>
                <p class="text-truncate" style="max-width: 500px; width: 100%">
                    {{ $project->description }}
                </p>
            </div>
            {{-- End Project Detials --}}

            {{-- Project Actions --}}
            <div style="min-width: 150px" class="ms-auto">
                {{-- Add Group Button --}}
                @if (Auth::user()->getProjectRole($project->id) === $project::ROLE_ADMIN)
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBoardModal"
                        aria-controls="createBoardModal">
                        <span class="tf-icons bx bx-plus"></span>&nbsp; Add Board
                    </button>
                @endif
                {{-- End Add Group Button --}}

                {{-- Invite Button --}}
                @if (Auth::user()->getProjectRole($project->id) === $project::ROLE_ADMIN)
                    <button type="button" class="btn btn-primary me-1" data-bs-toggle="offcanvas"
                        data-bs-target="#invitationOffCanvas" aria-controls="invitationOffCanvas">
                        <span class="tf-icons bx bx-user-plus"></span>&nbsp; Invite
                    </button>
                @endif
                {{-- End Invite Button --}}

                {{-- Dropdown --}}
                <div class="btn-group float-end">
                    <button type="button" class="btn btn-outline-primary btn-icon dropdown-toggle hide-arrow"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal"
                                data-bs-target="#projectInfoModal"><span class="bx bx-info-circle"></span> Details</a>
                        </li>
                        <li><a class="dropdown-item" href="{{ route('board.history', $project->id) }}"><span
                                    class="bx bx-info-circle"></span> Boards History</a>
                        </li>
                        @if (Auth::user()->getProjectRole($project->id) === $project::ROLE_ADMIN)
                            <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal"
                                    data-bs-target="#editModal"><span class="bx bx-edit-alt"></span> Edit</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="offcanvas"
                                    data-bs-target="#invitationOffCanvas"><span class="bx bx-user-plus"></span> Invite</a>
                            </li>
                            {{-- <li><a class="dropdown-item" href="javascript:void(0);"><span class="bx bx-user-check"></span> Permissions</a> --}}
                            </li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item text-danger" href="javascript:void(0);">
                                    <span class="bx bx-trash"></span> Delete
                                </a></li>
                        @endif
                    </ul>
                </div>
                {{-- End Dropdown --}}
            </div>
            {{-- End Project Actions --}}
        </div>
        {{-- End Project Header --}}

        {{-- Invite Member offcanvas --}}
        @if (Auth::user()->getProjectRole($project->id) === $project::ROLE_ADMIN)
            <div class="offcanvas offcanvas-end" tabindex="-1" id="invitationOffCanvas"
                aria-labelledby="offcanvasEndLabel">
                <div class="offcanvas-header">
                    <h5 id="offcanvasEndLabel" class="offcanvas-title">
                        <span class="bx bx-user-plus mt-n1"></span>
                        Invite Member
                    </h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
                </div>
                <div class="offcanvas-body mx-0 flex-grow-0">

                    <form action="{{ route('invitations.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="project">
                        <input type="hidden" name="id" value="{{ $project->id }}">

                        <div class="mb-3">
                            <label for="invitation-email" class="form-label">Email address</label>
                            <div class="input-group">
                                <span class="input-group-text" id="invitation-addon">
                                    <span class="bx bx-envelope"></span>
                                </span>
                                <input type="email" name="email" value="{{ old('email') }}"
                                    class="form-control @error('email', 'invitation') is-invalid @enderror"
                                    placeholder="Enter Email" aria-label="Enter Email" aria-describedby="invitation-addon"
                                    required>
                            </div>
                            @error('email', 'invitation')
                                <div class="form-text text-danger">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <span class="tf-icons bx bx-paper-plane"></span>&nbsp; Send Invite
                            </button>

                            <button type="button" class="btn btn-outline-secondary d-grid w-100"
                                data-bs-dismiss="offcanvas">
                                Cancel
                            </button>
                        </div>
                    </form>

                    @if (count($project->invites) > 0)
                        <hr>
                        <h5 class="offcanvas-title">
                            <span class="bx bx-time mt-n1"></span>
                            Invitation History
                        </h5>
                        <ul class="list-group list-group-flush">
                            @foreach ($project->invites as $invite)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0">{{ $invite->email }}</p>
                                        <small class="text-dark">Expires: {{ $invite->expires->format('M d, Y') }}</small>
                                    </div>

                                    <div class="d-flex">
                                        <form action="{{ route('invitations.resend', $invite->id) }}" method="POST"
                                            class="me-2">
                                            @csrf

                                            <button type="submit" class="btn rounded-pill btn-icon btn-outline-primary"
                                                data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="right"
                                                data-bs-html="false" title="Resend invitation email">
                                                <span class="tf-icons bx bx-redo"></span>
                                            </button>
                                        </form>
                                        <form action="{{ route('invitations.delete', $invite->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn rounded-pill btn-icon btn-outline-danger"
                                                data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="right"
                                                data-bs-html="false" title="Revoke and delete Invitation">
                                                <span class="tf-icons bx bx-trash"></span>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                </div>
            </div>
        @endif
        {{-- End Invite Member offcanvas --}}

        <hr>

        {{-- <div class="mb-5" id="project-board"></div> --}}

        <div class="project-details">
            {{-- Project Board --}}
            @forelse ($project->boards as $board)
                <div class="project-group">
                    <div class="d-flex  mb-3">
                        <h5 class="m-0">{{ $board->name }}</h5>
                        <div class="ms-auto">
                            <button type="button" class="btn btn-sm btn-primary add_task_btn"
                                data-board-id="{{ $board->id }}">
                                <span class="tf-icons bx bx-plus"></span>&nbsp; Add Task
                            </button>
                            @if (Auth::user()->getProjectRole($project->id) === $project::ROLE_ADMIN)
                                <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit_board_btn"
                                    data-board-name="{{ $board->name }}" data-board-sorting="{{ $board->sorting }}"
                                    data-board-update-route="{{ route('board.update', $board->id) }}">
                                    <span class="tf-icons bx bx-edit-alt"></span>
                                </button>
                            @endif
                        </div>
                    </div>

                    @if (is_countable($board->tasks) && count($board->tasks) > 0)
                        <div class="p-group-body mb-5">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <td>Title</td>
                                                    <td>Status</td>
                                                    <td>Priority</td>
                                                    <td>Due Date</td>
                                                    <td>Type</td>
                                                    <td>Assigned to</td>
                                                    <td>Created at</td>
                                                    <td>Last Updated</td>
                                                    <td>Subtask</td>
                                                    <td>Action</td>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($board->tasks as $task)
                                                    <tr id="task-{{ $task->id }}">
                                                        <td>
                                                            {{ $task->title }}
                                                            @if ($task->subtasks->count() > 0)
                                                                <button class="btn btn-link toggle-subtasks"
                                                                    data-task-id="{{ $task->id }}">
                                                                    <i class="bx bx-plus-circle"></i>
                                                                </button>
                                                            @endif
                                                        </td>
                                                        <td>{{ $task->status }}</td>
                                                        <td>{{ $task->priority }}</td>
                                                        <td>{{ $task->due_date->format('d/M/Y') }}</td>
                                                        <td>{{ $task->type }}</td>
                                                        <td>@foreach ($task->users as $u)
                                                            <img src="{{ !empty($u->getFirstMediaUrl()) ? $u->getFirstMediaUrl('default', 'preview') : 'https://ui-avatars.com/api/?name=' . urlencode($u->name) }}"
                                                                alt="user-avatar" class="d-block rounded-circle"
                                                                style="object-fit: cover" height="30" width="30" />
                                                        @endforeach</td>
                                                        <td>{{ $task->created_at->format('d/M/Y') }}</td>
                                                        <td>{{ $task->updated_at->diffForHumans() }}</td>
                                                        <td>
                                                            <button type="button"
                                                                class="btn btn-sm btn-primary add_subtask_btn"
                                                                data-board-id="{{ $task->id }}">
                                                                <span class="tf-icons bx bx-plus"></span>
                                                            </button>

                                                        </td>
                                                        <td>
                                                            <a href="{{ route('task.show', ['id' => $task->id]) }}"
                                                                class="btn btn-sm btn-primary">
                                                                <span class="tf-icons bx bx-edit-alt"></span>
                                                            </a>
                                                        </td>
                                                    </tr>

                                                    {{-- Subtasks Row (Hidden by Default) --}}
                                                    @if ($task->subtasks->count() > 0)
                                                        <tr class="subtasks-row d-none"
                                                            data-task-id="{{ $task->id }}">
                                                            <td colspan="10">
                                                                <table
                                                                    class="table table-striped table-bordered table-sm ">
                                                                    <thead>
                                                                        <tr>
                                                                            <td>Title</td>
                                                                            <td>Status</td>
                                                                            <td>Priority</td>
                                                                            <td>Due Date</td>
                                                                            <td>Type</td>
                                                                            <td>Assigned to</td>
                                                                            <td>Created at</td>
                                                                            <td>Last Updated</td>
                                                                            <td>SHOW</td>
                                                                            <td>Edit</td>
                                                                            <td>delete</td>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach ($task->subtasks as $subItem)
                                                                            <tr>
                                                                                <td>{{ $subItem->title }}</td>
                                                                                <td>{{ $subItem->status }}</td>
                                                                                <td>{{ $subItem->priority }}</td>
                                                                                <td>{{ \Carbon\Carbon::parse($subItem->due_date)->format('d/M/Y') }}
                                                                                </td>
                                                                                <td>{{ $subItem->type }}</td>
                                                                                <td>@foreach ($subItem->users as $u)
                                                                                    <img src="{{ !empty($u->getFirstMediaUrl()) ? $u->getFirstMediaUrl('default', 'preview') : 'https://ui-avatars.com/api/?name=' . urlencode($u->name) }}"
                                                                                        alt="user-avatar" class="d-block rounded-circle"
                                                                                        style="object-fit: cover" height="30" width="30" />
                                                                                @endforeach</td>
                                                                                <td>{{ \Carbon\Carbon::parse($subItem->created_at)->format('d/M/Y H:i') }}
                                                                                </td>
                                                                                <td>{{ \Carbon\Carbon::parse($subItem->updated_at)->diffForHumans() }}
                                                                                </td>
                                                                                <td>
                                                                                    <button type="button"
                                                                                        class="btn btn-sm btn-icon btn-outline-primary show_subtask_btn"
                                                                                        data-id="{{ $subItem->id }}"
                                                                                        data-title="{{ $subItem->title }}"
                                                                                        data-description="{!! $subItem->description !!}"
                                                                                        data-status="{{ $subItem->status }}"
                                                                                        data-priority="{{ $subItem->priority }}"
                                                                                        data-type="{{ $subItem->type }}"
                                                                                        data-due-date="{{ \Carbon\Carbon::parse($subItem->due_date)->format('d/M/Y') }}"
                                                                                        data-users='@json($subItem->users)'>
                                                                                        <span
                                                                                            class="tf-icons bx bx-show"></span>


                                                                                    </button>
                                                                                </td>
                                                                                <td>
                                                                                    <button type="button"
                                                                                        class="btn btn-sm btn-icon btn-outline-primary edit_subtask_btn"
                                                                                        data-subtask-title="{{ $subItem->title }}"
                                                                                        data-subtask-descriptions="{!! $subItem->description !!}"
                                                                                        data-subtask-status="{{ $subItem->status }}"
                                                                                        data-subtask-priority="{{ $subItem->priority }}"
                                                                                        data-subtask-type="{{ $subItem->type }}"
                                                                                        data-subtask-due-date="{{ \Carbon\Carbon::parse($subItem->due_date)->format('d/M/Y') }}"
                                                                                        data-subtask-assigned-to="@json($subItem->users)"
                                                                                        data-subtask-update-route="{{ route('subtask.update', $subItem->id) }}">
                                                                                        <span
                                                                                            class="tf-icons bx bx-edit-alt"></span>
                                                                                    </button>
                                                                                </td>
                                                                                <td>
                                                                                    <form
                                                                                        action="{{ route('subtask.delete', $subItem->id) }}"
                                                                                        method="POST"
                                                                                        class="d-inline delete-subtask-form">
                                                                                        @csrf
                                                                                        @method('DELETE')
                                                                                        <button type="button"
                                                                                            class="btn btn-sm btn-danger delete-subtask-btn"
                                                                                            data-id="{{ $subItem->id }}">
                                                                                            <i class="bx bx-trash"></i>
                                                                                        </button>
                                                                                    </form>
                                                                                </td>

                                                            </td>
                                                        </tr>
                                                    @endforeach
                                            </tbody>
                                        </table>

                                    </div>


                                    </td>
                                    </tr>
                    @endif
            @endforeach
            </tbody>
            </table>
        </div>
    </div>
    </div>
@else
    <div class="alert alert-primary">
        <p>
            <span class="bx bx-bell"></span>
            <strong>No Data</strong>
        </p>
        Start adding tasks to this board by clicking the
        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#addTaskModal"
            class="text-decoration-underline fw-bold">Add Task</a> button.
    </div>
    @endif





    </div>
@empty
    <div class="card mb-4">
        <h5 class="card-header">
            <span class="bx bx-bell"></span>
            Create Your First Board!
        </h5>
        <div class="card-body">
            <p class="card-text">
                Congratulations on taking the first step to supercharge your productivity! Let's create your
                first board and
                kickstart your journey to organized and efficient task management.
            </p>
            <p>
                Hit the <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#createBoardModal"
                    aria-controls="createBoardModal">Create Board</a>
                button below and customize your board to match your
                workflow. Get ready to experience a whole new level of collaboration and productivity!
            </p>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createBoardModal"
                aria-controls="createBoardModal">
                Let's create the first board
            </button>
        </div>
    </div>
    @endforelse
    {{-- End Project Board --}}
    </div>

    {{-- Project Info Modal --}}
    <div class="modal fade" id="projectInfoModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Project Info</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Details --}}
                    <div>
                        <h4>{{ $project->name }}</h4>
                        <p>
                            {{ nl2br($project->description) }}
                        </p>
                    </div>
                    {{-- End Details --}}

                    {{-- Members --}}
                    <div class="mt-5">
                        <h6>Members</h6>
                        @foreach ($project->users as $user)
                            <div class="d-flex mb-3 members-list">
                                <div class="flex-shrink-0  me-3">
                                    <img src="{{ !empty($user->getFirstMediaUrl()) ? $user->getFirstMediaUrl('default', 'preview') : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                                        alt="user-avatar" class="d-block rounded-circle" style="object-fit: cover"
                                        height="30" width="30" />
                                </div>
                                <div class="flex-grow-1 row">
                                    <div class="col-9 mb-sm-0 mb-2">
                                        @php
                                            $r = $project->roleMapping($user->pivot->role);
                                        @endphp
                                        <h6 class="mb-0">{{ $user->name }} <span
                                                class="badge rounded-pill bg-label-{{ Str::lower($r) == 'admin' ? 'primary' : 'secondary' }}"
                                                style="font-size:0.7rem;">{{ $r }}</span> </h6>
                                        <small class="text-muted">{{ $user->email }}</small>
                                    </div>
                                    <div class="col-3 text-end">
                                        @if ($user->pivot->role !== $project::ROLE_ADMIN && Auth::user()->getProjectRole($project->id) == $project::ROLE_ADMIN)
                                            <form
                                                action="{{ route('project.revokeAccess', ['id' => $project->id, 'userId' => $user->id]) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger"
                                                    data-bs-toggle="tooltip" data-bs-offset="0,4"
                                                    data-bs-placement="right" data-bs-html="false"
                                                    title="Remove {{ $user->name }} from project">
                                                    <span class="tf-icons bx bx-trash"></span>
                                                </button>
                                            </form>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    {{-- End Members --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- End Project Info Modal --}}

    {{-- Edit Modal --}}

    @if (Auth::user()->getProjectRole($project->id) === $project::ROLE_ADMIN)
        <div class="modal fade" id="editModal" data-bs-backdrop="static" tabindex="-1">
            <div class="modal-dialog">
                <form class="modal-content" action="{{ route('project.update', $project->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="backDropModalTitle">Edit Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col mb-3">
                                <label for="project-name" class="form-label">Name</label>
                                <input type="text" name="name" value="{{ old('name', $project->name) }}"
                                    id="project-name" class="form-control @error('name', 'project') is-invalid @enderror"
                                    placeholder="Enter Name" required />
                                @error('name', 'project')
                                    <div class="form-text text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <label for="project-description" class="form-label">Description</label>
                                <textarea name="description" id="project-description"
                                    class="form-control @error('description', 'project') is-invalid @enderror" rows="5">{{ old('description', $project->description) }}</textarea>
                                @error('description', 'project')
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
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    {{-- End Edit Modal --}}

    {{-- Create Task Modal --}}
    <div class="modal fade" id="addTaskModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add-task-modal-title">Add Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('task.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="board_id" id="add-task-board-id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col mb-3">
                                <label for="task-title" class="form-label">Title</label>
                                <input type="text" id="task-title"
                                    class="form-control @error('title', 'task') is-invalid @enderror"
                                    placeholder="Write a task title" name="title" value="{{ old('title') }}" />
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
                                <textarea id="editor" name="description">{{ old('description') }}</textarea>
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
                                        <option value="{{ $status }}" class="text-capitalize">
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
                                        <option value="{{ $priority }}" class="text-capitalize">
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
                                        <option value="{{ $type }}">{{ $type }}</option>
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
                                <input class="form-control @error('due_date', 'task') is-invalid @enderror" type="date"
                                    value="{{ old('due_date') }}" id="due-date" name="due_date" />
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

    <div class="modal fade" id="addRoleModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoleModalLabel">Add subtask</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('subtask.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="task_id" id="add-task-task-id">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col mb-3">
                                    <label for="task-title" class="form-label">Title</label>
                                    <input type="text" id="task-title"
                                        class="form-control @error('title', 'task') is-invalid @enderror"
                                        placeholder="Write a task title" name="title" value="{{ old('title') }}" />
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
                                    <textarea id="editor1" name="description">{{ old('description') }}</textarea>
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
                                            <option value="{{ $status }}" class="text-capitalize">
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
                                            <option value="{{ $priority }}" class="text-capitalize">
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
                                    <select class="form-select @error('type', 'task') is-invalid @enderror"
                                        id="task-type" aria-label="select task type" name="type">
                                        @foreach ($taskTypes as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
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
                                        type="date" value="{{ old('due_date') }}" id="due-date" name="due_date" />
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
    </div>


    {{-- Edit Task Modal --}}
    <div class="modal fade" id="editTaskModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content" id="edit-task-content">

            </div>
        </div>
    </div>
    {{-- End Edit Task Modal --}}
    {{-- Edit Subtask Modal --}}

    <div class="modal fade" id="editSubtaskModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Subtask</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="editSubtaskForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col mb-3">
                                <label for="subtask-title" class="form-label">Title</label>
                                <input type="text" id="subtask-title" class="form-control" name="title" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-3">
                                <label class="form-label">Description</label>
                                <textarea id="subtask-descriptions" name="description" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col mb-3">
                                <label for="status-select" class="form-label">Select Status</label>
                                <select class="form-select" id="status-select" name="status">
                                    @foreach ($taskStatuses as $status)
                                        <option value="{{ $status }}">{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col mb-3">
                                <label for="priority-select" class="form-label">Select Priority</label>
                                <select class="form-select" id="priority-select" name="priority">
                                    @foreach ($taskPriorities as $priority)
                                        <option value="{{ $priority }}">{{ $priority }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col mb-3">
                                <label for="subtask-type" class="form-label">Subtask Type</label>
                                <select class="form-select" id="subtask-type" name="type">
                                    @foreach ($taskTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col mb-3">
                                <label for="due-date" class="form-label">Due Date</label>
                                <input class="form-control" type="date" id="due-date" name="due_date" />
                            </div>
                        </div>
                        <div class="col mb-3">
                            <label for="assigned-to" class="form-label">Assigned To</label>
                            <select class="form-select" id="assigned-to" name="assigned_to">
                                
                                    @foreach ($task->users as $user)
                                        <!-- Now access users inside each subtask -->
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                
                            </select>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>

            </div>
        </div>
    </div>


    {{-- End Edit Subtask Modal --}}

    {{-- Show subtask modal --}}
    <div class="modal fade" id="showsubtask" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="show-subtask-id">Subtask Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card-body">
                        <div class="row">
                            <!-- Left Section -->
                            <div class="col-md-8 col-lg-7">
                                <div class="row">
                                    <div class="col mb-3">
                                        <label class="form-label">Title</label>
                                        <p id="subtask-title"></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col mb-3">
                                        <label class="form-label">Description</label>
                                        <div id="subtask-description"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- Right Section -->
                            <div class="col-md-4 col-lg-5">
                                <div class="row g-2">
                                    <div class="col mb-3">
                                        <label class="form-label">Status</label>
                                        <p id="subtask-status"></p>
                                    </div>
                                    <div class="col mb-3">
                                        <label class="form-label">Priority</label>
                                        <p id="subtask-priority"></p>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col mb-3">
                                        <label class="form-label">Type</label>
                                        <p id="subtask-type"></p>
                                    </div>
                                    <div class="col mb-3">
                                        <label class="form-label">Due Date</label>
                                        <p id="subtask-due-date"></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col mb-3">
                                        <label class="form-label">Assigned To</label>
                                        <div id="subtask-assigned-users"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- end subtask show modal --}}



    {{-- Create Board Modal --}}
    @if (Auth::user()->getProjectRole($project->id) === $project::ROLE_ADMIN)
        <div class="modal fade" id="createBoardModal" data-bs-backdrop="static" tabindex="-1">
            <div class="modal-dialog">
                <form class="modal-content" action="{{ route('board.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="backDropModalTitle">Create Board</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col mb-3">
                                <input type="hidden" name="project_id" value="{{ $project->id }}">
                                <label for="board-name" class="form-label">Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" id="board-name"
                                    class="form-control @error('name', 'board') is-invalid @enderror"
                                    placeholder="Enter Name" required />
                                @error('name', 'board')
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
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    {{-- End Create Board Modal --}}

    {{-- Edit Board Modal --}}
    @if (Auth::user()->getProjectRole($project->id) === $project::ROLE_ADMIN)
        <div class="modal fade" id="editBoardModal" data-bs-backdrop="static" tabindex="-1">
            <div class="modal-dialog">
                <form class="modal-content" id="editBoardForm" action="" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title" id="backDropModalTitle">Edit Board</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="edit-board-name" class="form-label">Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" id="edit-board-name"
                                    class="form-control @error('name', 'board') is-invalid @enderror"
                                    placeholder="Enter Name" required />
                                @error('name', 'board')
                                    <div class="form-text text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label for="board-sorting" class="form-label">Sorting</label>
                                <input type="text" name="sorting" value="{{ old('sorting') }}"
                                    id="edit-board-sorting"
                                    class="form-control @error('sorting', 'board') is-invalid @enderror"
                                    placeholder="Enter Sorting" required />
                                @error('sorting', 'board')
                                    <div class="form-text text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" value="1" id="close-board"
                                        name="close">
                                    <label class="form-check-label" for="close-board"> Close the board </label>
                                </div>
                                @error('name', 'board')
                                    <div class="form-text text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <div class="alert alert-secondary mt-3" role="alert">
                                    <span class="bx bx-bell"></span>
                                    Closed boards are not displayed in Project details page. But you can always check
                                    the boards in the
                                    Project's <a href="{{ route('board.history', $project->id) }}">Board History</a>
                                    Page
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    {{-- End Edit Board Modal --}}



    </div>
@endsection


@section('page-scripts')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
    <script src="https://cdn.datatables.net/rowreorder/1.4.1/js/dataTables.rowReorder.min.js"></script>

    <script src="https://cdn.ckeditor.com/ckeditor5/40.2.0/classic/ckeditor.js"></script>

    <script>
        // $(document).ready(function() {
        //     let table = $('.datatable').DataTable({
        //         fixedColumns: true,
        //         paging: false,
        //         scrollCollapse: true,
        //         scrollX: false,
        //         rowId: 'id',
        //         rowReorder: {
        //             dataSrc: 1
        //         },
        //         columnDefs: [{
        //             className: 'reorder',
        //             render: () => '≡',
        //         targets: 0
        //         }, {
        //             width: "200px",
        //             targets: 1
        //         }],
        //     });

        //     table.on('click', '', function() {
        //         let data = table.row(this).data();
        //         let taskId = table.row(this).id();
        //         taskId = parseInt(taskId.match(/\d+/)[0]);
        //         window.location.href = '{{ route('task.show') }}?id=' + taskId;
        //         // $.ajax({
        //         //   url: '{{ route('task.show') }}',
        //         //   data: {
        //         //     id: taskId,
        //         //   },
        //         //   success: function(result) {
        //         //     $("#edit-task-content").html(result);
        //         //     $('#editTaskModal').modal('show');
        //         //   }
        //         // });
        //    });

        //    table.on('row-reorder', function(e, diff, edit) {
        //        if (table.data('tabs')) {
        //            var rows = table.rows().data();
        //             var ord = new Array();
        //            for (var i = 0, ien = rows.length; i < ien; i++) {
        //                ord[i] = rows[i].DT_RowId;
        //            }
        //            console.log(ord, table.data('tabs'));
        //        }
        //    });
        // });

        ClassicEditor
            .create(document.querySelector('#editor'))
            .catch(error => {
                console.error(error);
            });
        ClassicEditor
            .create(document.querySelector('#editor1'))
            .catch(error => {
                console.error(error);
            });
    </script>

    <script>
        $(document).ready(function() {
            // $('#editTaskModal').modal('show');

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
            // End Open Add SUBTask Modal
            // Open Add subtask Modal
            $(document).on('click', '.add_subtask_btn', function(e) {
                e.preventDefault();
                var boardId = $(this).attr('data-board-id');
                $('#add-task-task-id').val(boardId);

                $('#addRoleModal').modal('show');
            });
            // End Open Add suTask Modal
            // Open Add subtask Modal
            $(document).on('click', '.show_subtask_btn', function(e) {
                e.preventDefault();

                var subtaskId = $(this).data('id');
                var title = $(this).data('title');
                var description = $(this).data('description');
                var status = $(this).data('status');
                var priority = $(this).data('priority');
                var type = $(this).data('type');
                var dueDate = $(this).data('due-date');
                var users = $(this).data('users'); // JSON string

                // Update modal content
                $('#show-subtask-id').text('Subtask Details: ' + title);
                $('#subtask-title').text(title);
                $('#subtask-description').html(description);
                $('#subtask-status').text(status);
                $('#subtask-priority').text(priority);
                $('#subtask-type').text(type);
                $('#subtask-due-date').text(dueDate);

                // Handle assigned users
                var userHtml = '';
                users.forEach(user => {
                    var avatar = user.avatar_url ? user.avatar_url :
                        'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name);
                    userHtml += `<div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2">
                            <img src="${avatar}" class="rounded-circle" height="30" width="30">
                        </div>
                        <p class="mb-0">${user.name}</p>
                    </div>`;
                });
                $('#subtask-assigned-users').html(userHtml);

                // Show modal
                $('#showsubtask').modal('show');
            });

            // End Open Add suTask Modal

            let subtaskEditor; // Define CKEditor instance globally

            // Initialize CKEditor 5
            ClassicEditor
                .create(document.querySelector('#subtask-descriptions'))
                .then(editor => {
                    subtaskEditor = editor; // Store the editor instance
                })
                .catch(error => {
                    console.error(error);
                });

            $(document).on('click', '.edit_subtask_btn', function() {
                let title = $(this).data('subtask-title');
                let description = $(this).data('subtask-descriptions');
                let status = $(this).data('subtask-status');
                let priority = $(this).data('subtask-priority');
                let type = $(this).data('subtask-type');
                let dueDate = $(this).data('subtask-due-date');
                let assignedTo = $(this).data('subtask-assigned-to');
                let updateRoute = $(this).data('subtask-update-route');

                $('#editSubtaskModal input[name="title"]').val(title);
                $('#editSubtaskModal select[name="status"]').val(status);
                $('#editSubtaskModal select[name="priority"]').val(priority);
                $('#editSubtaskModal select[name="type"]').val(type);
                $('#editSubtaskModal input[name="due_date"]').val(dueDate);
                $('#editSubtaskModal select[name="assigned_to"]').val(assignedTo);
                $('#editSubtaskModal form').attr('action', updateRoute);

                // Set data in CKEditor 5
                if (subtaskEditor) {
                    subtaskEditor.setData(description);
                }
                

                $('#editSubtaskModal').modal('show');
            });



            // delete subtask model
            $(document).on('click', '.delete-subtask-btn', function(e) {
                e.preventDefault();
                let form = $(this).closest('form');

                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });





        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Add event listener to toggle subtasks
            document.querySelectorAll(".toggle-subtasks").forEach(function(button) {
                button.addEventListener("click", function() {
                    const taskId = this.getAttribute("data-task-id");
                    const subtaskRow = document.querySelector(
                        `.subtasks-row[data-task-id="${taskId}"]`);

                    if (subtaskRow) {
                        // Toggle visibility of subtasks row
                        subtaskRow.classList.toggle("d-none");

                        // Change the button icon
                        const icon = this.querySelector("i");
                        if (subtaskRow.classList.contains("d-none")) {
                            icon.classList.remove("bx-minus-circle");
                            icon.classList.add("bx-plus-circle");
                        } else {
                            icon.classList.remove("bx-plus-circle");
                            icon.classList.add("bx-minus-circle");
                        }
                    }
                });
            });
        });
    </script>
@endsection
