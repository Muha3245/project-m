@extends('layouts.app')

@section('page-styles')
    <style>
        img.rounded-circle {
            object-fit: cover;
            width: 40px;
            height: 40px;
        }

        .chat-window {
            height: 400px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 20px;
            scroll-behavior: smooth;
        }

        .message-content {
            border-radius: 15px;
            padding: 8px 15px;
            margin-bottom: 4px;
            word-wrap: break-word;
            max-width: 70%;
            display: inline-block;
        }

        .message-content.bg-primary {
            background-color: #6c63ff !important;
        }

        .message-content.bg-light {
            background-color: white !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .chat-actions {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .group-list a {
            text-decoration: none;
            font-weight: bold;
            color: #000;
        }

        .group-list a:hover {
            color: #007bff;
        }

        .notification {
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
        }

        .text-muted {
            font-size: 0.75rem;
            color: #6c757d !important;
        }

        .btn-sm.text-danger {
            padding: 0;
            background: none;
            border: none;
        }

        .btn-sm.text-danger:hover {
            color: #dc3545 !important;
        }

        .participant-container {
            position: relative;
            width: 280px;
            margin: 10px;
            border-radius: 12px;
            overflow: hidden;
            background: #2c2c2c;
            transition: all 0.3s ease;
        }

        .participant-container.speaking {
            border: 2px solid #4CAF50;
            box-shadow: 0 0 15px rgba(76, 175, 80, 0.5);
        }

        .video-wrapper {
            position: relative;
            aspect-ratio: 16/9;
            background: #1a1a1a;
        }

        .video-wrapper video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .user-avatar {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: none; /* Hidden by default, shown when video is off */
        }

        .participant-name {
            padding: 8px;
            color: white;
            font-size: 14px;
            text-align: center;
            background: rgba(0, 0, 0, 0.5);
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        .speaking-indicator {
            display: none;
            margin-left: 5px;
        }

        .speaking .speaking-indicator {
            display: inline;
        }

        .call-controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
        }

        .call-controls button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin: 0 5px;
            transition: all 0.3s ease;
        }

        .call-controls button i {
            font-size: 20px;
        }

        .call-controls button.muted {
            background-color: #dc3545;
            color: white;
        }

        /* Status indicators */
        .status-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            background: rgba(0, 0, 0, 0.5);
            color: white;
        }
    </style>
@endsection

@section('page-content')
    <div class="container">
        <div class="row">
            <!-- Sidebar: Groups -->
            <div class="col-md-4">
                <h4>Groups</h4>
                <ul class="list-group">
                    @forelse ($groups as $group)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @if ($group->users->contains(auth()->id()))
                                <a href="{{ route('groups.show', $group->id) }}" class="text-decoration-none flex-grow-1">
                                    {{ $group->name }}
                                </a>
                                {{-- <button class="btn btn-sm btn-primary me-2" data-bs-toggle="modal"
                                    data-bs-target="#manageGroupModal-{{ $group->id }}">
                                    <i class="bx bx-cog"></i> Manage
                                </button> --}}
                            @endif
                            @if ($group->created_by === auth()->id())
                                <div>
                                    <button class="btn btn-sm btn-primary me-2" data-bs-toggle="modal"
                                        data-bs-target="#manageGroupModal-{{ $group->id }}">
                                        <i class="bx bx-cog"></i> Manage
                                    </button>
                                    <form action="{{ route('groups.remove', $group) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this group?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item">You are not in any group.</li>
                    @endforelse
                </ul>
                <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                    Create Group
                </button>
            </div>

            <!-- Chat Window -->
            <div class="col-md-8">
                <h4>Chat Window</h4>
                <div id="notification-container"
                    style="position: fixed; top: 20px; right: 20px; width: 300px; z-index: 9999;">
                    <!-- Notifications will be dynamically inserted here -->
                </div>

                @if ($group)
                    <div class="border p-2 bg-light mb-3">
                        <span class="text-primary">{{ $group->name }}</span>
                        <div class="d-flex gap-2">
                            <!-- Group Audio Call Button -->
                            <i class="bx bx-phone call-icon" style="cursor: pointer; font-size: 25px; color: #696cff;"
                                data-type="audio" data-group-id="{{ $group->id }}"></i>

                            <!-- Group Video Call Button -->
                            <i class="bx bx-video video-icon" style="cursor: pointer; font-size: 25px; color: #696cff;"
                                data-type="video" data-group-id="{{ $group->id }}"></i>
                        </div>
                    </div>

                    <div id="chatWindow" class="border p-3 chat-window">
                        @if ($messages->isNotEmpty())
                            @foreach ($messages as $message)
                                <div class="mb-3">
                                    <div
                                        class="d-flex {{ $message->user_id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
                                        @if ($message->user_id !== auth()->id())
                                            <img src="{{ $message->user->getFirstMediaUrl('default', 'preview') ?: 'https://ui-avatars.com/api/?name=' . urlencode($message->user->name) }}"
                                                alt="user-avatar" class="rounded-circle me-2" height="40" width="40">
                                        @endif
                                        <div>
                                            <div
                                                class="message-content {{ $message->user_id === auth()->id() ? 'bg-primary' : 'bg-light' }}">
                                                {{ $message->message }}
                                            </div>
                                            @if ($message->file_path)
                                                @if (in_array(pathinfo($message->file_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
                                                    <p class="image-message">
                                                        <img src="{{ asset('storage/' . $message->file_path) }}"
                                                            alt="Image" style="max-width:150px; border-radius:5px;">
                                                        <i class="fas fa-file"></i>
                                                        <a href="{{ asset($message->file_path) }}"
                                                            target="_blank">Download</a>
                                                    </p>
                                                @endif
                                            @endif

                                            @if ($message->voice_message_path)
                                                <p class="voice-message">
                                                    <i class="fas fa-microphone"></i>
                                                    <audio controls>
                                                        <source
                                                            src="{{ asset('storage/' . $message->voice_message_path) }}"
                                                            type="audio/mpeg">
                                                        Your browser does not support audio playback.
                                                    </audio>
                                                </p>
                                            @endif
                                            <small class="text-muted">
                                                {{ $message->created_at->format('d M Y, h:i A') }}
                                                @if ($message->user_id === auth()->id())
                                                    <form method="POST"
                                                        action="{{ route('groups.messages.delete', ['group' => $group->id, 'message' => $message->id]) }}"
                                                        class="d-inline ms-2">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm text-danger p-0"
                                                            onclick="return confirm('Delete this message?')">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted">No messages yet.</p>
                        @endif
                    </div>

                    <form method="POST" id="messageForm" action="{{ route('groups.sendMessage', $group->id) }}"
                        class="mt-3" enctype="multipart/form-data">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type a message...">
                            <label for="fileInput" class="btn btn-outline-secondary">
                                <i class="bx bx-image"></i>
                            </label>
                            <input type="file" id="fileInput" name="file" hidden>
                            <label id="recordButton" class="btn btn-outline-secondary">
                                <i class="bx bx-microphone"></i>
                            </label>
                            <audio id="audioPreview" controls style="display: none;" class="me-2"></audio>
                            <input type="hidden" id="voiceInput" name="voice">
                            <img id="imagePreview" class="preview-image" style="display: none;">
                            <button class="btn btn-primary" type="submit">Send</button>
                        </div>
                    </form>
                @else
                    <div class="border p-3 chat-window text-center text-muted">
                        Select a group to start messaging
                    </div>
                @endif
            </div>

            <!-- Create Group Modal -->
            <div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('groups.create') }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="createGroupModalLabel">Create Group</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="groupName" class="form-label">Group Name</label>
                                    <input type="text" class="form-control" id="groupName" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="users" class="form-label">Add Users</label>
                                    <select class="form-select" name="user_ids[]" id="users" multiple required>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Create Group</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Manage Group Modals -->
            @foreach ($groups as $group)
                <div class="modal fade" id="manageGroupModal-{{ $group->id }}" tabindex="-1"
                    aria-labelledby="manageGroupModalLabel-{{ $group->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="manageGroupModalLabel-{{ $group->id }}">Manage Group:
                                    {{ $group->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <h6>Group Members</h6>
                                <div class="mb-3">
                                    <input type="text" class="form-control"
                                        id="groupMembersSearch-{{ $group->id }}"
                                        placeholder="Search group members...">
                                </div>
                                <ul class="list-group" id="groupMembersList-{{ $group->id }}">
                                    @foreach ($group->users as $user)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $user->name }}
                                            @if ($user->id !== $group->created_by)
                                                <form method="POST"
                                                    action="{{ route('group.removeUser', ['group' => $group->id, 'user' => $user->id]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                                </form>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>

                                <h6 class="mt-4">Add Users</h6>
                                <div class="mb-3">
                                    <input type="text" class="form-control" id="addUsersSearch-{{ $group->id }}"
                                        placeholder="Search users to add...">
                                </div>
                                <form method="POST" action="{{ route('groups.addUsers', $group->id) }}">
                                    @csrf
                                    <select class="form-select mb-3" name="user_ids[]"
                                        id="addUsersList-{{ $group->id }}" multiple required>
                                        @foreach ($users as $user)
                                            @if (!$group->users->contains($user->id))
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary">Add Users</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Add this modal for group call -->
            <div class="modal fade" id="groupCallModal" tabindex="-1" aria-labelledby="groupCallModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content bg-dark text-white">
                        <div class="modal-header border-0">
                            <h5 class="modal-title" id="groupCallModalLabel">Group Call</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="video-grid" class="d-flex flex-wrap justify-content-center gap-3 p-3">
                                <!-- Local user container -->
                                <div class="participant-container" id="local-container">
                                    <div class="video-wrapper">
                                        <video id="localVideo" autoplay muted playsinline></video>
                                        <img src="{{ auth()->user()->getFirstMediaUrl('default', 'preview') ?: 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}" 
                                             class="user-avatar" alt="Your avatar">
                                    </div>
                                    <div class="participant-name">
                                        You ({{ auth()->user()->name }})
                                        <span class="speaking-indicator">🎤</span>
                                    </div>
                                </div>
                                
                                <!-- Remote participants will be added here dynamically -->
                                <div id="remote-container"></div>
                            </div>

                            <div class="call-controls text-center mt-4">
                                <button id="toggleVideo" class="btn btn-light rounded-circle mx-2">
                                    <i class="bx bx-video"></i>
                                </button>
                                <button id="toggleAudio" class="btn btn-light rounded-circle mx-2">
                                    <i class="bx bx-microphone"></i>
                                </button>
                                <button id="endCall" class="btn btn-danger rounded-circle mx-2">
                                    <i class="bx bx-phone-off"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <script src="https://js.pusher.com/8.3.0/pusher.min.js"></script>
    <script>
        $(document).ready(function() {
            let mediaRecorder;
            let audioChunks = [];
            let audioBlob;

            $("#fileInput").change(function(event) {
                const file = event.target.files[0];
                if (file && file.type.startsWith("image/")) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $("#imagePreview").attr("src", e.target.result).show();
                    };
                    reader.readAsDataURL(file);
                }
            });

            $("#recordButton").click(async function() {
                if (!mediaRecorder || mediaRecorder.state === "inactive") {
                    let stream = await navigator.mediaDevices.getUserMedia({
                        audio: true
                    });
                    mediaRecorder = new MediaRecorder(stream, {
                        mimeType: "audio/webm"
                    });

                    mediaRecorder.start();
                    $("#recordButton").html('<i class="bx bx-stop"></i> Stop Recording');

                    mediaRecorder.ondataavailable = function(event) {
                        audioChunks.push(event.data);
                    };

                    mediaRecorder.onstop = function() {
                        audioBlob = new Blob(audioChunks, {
                            type: "audio/webm"
                        });
                        let audioUrl = URL.createObjectURL(audioBlob);
                        $("#audioPreview").attr("src", audioUrl).show();
                        $("#voiceInput").val(audioBlob);
                    };
                } else {
                    mediaRecorder.stop();
                    $("#recordButton").html('<i class="bx bx-microphone"></i> Record Voice');
                }
            });

            $("#messageForm").submit(function(event) {
                event.preventDefault();
                
                const messageInput = $(this).find('input[name="message"]');
                const message = messageInput.val().trim();
                const hasFile = $("#fileInput")[0].files.length > 0;
                const hasVoice = audioBlob != null;

                // Check if there's any content to send
                if (!message && !hasFile && !hasVoice) {
                    return false;
                }
                
                let formData = new FormData(this);
                if (hasFile) {
                    formData.append("file", $("#fileInput")[0].files[0]);
                }

                if (hasVoice) {
                    let audioFile = new File([audioBlob], "voice_message.webm", {
                        type: "audio/webm"
                    });
                    formData.append("voice", audioFile);
                }

                // Disable the form while sending
                const submitButton = $(this).find('button[type="submit"]');
                submitButton.prop('disabled', true);

                $.ajax({
                    url: $(this).attr('action'),
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Reset the form on successful send
                        $("#messageForm")[0].reset();
                        $("#audioPreview").hide();
                        $("#imagePreview").hide();
                        audioChunks = [];
                        audioBlob = null;
                    },
                    error: function(xhr) {
                        console.error("Error sending message:", xhr.responseText);
                    },
                    complete: function() {
                        // Re-enable the form
                        submitButton.prop('disabled', false);
                    }
                });
            });

            // Pusher for Real-Time Group Chat
            const pusher = new Pusher("8f668e9eccd5774f8ab5", {
                cluster: "ap2",
                forceTLS: true
            });

            const groupId = {{ $group->id ?? 'null' }};

            if (groupId) {
                const channel = pusher.subscribe('group.{{ $group->id }}');

                channel.bind("GroupMessageSenting", (data) => {
                    console.log('Received message data:', data); // For debugging
                    
                    const chatWindow = document.getElementById("chatWindow");
                    if (!chatWindow) return;

                    const isCurrentUser = data.user_id === {{ auth()->id() }};
                    
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'mb-3';
                    
                    const messageContent = `
                        <div class="d-flex ${isCurrentUser ? 'justify-content-end' : 'justify-content-start'}">
                            ${!isCurrentUser ? `
                                <img src="${data.user.avatar}" 
                                    alt="user-avatar" class="rounded-circle me-2" height="40" width="40">
                            ` : ''}
                            <div>
                                <div class="message-content ${isCurrentUser ? 'bg-primary text-white' : 'bg-light'}">
                                    ${data.message || ''}
                                </div>
                                ${data.file_path ? `
                                    <div class="mt-2">
                                        <img src="${data.file_path}" alt="Attached file" style="max-width: 200px; border-radius: 5px;">
                                    </div>
                                ` : ''}
                                ${data.voice_message_path ? `
                                    <div class="mt-2">
                                        <audio controls>
                                            <source src="${data.voice_message_path}" type="audio/mpeg">
                                        </audio>
                                    </div>
                                ` : ''}
                                <div class="d-flex align-items-center ${isCurrentUser ? 'justify-content-end' : 'justify-content-start'}">
                                    <small class="text-muted">
                                        ${new Date(data.created_at).toLocaleString('en-US', {
                                            hour: 'numeric',
                                            minute: 'numeric',
                                            hour12: true,
                                            day: 'numeric',
                                            month: 'short',
                                            year: 'numeric'
                                        })}
                                    </small>
                                    ${isCurrentUser ? `
                                        <form method="POST" action="/groups/${data.groupId}/messages/${data.id}" class="d-inline ms-2">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm text-danger p-0" onclick="return confirm('Delete this message?')">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;

                    messageDiv.innerHTML = messageContent;
                    chatWindow.appendChild(messageDiv);
                    chatWindow.scrollTop = chatWindow.scrollHeight;
                });
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            // Handle click events for group call buttons
            $(document).on("click", ".call-icon, .video-icon", function() {
                // Get the call type (audio or video) and group ID from the button's data attributes
                var callType = $(this).data("type"); // "audio" or "video"
                var groupId = $(this).data("group-id"); // Group ID

                // Validate the group ID
                if (!groupId || isNaN(groupId)) {
                    alert("Error: Invalid group ID.");
                    return;
                }

                console.log("Initiating group call:", callType, "for group:", groupId);

                // Send an AJAX request to initiate the group call
                $.ajax({
                    url: '/initiate-group-call', // Route to handle group call initiation
                    type: 'POST',
                    data: {
                        group_id: groupId,
                        call_type: callType,
                        _token: $('meta[name="csrf-token"]').attr('content') // CSRF token
                    },
                    success: function(response) {
                        console.log('Group call initiated:', response);

                        // Redirect to the group call page
                        window.location.href = "/group-call/" + groupId + "/" + callType;
                    },
                    error: function(xhr) {
                        console.error('Error initiating group call:', xhr.responseText);
                        alert('Failed to initiate the group call. Please try again.');
                    }
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Function to fetch notifications
            function fetchNotifications() {
                $.ajax({
                    url: '/fetch-notifications', // Route to fetch notifications
                    type: 'GET',
                    success: function(response) {
                        console.log('Notifications fetched:', response);

                        // Clear existing notifications
                        $('#notification-container').empty();

                        // Display new notifications
                        response.forEach(function(notification) {
                            let notificationData = notification.data;

                            // Create notification HTML
                            let notificationHtml = `
                    <div class="notification" style="background: #fff; padding: 10px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 10px;">
                        <strong>${notificationData.message}</strong>
                        <br>
                        <small>Call Type: ${notificationData.call_type}</small>
                        <div class="mt-2">
                            <button onclick="acceptGroupCall(${notificationData.sender_id}, ${notificationData.group_id}, '${notificationData.call_type}', '${notification.id}')" class="btn btn-sm btn-success">Accept</button>
                            <button onclick="dismissNotification('${notification.id}')" class="btn btn-sm btn-danger">Decline</button>
                        </div>
                    </div>
                `;

                            // Append notification to the container
                            $('#notification-container').append(notificationHtml);
                        });
                    },
                    error: function(xhr) {
                        console.error('Error fetching notifications:', xhr.responseText);
                    }
                });
            }

            // Fetch notifications every 5 seconds
            setInterval(fetchNotifications, 5000);

            // Initial fetch
            fetchNotifications();
        });

        // Function to accept a group call
        function acceptGroupCall(senderId, groupId, callType, notificationId) {
            console.log('Accepting group call from:', senderId, 'for group:', groupId);

            // Mark the notification as read
            $.ajax({
                url: '/mark-notification-read',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
                    notification_id: notificationId
                },
                success: function(response) {
                    console.log('Notification marked as read:', response);

                    // Redirect to the group call page using group_id
                    window.location.href = "/group-call/" + groupId + "/" + callType;
                },
                error: function(xhr) {
                    console.error('Error marking notification as read:', xhr.responseText);
                }
            });
        }

        // Function to dismiss a notification
        function dismissNotification(notificationId) {
            $.ajax({
                url: '/mark-notification-read',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
                    notification_id: notificationId
                },
                success: function(response) {
                    console.log('Notification dismissed:', response);

                    // Remove the notification from the UI
                    $(`#notification-${notificationId}`).remove();
                },
                error: function(xhr) {
                    console.error('Error dismissing notification:', xhr.responseText);
                }
            });
        }
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @foreach ($groups as $group)
                const groupMembersSearch{{ $group->id }} = document.getElementById(
                    'groupMembersSearch-{{ $group->id }}');
                const groupMembersList{{ $group->id }} = document.getElementById(
                    'groupMembersList-{{ $group->id }}');

                groupMembersSearch{{ $group->id }}.addEventListener('input', function() {
                    const searchValue = groupMembersSearch{{ $group->id }}.value.toLowerCase();
                    Array.from(groupMembersList{{ $group->id }}.children).forEach(member => {
                        const memberName = member.textContent.toLowerCase();
                        member.style.display = memberName.includes(searchValue) ? '' : 'none';
                    });
                });

                const addUsersSearch{{ $group->id }} = document.getElementById(
                    'addUsersSearch-{{ $group->id }}');
                const addUsersList{{ $group->id }} = document.getElementById(
                    'addUsersList-{{ $group->id }}');

                addUsersSearch{{ $group->id }}.addEventListener('input', function() {
                    const searchValue = addUsersSearch{{ $group->id }}.value.toLowerCase();
                    Array.from(addUsersList{{ $group->id }}.options).forEach(option => {
                        const userName = option.textContent.toLowerCase();
                        option.style.display = userName.includes(searchValue) ? '' : 'none';
                    });
                });
            @endforeach
        });
    </script>

    <script>
    let localStream = null;
    let audioContext = null;
    let audioAnalyser = null;
    let isSpeaking = false;
    let videoEnabled = true;
    let audioEnabled = true;

    // Initialize audio context for speech detection
    function initAudioAnalysis(stream) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        audioAnalyser = audioContext.createAnalyser();
        const microphone = audioContext.createMediaStreamSource(stream);
        microphone.connect(audioAnalyser);
        audioAnalyser.fftSize = 512;
        const dataArray = new Uint8Array(audioAnalyser.frequencyBinCount);
        
        function checkAudioLevel() {
            audioAnalyser.getByteFrequencyData(dataArray);
            const audioLevel = dataArray.reduce((a, b) => a + b) / dataArray.length;
            
            if (audioLevel > 30 && !isSpeaking) {
                isSpeaking = true;
                document.getElementById('local-container').classList.add('speaking');
            } else if (audioLevel <= 30 && isSpeaking) {
                isSpeaking = false;
                document.getElementById('local-container').classList.remove('speaking');
            }
            
            requestAnimationFrame(checkAudioLevel);
        }
        
        checkAudioLevel();
    }

    // Toggle video
    document.getElementById('toggleVideo').addEventListener('click', () => {
        if (localStream) {
            const videoTrack = localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoEnabled = !videoEnabled;
                videoTrack.enabled = videoEnabled;
                const videoElement = document.getElementById('localVideo');
                const avatarElement = document.querySelector('#local-container .user-avatar');
                
                if (videoEnabled) {
                    videoElement.style.display = 'block';
                    avatarElement.style.display = 'none';
                    document.getElementById('toggleVideo').classList.remove('muted');
                } else {
                    videoElement.style.display = 'none';
                    avatarElement.style.display = 'block';
                    document.getElementById('toggleVideo').classList.add('muted');
                }
            }
        }
    });

    // Toggle audio
    document.getElementById('toggleAudio').addEventListener('click', () => {
        if (localStream) {
            const audioTrack = localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioEnabled = !audioEnabled;
                audioTrack.enabled = audioEnabled;
                document.getElementById('toggleAudio').classList.toggle('muted', !audioEnabled);
            }
        }
    });

    // Create remote participant container
    function createRemoteParticipantContainer(userId, userName, avatarUrl) {
        const container = document.createElement('div');
        container.className = 'participant-container';
        container.id = `participant-${userId}`;
        
        container.innerHTML = `
            <div class="video-wrapper">
                <video id="video-${userId}" autoplay playsinline></video>
                <img src="${avatarUrl}" class="user-avatar" alt="${userName}'s avatar">
            </div>
            <div class="participant-name">
                ${userName}
                <span class="speaking-indicator">🎤</span>
            </div>
        `;
        
        document.getElementById('remote-container').appendChild(container);
        return container;
    }

    // Start group call
    async function startGroupCall() {
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            const localVideo = document.getElementById('localVideo');
            localVideo.srcObject = localStream;
            
            // Initialize audio analysis for speaking detection
            initAudioAnalysis(localStream);
            
            // Show the call modal
            const modal = new bootstrap.Modal(document.getElementById('groupCallModal'));
            modal.show();
            
            // Initialize peer connections here
            // ... Your existing peer connection code ...
            
        } catch (err) {
            console.error('Error accessing media devices:', err);
        }
    }

    // End call
    document.getElementById('endCall').addEventListener('click', () => {
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
        }
        // Close peer connections
        // ... Your existing peer connection closing code ...
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('groupCallModal'));
        modal.hide();
    });
    </script>
@endsection
