                        <!DOCTYPE html>
                        <html lang="en">

                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>Group Call - {{ $group->name }}</title>
                            <script src="https://cdn.agora.io/sdk/release/AgoraRTC_N-4.17.1.js"></script>
                            <style>
                                body {
                                    margin: 0;
                                    padding: 0;
                                    background: #202124;
                                    color: white;
                                    font-family: Arial, sans-serif;
                                }

                                .group-header {
                                    position: fixed;
                                    top: 0;
                                    left: 0;
                                    right: 0;
                                    background: rgba(0, 0, 0, 0.8);
                                    padding: 15px 20px;
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                    z-index: 100;
                                }

                                .group-name {
                                    font-size: 1.2em;
                                    font-weight: bold;
                                }

                                #videos-container {
                                    display: grid;
                                    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                                    gap: 20px;
                                    padding: 80px 20px 100px;
                                }

                                .video-wrapper {
                                    position: relative;
                                    width: 100%;
                                    height: 300px;
                                    background: #1a1a1a;
                                    border-radius: 12px;
                                    overflow: hidden;
                                }

                                .video-container {
                                    width: 100%;
                                    height: 100%;
                                    position: relative;
                                }

                                .video-wrapper video {
                                    width: 100%;
                                    height: 100%;
                                    object-fit: cover;
                                }

                                .video-avatar {
                                    position: absolute;
                                    top: 50%;
                                    left: 50%;
                                    transform: translate(-50%, -50%);
                                    width: 120px;
                                    height: 120px;
                                    border-radius: 50%;
                                    display: none;
                                    z-index: 1;
                                }

                                .video-muted .video-avatar {
                                    display: block;
                                }

                                .video-muted video {
                                    display: none;
                                }

                                .video-info {
                                    position: absolute;
                                    bottom: 0;
                                    left: 0;
                                    right: 0;
                                    padding: 12px;
                                    background: rgba(0, 0, 0, 0.8);
                                    color: white;
                                    z-index: 2;
                                }

                                .participant-name {
                                    display: flex;
                                    flex-direction: column;
                                    gap: 4px;
                                }

                                .status-indicators {
                                    display: flex;
                                    gap: 8px;
                                    margin-top: 4px;
                                }

                                .mute-indicator {
                                    background: rgba(234, 67, 53, 0.9);
                                    color: white;
                                    padding: 4px 8px;
                                    border-radius: 4px;
                                    font-size: 12px;
                                    font-weight: bold;
                                    display: inline-flex;
                                    align-items: center;
                                    gap: 4px;
                                    margin-top: 4px;
                                }

                                .controls {
                                    position: fixed;
                                    bottom: 20px;
                                    left: 50%;
                                    transform: translateX(-50%);
                                    display: flex;
                                    gap: 20px;
                                    background: rgba(0, 0, 0, 0.8);
                                    padding: 15px 25px;
                                    border-radius: 50px;
                                    z-index: 100;
                                }

                                .controls button {
                                    width: 45px;
                                    height: 45px;
                                    border-radius: 50%;
                                    border: none;
                                    cursor: pointer;
                                    background: #424242;
                                    color: white;
                                    font-size: 20px;
                                    transition: background-color 0.3s;
                                }

                                .muted {
                                    background: #ea4335 !important;
                                }

                                #local-container {
                                    width: 100%;
                                    height: 100%;
                                    position: relative;
                                }

                                #local-container video {
                                    width: 100%;
                                    height: 100%;
                                    object-fit: cover;
                                }

                                .remote-video {
                                    width: 100%;
                                    height: 100%;
                                    object-fit: cover;
                                }

                                #remote-container {
                                    display: grid;
                                    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                                    gap: 20px;
                                }

                                /* Add styles for disabled camera button */
                                .controls button[disabled] {
                                    opacity: 0.5;
                                    cursor: not-allowed;
                                }
                            </style>
                        </head>

                        <body>
                            <div class="group-header">
                                <div class="group-name">{{ $group->name }}</div>
                                <div class="members-count">{{ $group->users->count() }} members</div>
                            </div>

                            <div id="videos-container">
                                <div id="remote-container"></div>
                            </div>

                            <div class="controls">
                                <button id="mute-mic">🎤</button>
                                <button id="mute-camera">📷</button>
                                <button class="end-call" style="background: #ea4335">❌</button>
                            </div>

                            <script>
                                const APP_ID = "8e7c103dab4d44e0995a874e3f8a266a";
                                const TOKEN = "007eJxTYFju+2tRYazKlF8pGn3Lfioeujbl5NQDp3tnZUZPqAzLlD6kwGCRap5saGCckphkkmJikmpgaWmaaGFukmqcZpFoZGaWqNR0Kr0hkJHhXdoeFkYGCATxmRkSczIZGADcmiFi";
                                const CHANNEL_NAME = "ali";
                                const USER_ID = {{ auth()->id() }};
                                const CALL_TYPE = "{{ $call_type }}";

                                const PARTICIPANT_NAMES = {
                                    @foreach($group->users as $user)
                                        {{ $user->id }}: "{{ $user->name }}",
                                    @endforeach
                                };

                                const client = AgoraRTC.createClient({
                                    mode: "rtc",
                                    codec: "vp8"
                                });

                                let localTracks = [];
                                let remoteUsers = {};
                                let isAudioMuted = false;
                                let isVideoMuted = false;

                                async function joinGroupCall() {
                                    try {
                                        await client.join(APP_ID, CHANNEL_NAME, TOKEN, USER_ID);
                                        console.log("Joined channel:", CHANNEL_NAME);

                                        // Remove any existing containers
                                        const existingWrappers = document.querySelectorAll(`[id^="video-wrapper-${USER_ID}"]`);
                                        existingWrappers.forEach(wrapper => wrapper.remove());

                                        // Create wrapper with actual name from group
                                        const wrapper = createVideoWrapper(USER_ID, PARTICIPANT_NAMES[USER_ID], true);
                                        document.getElementById("videos-container").appendChild(wrapper);

                                        if (CALL_TYPE === 'voice') {
                                            localTracks = [await AgoraRTC.createMicrophoneAudioTrack()];
                                            document.getElementById(`video-wrapper-${USER_ID}`).classList.add('video-muted');
                                            document.getElementById("mute-camera").style.opacity = "0.5";
                                        } else {
                                            localTracks = await AgoraRTC.createMicrophoneAndCameraTracks();
                                            if (localTracks[1]) {
                                                localTracks[1].play("local-container");
                                            }
                                        }

                                        await client.publish(localTracks);

                                        // Broadcast initial state to all participants
                                        broadcastUserState();

                                    } catch (error) {
                                        console.error("Error joining call:", error);
                                        handleJoinError(error);
                                    }
                                }

                                function createVideoWrapper(userId, userName, isLocal = false) {
                                    const wrapper = document.createElement('div');
                                    wrapper.className = 'video-wrapper';
                                    wrapper.id = `video-wrapper-${userId}`;

                                    const videoContainer = document.createElement('div');
                                    videoContainer.className = 'video-container';
                                    videoContainer.id = isLocal ? 'local-container' : `remote-container-${userId}`;

                                    const avatar = document.createElement('img');
                                    avatar.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=random`;
                                    avatar.className = 'video-avatar';
                                    avatar.alt = userName;

                                    const info = document.createElement('div');
                                    info.className = 'video-info';
                                    info.innerHTML = `
                                        <div class="participant-name">
                                            ${userName}
                                            <div class="status-indicators">
                                                <span class="mute-indicator audio-status-${userId}" style="display: none">🎤 Muted</span>
                                                <span class="mute-indicator video-status-${userId}" style="display: none">📷 Off</span>
                                            </div>
                                        </div>
                                    `;

                                    wrapper.appendChild(videoContainer);
                                    wrapper.appendChild(avatar);
                                    wrapper.appendChild(info);

                                    return wrapper;
                                }

                                function broadcastUserState() {
                                    client.sendStreamMessage(JSON.stringify({
                                        type: 'user-state',
                                        userId: USER_ID,
                                        userName: PARTICIPANT_NAMES[USER_ID],
                                        audioMuted: isAudioMuted,
                                        videoMuted: isVideoMuted
                                    }));
                                }

                                client.on("user-published", async (user, mediaType) => {
                                    try {
                                        await client.subscribe(user, mediaType);
                                        
                                        if (!document.getElementById(`video-wrapper-${user.uid}`)) {
                                            const userName = PARTICIPANT_NAMES[user.uid] || `Participant ${user.uid}`;
                                            const wrapper = createVideoWrapper(user.uid, userName);
                                            document.getElementById("remote-container").appendChild(wrapper);
                                        }

                                        if (mediaType === "video") {
                                            user.videoTrack.play(`remote-container-${user.uid}`);
                                            document.getElementById(`video-wrapper-${user.uid}`).classList.remove("video-muted");
                                        }
                                        if (mediaType === "audio") {
                                            user.audioTrack.play();
                                        }

                                        // Request current state from the new user
                                        broadcastUserState();
                                    } catch (error) {
                                        console.error("Error handling remote user:", error);
                                    }
                                });

                                client.on("user-muted", async (user, mediaType) => {
                                    updateMuteStatus(user.uid, mediaType, true);
                                });

                                client.on("user-unmuted", async (user, mediaType) => {
                                    updateMuteStatus(user.uid, mediaType, false);
                                });

                                client.on("user-left", (user) => {
                                    const wrapper = document.getElementById(`video-wrapper-${user.uid}`);
                                    if (wrapper) {
                                        wrapper.remove();
                                    }
                                    delete remoteUsers[user.uid];
                                });

                                client.on("user-unpublished", async (user, mediaType) => {
                                    console.log("User unpublished:", user.uid, mediaType);
                                    if (mediaType === "video") {
                                        document.getElementById(`video-wrapper-${user.uid}`).classList.add("video-muted");
                                    }
                                });

                                // Update audio mute handler
                                document.getElementById("mute-mic").addEventListener("click", async () => {
                                    if (localTracks[0]) {
                                        isAudioMuted = !isAudioMuted;
                                        await localTracks[0].setEnabled(!isAudioMuted);
                                        document.getElementById("mute-mic").classList.toggle("muted", isAudioMuted);
                                        
                                        // Broadcast new mute state
                                        client.sendStreamMessage(JSON.stringify({
                                            type: 'mute-update',
                                            userId: USER_ID,
                                            userName: PARTICIPANT_NAMES[USER_ID],
                                            mediaType: 'audio',
                                            muted: isAudioMuted
                                        }));
                                    }
                                });

                                // Update camera button handler
                                document.getElementById("mute-camera").addEventListener("click", async () => {
                                    try {
                                        if (CALL_TYPE === 'voice' && !localTracks[1]) {
                                            // First time enabling camera in voice call
                                            const videoTrack = await AgoraRTC.createCameraVideoTrack();
                                            localTracks.push(videoTrack);
                                            videoTrack.play("local-container");
                                            await client.publish([videoTrack]);
                                            document.getElementById(`video-wrapper-${USER_ID}`).classList.remove('video-muted');
                                            document.getElementById("mute-camera").style.opacity = "1";
                                            isVideoMuted = false;
                                        } else if (localTracks[1]) {
                                            // Toggle existing video track
                                            isVideoMuted = !isVideoMuted;
                                            await localTracks[1].setEnabled(!isVideoMuted);
                                            document.getElementById(`video-wrapper-${USER_ID}`).classList.toggle("video-muted",
                                                isVideoMuted);
                                        }

                                        document.getElementById("mute-camera").classList.toggle("muted", isVideoMuted);

                                        // Broadcast camera state
                                        client.sendStreamMessage(JSON.stringify({
                                            type: 'mute-status',
                                            userId: USER_ID,
                                            mediaType: 'video',
                                            muted: isVideoMuted
                                        }));
                                    } catch (error) {
                                        console.error("Error toggling camera:", error);
                                        alert("Failed to toggle camera. Please check your camera permissions.");
                                    }
                                });

                                // Handle incoming stream messages
                                client.on("stream-message", async (_, message) => {
                                    try {
                                        const data = JSON.parse(message);
                                        
                                        if (data.type === 'user-state' || data.type === 'mute-update') {
                                            // Update the UI for the user's state
                                            updateMuteStatus(data.userId, data.mediaType, data.muted);
                                            
                                            // Update participant name if needed
                                            if (data.userName && !PARTICIPANT_NAMES[data.userId]) {
                                                PARTICIPANT_NAMES[data.userId] = data.userName;
                                                updateParticipantName(data.userId, data.userName);
                                            }
                                        }
                                    } catch (error) {
                                        console.error("Error handling stream message:", error);
                                    }
                                });

                                // Add these helper functions
                                function updateParticipantName(userId, userName) {
                                    const nameElement = document.querySelector(`#video-wrapper-${userId} .participant-name`);
                                    if (nameElement) {
                                        nameElement.firstChild.textContent = userName;
                                    }
                                }

                                function updateMuteStatus(userId, type, muted) {
                                    const wrapper = document.getElementById(`video-wrapper-${userId}`);
                                    if (wrapper) {
                                        const statusElement = wrapper.querySelector(`.${type}-status-${userId}`);
                                        if (statusElement) {
                                            statusElement.style.display = muted ? "inline-block" : "none";
                                            statusElement.innerHTML = type === "audio" ? '🎤 Muted' : '📷 Off';
                                        }
                                        
                                        if (type === 'video') {
                                            wrapper.classList.toggle('video-muted', muted);
                                        }
                                    }
                                }

                                document.querySelector(".end-call").addEventListener("click", async () => {
                                    localTracks.forEach(track => {
                                        track.stop();
                                        track.close();
                                    });
                                    await client.leave();
                                    window.location.href = "/groups/{{ $group->id }}";
                                });

                                // Add logging for debugging
                                client.on("connection-state-change", (curState, prevState) => {
                                    console.log("Connection state changed from", prevState, "to", curState);
                                });

                                joinGroupCall();
                            </script>
                        </body>

                        </html>
