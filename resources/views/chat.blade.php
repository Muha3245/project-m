@extends('layouts.app')

@section('page-styles')
    <style>
        img.rounded-circle {
            object-fit: cover;
        }

        /* Chat window enhancements */
        .chat-window {
            height: 450px;
            overflow-y: auto;
            border-radius: 10px;
            background: #f8f9fa;
            padding: 15px;
        }

        /* Message bubbles */
        .message-content {
            padding: 10px 14px;
            border-radius: 10px;
            max-width: 75%;
            font-size: 14px;
            word-wrap: break-word;
            display: inline-block;
        }

        .message-content.bg-primary {
            color: white;
            background-color: #2a629b;
            border-radius: 10px 10px 0 10px;
        }

        .message-content.bg-light {
            background-color: #e9ecef;
            border-radius: 10px 10px 10px 0;
        }

        /* Image message styling */
        .image-message img {
            max-width: 120px;
            border-radius: 8px;
            margin-top: 5px;
            display: block;
        }

        /* Voice message styling */
        .voice-message audio {
            width: 180px;
            margin-top: 5px;
        }

        /* Chat input area */
        .input-group {
            display: flex;
            align-items: center;
        }

        .input-group input {
            border-radius: 8px;
        }

        .input-group .btn {
            border-radius: 8px;
            padding: 8px 12px;
        }

        #audioPreview {
            margin-left: 20px;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .preview-image {
            max-width: 80px;
            margin-top: -150px;
            border-radius: 5px;
            display: flex;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .message-content {
                max-width: 90%;
            }

            .chat-window {
                height: 400px;
                padding: 10px;
            }
        }
    </style>
@endsection

@section('page-content')
    <div class="container">
        <div class="row">
            <!-- Chat Sidebar -->
            <div class="col-md-4">
                <h4>Chats</h4>
                <div class="form-group mb-3">
                    <input type="text" name="search" id="search" placeholder="Search users..." class="form-control"
                        onfocus="this.placeholder=''">
                </div>
                <div id="search_list"></div>

                <ul class="list-group mb-3" id="userList">
                    <li class="list-group-item"><strong>Users</strong></li>
                    @foreach ($users as $user)
                        <li class="list-group-item d-flex justify-content-between align-items-center user-item"
                            data-user-name="{{ strtolower($user->name) }}">
                            <a href="{{ route('one-to-one.index', ['to_id' => $user->id]) }}"
                                class="text-decoration-none flex-grow-1">
                                <img src="{{ $user->getFirstMediaUrl('default', 'preview') ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                                    alt="user-avatar" class="rounded-circle me-2" height="30" width="30">
                                <span class="user-name">{{ $user->name }}</span>
                            </a>
                            @if ($user->unread_messages_count > 0)
                                <span class="badge bg-danger rounded-pill">{{ $user->unread_messages_count }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <!-- Group Chats -->
                <h4>Groups</h4>
                <div class="mb-3">
                    <input type="text" id="groupSearch" class="form-control" placeholder="Search Groups...">
                </div>
                <ul class="list-group mb-3" id="groupList">
                    @foreach ($groups as $group)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @foreach ($group->users as $user)
                                @if ($user->id === auth()->id())
                                    <a href="{{ route('groups.show', $group->id) }}"
                                        class="text-decoration-none flex-grow-1">
                                        {{ $group->name }}
                                    </a>
                                @endif
                            @endforeach

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
                    @endforeach
                </ul>

                <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                    Create Group
                </button>
            </div>

            <!-- Chat Window -->
            <div class="col-md-8">
                <h4>Chat Window</h4>

                @if ($activeChat)
                    <div class="border p-2 bg-light mb-3 d-flex justify-content-between align-items-center">
                        <span class="text-primary">{{ $activeChat->name }}</span>
                        <div class="d-flex gap-2">
                            <i class="bx bx-phone call-icon" style="cursor: pointer; font-size: 25px; color: #696cff;"
                                data-type="audio" data-receiver-id="{{ $activeChat->id }}"></i>

                            <i class="bx bx-video video-icon" style="cursor: pointer; font-size: 25px; color: #696cff;"
                                data-type="video" data-receiver-id="{{ $activeChat->id }}"></i>
                        </div>
                        
                    </div>

                    <div class="border p-3 chat-window" id="chatWindow">
                        @if ($messages->isNotEmpty())
                            @foreach ($messages as $message)
                                <div class="mb-3">


                                    <div
                                        class="d-flex {{ $message->from_id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
                                        @if ($message->from_id !== auth()->id())
                                            <img src="{{ $message->from->getFirstMediaUrl('default', 'preview') ?: 'https://ui-avatars.com/api/?name=' . urlencode($message->from->name) }}"
                                                alt="user-avatar" class="rounded-circle me-2" height="40" width="40">
                                        @endif
                                        <div>
                                            <div
                                                class="message-content {{ $message->from_id === auth()->id() ? 'bg-primary' : 'bg-light' }}">
                                                {{ $message->message }}
                                            </div>
                                            @if ($message->file_path)
                                                @if (in_array(pathinfo($message->file_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
                                                    <p class="image-message">
                                                        <img src="{{ asset('storage/' . $message->file_path) }}"
                                                            alt="Image" style="max-width:150px; border-radius:5px;">
                                                        <i class="fas fa-file"></i>
                                                        <a href="{{ asset('storage/' . $message->file_path) }}"
                                                            target="_blank">Download</a>
                                                    </p>
                                                    <p class="file-message">

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
                                                @if ($message->from_id === auth()->id())
                                                    <form method="POST"
                                                        action="{{ route('message.delete', $message->id) }}"
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

                    <form method="POST" id="messageForm"action="{{ route('one-to-one.send') }}" class="mt-3"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="input-group">
                            <input type="hidden" name="to_id" value="{{ request('to_id') }}">
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
                {{-- @elseif (!empty($group))
                    <div class="border p-2 bg-light mb-3">
                        <span class="text-primary"></span>
                    </div>

                    <div id="chatWindow" class="border p-3 chat-window">
                        @if (!empty($messages))
                            @foreach ($messages as $message)
                                <div
                                    class="d-flex {{ $message->user_id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                                    @if ($message->user_id !== auth()->id())
                                        <img src="{{ $message->user->getFirstMediaUrl('default', 'preview') ?: 'https://ui-avatars.com/api/?name=' . urlencode($message->user->name) }}"
                                            class="rounded-circle me-2" alt="{{ $message->user->name }}" height="40"
                                            width="40">
                                    @endif
                                    <div>
                                        <div
                                            class="message-content {{ $message->user_id === auth()->id() ? 'bg-primary' : 'bg-light' }}">
                                            {{ $message->message }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $message->user->name }} •
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
                            @endforeach
                        @else
                            <p class="text-muted">No messages yet.</p>
                        @endif
                    </div> --}}

                    {{-- <form method="POST" action="{{ route('groups.sendMessage', $group->id) }}" class="mt-3">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type a message..."
                                required>
                            <button class="btn btn-primary" type="submit">Send</button>
                        </div>
                    </form> --}}
                @else
                    <div class="border p-3 chat-window text-center text-muted">
                        Select a chat or group to start messaging
                    </div>
                @endif
            </div>
        </div>
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                    @if ($user->id !== auth()->id())
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create</button>
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
                        <h5 class="modal-title" id="manageGroupModalLabel-{{ $group->id }}">
                            Manage Group: {{ $group->name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6>Group Members</h6>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="groupMembersSearch-{{ $group->id }}"
                                placeholder="Search group members...">
                        </div>
                        <ul class="list-group mb-4" id="groupMembersList-{{ $group->id }}">
                            @foreach ($group->users as $user)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $user->name }}
                                    @if ($user->id !== $group->created_by)
                                        <form method="POST"
                                            action="{{ route('group.removeUser', ['group' => $group->id, 'user' => $user->id]) }}"
                                            onsubmit="return confirm('Remove this user from the group?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        <h6>Add Users</h6>
                        <form method="POST" action="{{ route('groups.addUsers', $group->id) }}">
                            @csrf
                            <div class="mb-3">
                                <input type="text" class="form-control" id="addUsersSearch-{{ $group->id }}"
                                    placeholder="Search users to add...">
                            </div>
                            <select class="form-select mb-3" name="user_ids[]" id="addUsersList-{{ $group->id }}"
                                multiple required>
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
@endsection

@section('page-scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <script src="https://js.pusher.com/8.3.0/pusher.min.js"></script>

    <script>
        $(document).ready(function() {
            // User Search
            $('#search').on('keyup', function() {
                const query = $(this).val().toLowerCase();
                $('.user-item').each(function() {
                    const userName = $(this).data('user-name');
                    $(this).toggle(userName.includes(query));
                });
            });

            // Group Search
            $('#groupSearch').on('keyup', function() {
                const query = $(this).val().toLowerCase();
                $('#groupList li').each(function() {
                    const groupName = $(this).find('a').text().toLowerCase().trim();
                    $(this).toggle(groupName.includes(query));
                });
            });

            // Media Handling
            let mediaRecorder;
            let audioChunks = [];

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
                    $("#recordButton").html('<i class="fas fa-stop"></i> Stop Recording');

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
                    mediaRecorder.stop(); // Stop recording
                    $("#recordButton").html('<i class="fas fa-microphone"></i> Record Voice');
                }
            });
        });
        $("#messageForm").submit(function(event) {
            // event.preventDefault();

            let formData = new FormData(this);

            // If an image is selected, append the image to formData
            let imageInput = $("#fileInput")[0].files[0];
            if (imageInput) formData.append("image", imageInput);

            if (audioBlob) {
                let audioFile = new File([audioBlob], "voice_message.webm", {
                    type: "audio/webm"
                });
                formData.append("voice", audioFile);
            }

            $.ajax({
                url: "{{ route('one-to-one.send') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $("#messageInput").val("");
                    $("#fileInput").val("");
                    $("#voiceInput").val("");
                    $("#audioPreview").hide();
                    $("#imageInput").val("");
                    $("#imagePreview").hide();
                    audioChunks = [];
                },
                error: function(xhr) {
                    console.error("Error sending message:", xhr.responseText);
                }
            });
        });
    </script>

    <script type="module">
        const pusher = new Pusher("8f668e9eccd5774f8ab5", {
            cluster: "ap2",
            forceTLS: true
        });

        const channel = pusher.subscribe("chat.{{ auth()->id() }}");
        const authUserId = {{ auth()->id() }};

        channel.bind("message.sent", (data) => {
            const chatWindow = document.getElementById("chatWindow");

            if (chatWindow) {
                let fileHtml = "";
                let voiceHtml = "";
                let imageHtml = "";

                // Handle file message
                if (data.message.file_path) {
                    let fileExt = data.message.file_path.split('.').pop().toLowerCase();
                    let fileIcon = '<i class="fas fa-file"></i>';

                    if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                        fileIcon =
                            `<img src="${data.message.file_path}" style="max-width:100px; height:100px; border-radius:5px;">`;
                    }

                    fileHtml = `
                    <p class="file-message">
                        ${fileIcon} <a href="${data.message.file_path}" target="_blank">Download</a>
                    </p>`;
                }

                // Handle voice message
                if (data.message.voice_message_path) {
                    voiceHtml = `
                    <p class="voice-message">
                        <i class="fas fa-microphone"></i>
                        <audio controls>
                            <source src="${data.message.voice_message_path}" type="audio/mpeg">
                        </audio>
                    </p>`;
                }

                // Handle image message (If separate image key exists)
                if (data.message.image_path) {
                    imageHtml = `
                    <p class="image-message">
                        <img src="${data.message.image_path}" style="max-width:100%; border-radius:5px;">
                    </p>`;
                }

                // Final Message HTML
                const messageHtml = `
                <div class="d-flex ${data.message.from_id === authUserId ? 'justify-content-end' : 'justify-content-start'} mb-3">
                    ${data.message.from_id !== authUserId ? `
                                            <img src="${data.message.user.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(data.message.user.name)}" 
                                                alt="user-avatar" class="rounded-circle me-2" height="40" width="40">
                                        ` : ''}
                    <div>
                        <div class="message-content ${data.message.from_id === authUserId ? 'bg-primary text-white' : 'bg-light'} p-2 rounded">
                            ${data.message.message || ""}
                            ${fileHtml}
                            ${voiceHtml}
                            ${imageHtml}
                        </div>
                        <small class="text-muted">${data.message.created_at}</small>
                    </div>
                </div>`;

                // Append message to chat window
                chatWindow.insertAdjacentHTML('beforeend', messageHtml);
                chatWindow.scrollTop = chatWindow.scrollHeight;
            }
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @foreach ($groups as $group)
                // Group Members Search
                const groupMembersSearch{{ $group->id }} = document.getElementById(
                    'groupMembersSearch-{{ $group->id }}');
                const groupMembersList{{ $group->id }} = document.getElementById(
                    'groupMembersList-{{ $group->id }}');

                groupMembersSearch{{ $group->id }}.addEventListener('input', function() {
                    const searchValue = this.value.toLowerCase();
                    Array.from(groupMembersList{{ $group->id }}.children).forEach(member => {
                        const memberName = member.textContent.toLowerCase();
                        member.style.display = memberName.includes(searchValue) ? '' : 'none';
                    });
                });

                // Add Users Search
                const addUsersSearch{{ $group->id }} = document.getElementById(
                    'addUsersSearch-{{ $group->id }}');
                const addUsersList{{ $group->id }} = document.getElementById(
                    'addUsersList-{{ $group->id }}');

                addUsersSearch{{ $group->id }}.addEventListener('input', function() {
                    const searchValue = this.value.toLowerCase();
                    Array.from(addUsersList{{ $group->id }}.options).forEach(option => {
                        const userName = option.textContent.toLowerCase();
                        option.style.display = userName.includes(searchValue) ? '' : 'none';
                    });
                });
            @endforeach

            // Auto-scroll chat window
            const chatWindow = document.getElementById('chatWindow');
            if (chatWindow) {
                chatWindow.scrollTop = chatWindow.scrollHeight;
            }
        });
    </script>
    <script>
        // Handle click events for call and video icons
        $(document).on("click", ".call-icon, .video-icon", function() {
            var type = $(this).data("type"); // Get call type (audio or video)
            var receiverId = $(this).data("receiver-id"); // Get receiver ID
    
            // Ensure receiverId is a valid number
            receiverId = parseInt(receiverId);
            if (!receiverId || isNaN(receiverId)) {
                alert("Error: The receiver ID is not available.");
                return;
            }
    
            console.log("Receiver ID:", receiverId); // Debugging
    
            // Send call notification via AJAX
            $.ajax({
                url: '/send-call-notification',
                type: 'POST',
                data: {
                    receiver_id: receiverId, // Receiver ID
                    call_type: type, // Call type (audio or video)
                    _token: $('meta[name="csrf-token"]').attr('content') // CSRF token
                },
                success: function(response) {
                    console.log('Call notification sent:', response);
                    // Redirect to the call page after sending the notification
                    window.location.href = "/call/" + receiverId + "/" + type;
                },
                error: function(xhr) {
                    console.error('Error sending call notification:', xhr.responseText);
                    alert('Failed to send call notification. Please try again.');
                }
            });
        });
    
        // Fetch and display notifications
        function checkNotifications() {
            $.ajax({
                url: "/fetch-notifications",
                type: "GET",
                success: function(notifications) {
                    let notificationContainer = $("#notification-container");
                    notificationContainer.empty(); // Clear old notifications
    
                    notifications.forEach(notification => {
                        let notificationData = notification.data;
    
                        // Create a unique ID for each notification to avoid duplicates
                        let notificationId = `notification-${notification.id}`;
    
                        // Check if the notification already exists in the DOM
                        if (!$(`#${notificationId}`).length) {
                            let notificationDiv = `
                                <div id="${notificationId}" style="background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); margin-bottom: 10px;">
                                    <strong>${notificationData.message}</strong>
                                    <br>
                                    <button onclick="acceptCall(${notificationData.sender_id}, '${notificationData.call_type}', '${notification.id}')" style="background: green; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; margin-top: 5px;">Accept</button>
                                    <button onclick="dismissNotification('${notification.id}')" style="background: red; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; margin-top: 5px;">Decline</button>
                                </div>
                            `;
    
                            notificationContainer.append(notificationDiv);
                        }
                    });
                },
                error: function(xhr) {
                    console.error("Error fetching notifications:", xhr.responseText);
                }
            });
        }
    
        // Handle accepting a call
        function acceptCall(senderId, callType, notificationId) {
            console.log("Accepting call from:", senderId);
    
            // Mark notification as read before redirecting
            $.ajax({
                url: "/mark-notification-read",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    notification_id: notificationId
                },
                success: function() {
                    console.log("Notification marked as read.");
                    // Redirect to the call page
                    window.location.href = "/call/" + senderId + "/" + callType;
                },
                error: function(xhr) {
                    console.error("Error marking notification as read:", xhr.responseText);
                    // Still proceed to call in case of error
                    window.location.href = "/call/" + senderId + "/" + callType;
                }
            });
        }
    
        // Handle dismissing a notification
        function dismissNotification(notificationId) {
            $(`#notification-${notificationId}`).remove(); // Remove the notification from the DOM
    
            // Mark notification as read
            $.ajax({
                url: "/mark-notification-read",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    notification_id: notificationId
                },
                success: function(response) {
                    console.log("Notification dismissed and marked as read.");
                },
                error: function(xhr) {
                    console.error("Error dismissing notification:", xhr.responseText);
                }
            });
        }
    
        // Poll for notifications every 5 seconds
        setInterval(checkNotifications, 5000);
    
        // Initial check for notifications when the page loads
        $(document).ready(function() {
            checkNotifications();
        });
    </script>
@endsection
