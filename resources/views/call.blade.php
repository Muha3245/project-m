<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Meet Style Calling</title>
    <script src="https://cdn.agora.io/sdk/release/AgoraRTC_N-4.17.1.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #202124;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        #main-container {
            width: 90%;
            height: 80vh;
            background: black;
            border-radius: 10px;
            position: relative;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        #remote-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .remote-video {
            flex: 1;
            max-width: 48%;
            height: 45%;
            background: black;
            border-radius: 10px;
        }

        #local-container {
            width: 180px;
            height: 120px;
            position: absolute;
            bottom: 20px;
            right: 20px;
            border-radius: 10px;
            background: black;
            border: 2px solid white;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 24px;
        }

        #local-container.avatar {
            background-color: #3c4043;
            /* border-radius: 50%; */
        }

        video {
            width: 100%;
            height: 100%;
            border-radius: 10px;
        }

        .controls {
            display: flex;
            gap: 15px;
            position: absolute;
            bottom: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px;
            border-radius: 50px;
        }

        .controls button {
            background-color: #3c4043;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            width: 50px;
            height: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .controls button:hover {
            background-color: #5f6368;
        }

        .end-call {
            background-color: red;
        }

        .end-call:hover {
            background-color: darkred;
        }
    </style>
</head>
<body>
    <div id="main-container">
        <div id="remote-container" class="avatar">
            <img src="{{ $receiver->getFirstMediaUrl('default', 'preview') ?: 'https://ui-avatars.com/api/?name=' . urlencode($receiver->name) }}" 
                 alt="receiver-avatar" class="rounded-circle me-2" height="30" width="30">
        </div>
                <div id="local-container" class="avatar">
            <img src="{{ $sender->getFirstMediaUrl('default', 'preview') ?: 'https://ui-avatars.com/api/?name=' . urlencode($sender->name) }}" 
                 alt="user-avatar" class="rounded-circle me-2 " height="30" width="30">
        </div>
        

    <div class="controls">
        <button id="mute-mic">🎤</button>
        <button id="mute-camera">📷</button>
        <button class="end-call">🚫</button>
    </div>

    <script>
        // ✅ Agora Credentials
        const APP_ID = "8e7c103dab4d44e0995a874e3f8a266a";  // Your Agora App ID
        const TOKEN = "007eJxTYDjL0dfYUaAxe52xlpJCzo48tnbjWQfsfjW/5NMr3996QFOBwSLVPNnQwDglMckkxcQk1cDS0jTRwtwk1TjNItHIzCyx+fzp9IZARoZgI3cGRigE8ZkZEnMyGRgArFIdVg==";  // Your Agora Token
        const CHANNEL_NAME = "ali";  // Channel name (must match the one used to generate the token)
        const CALL_TYPE = @json($call->call_type);  // Call type (audio or video) from Laravel
        const USER_ID = @json(auth()->id());  // Authenticated user ID from Laravel

        // Log credentials for debugging
        console.log("App ID:", APP_ID);
        console.log("Token:", TOKEN);
        console.log("Channel Name:", CHANNEL_NAME);
        console.log("User ID:", USER_ID);

        // ✅ Initialize Agora Client
        const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
        let localTracks = []; // To store local audio and video tracks

        // ✅ Function to join the call
        async function joinCall() {
            try {
                console.log("Joining Agora channel:", CHANNEL_NAME);

                // Join the Agora channel
                const UID = await client.join(APP_ID, CHANNEL_NAME, TOKEN, USER_ID);
                console.log("Joined Agora channel successfully. UID:", UID);

                // Create and publish local tracks based on call type
                if (CALL_TYPE === "video") {
                    console.log("Starting video call...");
                    localTracks = await AgoraRTC.createMicrophoneAndCameraTracks();
                    localTracks[1].play("local-container");  // Play local video
                    document.getElementById("local-container").classList.remove("avatar");
                } else {
                    console.log("Starting audio call...");
                    localTracks = [await AgoraRTC.createMicrophoneAudioTrack()];
                    document.getElementById("local-container").classList.add("avatar");
                }

                // Publish local tracks to the channel
                await client.publish(localTracks);
                console.log("Published local tracks to Agora.");

            } catch (error) {
                console.error("Error joining Agora call:", error);
                alert("⚠️ Failed to join the call. Check your App ID, Token, and network connection.");
            }
        }

        // ✅ Handle Remote Users Joining
        client.on("user-published", async (user, mediaType) => {
            try {
                await client.subscribe(user, mediaType);
                console.log("Subscribed to remote user:", user.uid);

                if (mediaType === "video") {
                    const remoteVideoTrack = user.videoTrack;
                    remoteVideoTrack.play("remote-container");  // Play remote video
                }
                if (mediaType === "audio") {
                    user.audioTrack.play();  // Play remote audio
                }

            } catch (error) {
                console.error("Error subscribing to remote user:", error);
            }
        });

        // ✅ Handle Remote Users Leaving
        client.on("user-left", (user) => {
            console.log("Remote user left:", user.uid);
            document.getElementById("remote-container").innerHTML = ""; // Clear remote video
        });

        // ✅ Handle Call End
        document.querySelector(".end-call").addEventListener("click", async () => {
            try {
                // Stop and clean up local tracks
                localTracks.forEach(track => track.stop());
                localTracks = [];

                // Leave the Agora channel
                await client.leave();
                console.log("Left the Agora call successfully.");

                // Redirect to the chat page
                window.location.href = "/one-to-one-chat";

            } catch (error) {
                console.error("Error leaving the call:", error);
            }
        });

        // ✅ Mute/Unmute Microphone
        document.getElementById("mute-mic").addEventListener("click", () => {
            if (localTracks[0]) {
                localTracks[0].setMuted(!localTracks[0].muted);
                // Change the icon to indicate mute/unmute state
                document.getElementById("mute-mic").textContent = localTracks[0].muted ? "🔇" : "🎤";
            }
        });

        // ✅ Mute/Unmute Camera
        document.getElementById("mute-camera").addEventListener("click", async () => {
    if (localTracks[1]) {
        if (localTracks[1].muted) {
            // Re-enable camera by creating a new track
            localTracks[1] = await AgoraRTC.createCameraVideoTrack();
            await client.publish(localTracks[1]);
            localTracks[1].play("local-container");
            document.getElementById("mute-camera").textContent = "📷";
            document.getElementById("local-container").classList.remove("avatar");
            document.getElementById("local-container").style.display = "block";
        } else {
            // Stop the video track properly
            await client.unpublish(localTracks[1]);
            localTracks[1].stop();
            localTracks[1].close();
            localTracks[1] = null; // Remove the track reference
            document.getElementById("mute-camera").textContent = "📷❌";
            document.getElementById("local-container").classList.add("avatar");
            document.getElementById("local-container").style.display = "none";
        }
    } else {
        // If no camera track exists (e.g., in an audio-only call), create one
        localTracks[1] = await AgoraRTC.createCameraVideoTrack();
        await client.publish(localTracks[1]);
        localTracks[1].play("local-container");
        document.getElementById("mute-camera").textContent = "📷";
        document.getElementById("local-container").classList.remove("avatar");
        document.getElementById("local-container").style.display = "block";
    }
});


        // ✅ Start the Call
        joinCall();
    </script>
</body>
</html>








