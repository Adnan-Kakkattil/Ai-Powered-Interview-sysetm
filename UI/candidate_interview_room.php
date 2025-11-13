<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/helpers.php';

requireRole(['candidate']);

$currentUser = currentUser();

$interviewId = $_GET['interview_id'] ?? '';
if (!$interviewId) {
    redirect('candidate_dashboard.php');
}

$interviewResponse = apiRequest('/candidates/interviews');
$interviews = $interviewResponse['data']['interviews'] ?? [];

$selectedInterview = null;
foreach ($interviews as $interview) {
    $id = (string)($interview['_id'] ?? $interview['id'] ?? '');
    if ($id === $interviewId) {
        $selectedInterview = $interview;
        break;
    }
}

if (!$selectedInterview) {
    $_SESSION['flash_error'] = 'Interview not found.';
    redirect('candidate_dashboard.php');
}

$meetingRoomId = $selectedInterview['meetingRoomId'] ?? 'N/A';
$interviewerName = $selectedInterview['interviewer']['name'] ?? 'Hiring Team';
$startAt = $selectedInterview['schedule']['start'] ?? null;
$endAt = $selectedInterview['schedule']['end'] ?? null;
$status = $selectedInterview['status'] ?? 'scheduled';
$startLabel = formatDateTime($startAt);
$endLabel = formatDateTime($endAt);
$codingTask = $selectedInterview['codingTask']['title'] ?? null;

$pageTitle = 'Join Interview Room';
$activeNav = 'candidate_interviews';
require __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen">
    <div class="max-w-5xl mx-auto px-6 py-10 space-y-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <p class="text-sm text-gray-500 mb-1 uppercase tracking-[0.2em]">SmartHire Live Interview</p>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Join your session</h1>
                <div class="text-sm text-gray-600 space-y-1">
                    <div><span class="font-medium text-gray-800">Interviewer:</span> <?= htmlspecialchars($interviewerName) ?></div>
                    <div><span class="font-medium text-gray-800">Schedule:</span> <?= htmlspecialchars($startLabel) ?> &mdash; <?= htmlspecialchars($endLabel) ?></div>
                    <div><span class="font-medium text-gray-800">Status:</span> <span class="capitalize"><?= htmlspecialchars($status) ?></span></div>
                    <div><span class="font-medium text-gray-800">Room ID:</span> <?= htmlspecialchars($meetingRoomId) ?></div>
                    <?php if ($codingTask): ?>
                        <div><span class="font-medium text-gray-800">Coding Task:</span> <?= htmlspecialchars($codingTask) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-white border border-blue-100 shadow-sm rounded-2xl px-6 py-4 text-sm text-blue-700 max-w-sm">
                <div class="font-semibold text-blue-800 mb-2">Before you join</div>
                <ul class="space-y-2 text-xs text-blue-700">
                    <li class="flex items-start space-x-2"><i class="ri-check-line mt-0.5"></i><span>Use Chrome or Edge for best performance.</span></li>
                    <li class="flex items-start space-x-2"><i class="ri-check-line mt-0.5"></i><span>Allow camera and microphone access when prompted.</span></li>
                    <li class="flex items-start space-x-2"><i class="ri-check-line mt-0.5"></i><span>Have your coding environment ready; a collaborative editor may open during the session.</span></li>
                </ul>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Live room console</h2>
                    <button id="copyRoom" class="inline-flex items-center text-xs font-medium text-primary hover:text-brand-700">
                        <i class="ri-clipboard-line mr-1"></i> Copy Room ID
                    </button>
                </div>
                <div class="bg-gray-50 border border-dashed border-gray-200 rounded-xl p-6 text-sm text-gray-600" id="videoArea">
                    <div class="font-medium text-gray-900 mb-3">Live session preview</div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">You</div>
                            <video id="localVideo" class="w-full rounded-xl bg-gray-900 aspect-video" autoplay playsinline muted></video>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Interviewer</div>
                            <video id="remoteVideo" class="w-full rounded-xl bg-gray-900 aspect-video" autoplay playsinline></video>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-4">If video does not start automatically, ensure your camera and microphone permissions are granted.</p>
                </div>
                <div class="bg-gray-900 text-gray-100 rounded-xl p-4 text-xs font-mono overflow-y-auto h-40" id="socketLog">
                    <div class="text-gray-400">Connecting to SmartHire realtime services…</div>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button id="toggleMic" class="inline-flex items-center px-4 py-2 rounded-button text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                        <i class="ri-mic-line mr-2"></i>Mic On
                    </button>
                    <button id="toggleCam" class="inline-flex items-center px-4 py-2 rounded-button text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                        <i class="ri-video-line mr-2"></i>Camera On
                    </button>
                    <button id="leaveRoom" class="inline-flex items-center px-4 py-2 rounded-button text-xs font-semibold bg-rose-500 text-white hover:bg-rose-600 transition">
                        <i class="ri-logout-box-line mr-2"></i>Leave Session
                    </button>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Shared resources</h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li><a href="#" class="text-primary hover:underline">Open collaborative editor (coming soon)</a></li>
                        <li><a href="#" class="text-primary hover:underline">View job description</a></li>
                        <li><a href="#" class="text-primary hover:underline">View interview guidelines</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Session notes</h3>
                    <textarea id="sessionNotes" rows="6" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" placeholder="Capture key takeaways or questions to ask…"></textarea>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="http://localhost:5000/socket.io/socket.io.js"></script>
<script>
    (() => {
        const socketLog = document.getElementById('socketLog');
        const appendLog = (message, type = 'info') => {
            if (!socketLog) return;
            const line = document.createElement('div');
            line.className = type === 'error' ? 'text-rose-400' : 'text-gray-300';
            line.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            socketLog.appendChild(line);
            socketLog.scrollTop = socketLog.scrollHeight;
        };

        const interviewId = <?= json_encode($interviewId) ?>;
        const meetingRoomId = <?= json_encode($meetingRoomId) ?>;
        const attendeeRole = 'candidate';

        const localVideo = document.getElementById('localVideo');
        const remoteVideo = document.getElementById('remoteVideo');
        const config = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };
        const pc = new RTCPeerConnection(config);
        let localStream;
        let offerSent = false;

        pc.ontrack = ({ streams }) => {
            const [stream] = streams;
            if (stream && remoteVideo.srcObject !== stream) {
                remoteVideo.srcObject = stream;
                remoteVideo.autoplay = true;
                remoteVideo.playsInline = true;
                remoteVideo.muted = false;
                remoteVideo.play().catch(() => {});
                appendLog('Remote media stream received.');
            }
        };

        pc.onicecandidate = (event) => {
            if (event.candidate) {
                socket.emit('iceCandidate', { interviewId, payload: event.candidate });
            }
        };

        pc.onconnectionstatechange = () => {
            appendLog(`Connection state: ${pc.connectionState}`);
        };

        const initMedia = async () => {
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                localStream.getTracks().forEach((track) => pc.addTrack(track, localStream));
                localVideo.srcObject = localStream;
                localVideo.muted = true;
                await localVideo.play().catch(() => {});
                appendLog('Camera and microphone ready.');
            } catch (error) {
                appendLog(`Media devices error: ${error.message}`, 'error');
            }
        };

        const createAndSendOffer = async () => {
            if (offerSent) return;
            try {
                const offer = await pc.createOffer();
                await pc.setLocalDescription(offer);
                socket.emit('offer', { interviewId, payload: offer });
                offerSent = true;
                appendLog('Offer sent to interviewer.');
            } catch (error) {
                appendLog(`Offer error: ${error.message}`, 'error');
            }
        };

        const socket = io('http://localhost:5000/interview', {
            transports: ['websocket', 'polling'],
        });

        socket.on('connect', () => {
            appendLog('Connected to SmartHire realtime. Joining room…');
            socket.emit('joinRoom', { interviewId, role: attendeeRole });
        });

        socket.on('userJoined', (payload) => {
            appendLog(`Participant joined: ${payload.socketId}`);
            if (payload.role === 'admin') {
                setTimeout(createAndSendOffer, 500);
            }
        });

        socket.on('userLeft', (payload) => {
            appendLog(`Participant left: ${payload.socketId}`);
        });

        socket.on('offer', async ({ payload }) => {
            appendLog('Offer received unexpectedly.', 'error');
        });

        socket.on('answer', async ({ payload }) => {
            try {
                await pc.setRemoteDescription(new RTCSessionDescription(payload));
                appendLog('Answer received. Connection established.');
            } catch (error) {
                appendLog(`Answer error: ${error.message}`, 'error');
            }
        });

        socket.on('iceCandidate', async ({ payload }) => {
            try {
                await pc.addIceCandidate(new RTCIceCandidate(payload));
            } catch (error) {
                appendLog(`ICE add error: ${error.message}`, 'error');
            }
        });

        socket.on('disconnect', (reason) => {
            appendLog(`Disconnected: ${reason}`, 'error');
        });

        initMedia().then(() => {
            setTimeout(createAndSendOffer, 1000);
        });

        const copyRoom = document.getElementById('copyRoom');
        if (copyRoom) {
            copyRoom.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(meetingRoomId);
                    copyRoom.textContent = 'Room ID copied!';
                    setTimeout(() => (copyRoom.textContent = 'Copy Room ID'), 3000);
                } catch (err) {
                    appendLog('Unable to copy room ID.', 'error');
                }
            });
        }

        const leaveRoom = document.getElementById('leaveRoom');
        if (leaveRoom) {
            leaveRoom.addEventListener('click', () => {
                if (socket) {
                    socket.emit('leaveRoom', { interviewId });
                    socket.disconnect();
                }
                if (localStream) {
                    localStream.getTracks().forEach((track) => track.stop());
                }
                window.location.href = 'candidate_dashboard.php';
            });
        }

        document.getElementById('toggleMic')?.addEventListener('click', (event) => {
            if (!localStream) return;
            const micBtn = event.currentTarget;
            const audioTrack = localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
                micBtn.classList.toggle('bg-gray-200');
                micBtn.classList.toggle('text-gray-500');
                micBtn.innerHTML = `<i class="ri-mic-line mr-2"></i>${audioTrack.enabled ? 'Mic On' : 'Mic Off'}`;
            }
        });

        document.getElementById('toggleCam')?.addEventListener('click', (event) => {
            if (!localStream) return;
            const camBtn = event.currentTarget;
            const videoTrack = localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.enabled = !videoTrack.enabled;
                camBtn.classList.toggle('bg-gray-200');
                camBtn.classList.toggle('text-gray-500');
                camBtn.innerHTML = `<i class="ri-video-line mr-2"></i>${videoTrack.enabled ? 'Camera On' : 'Camera Off'}`;
            }
        });

        window.addEventListener('beforeunload', () => {
            if (socket && socket.connected) {
                socket.emit('leaveRoom', { interviewId });
                socket.disconnect();
            }
            if (localStream) {
                localStream.getTracks().forEach((track) => track.stop());
            }
        });
    })();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
