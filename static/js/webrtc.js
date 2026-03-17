// webrtc.js — Fixed WebRTC signaling
// Key rules:
//   1. INTERVIEWER joins room right away; creates offer only after 'user-joined' fires.
//   2. CANDIDATE joins room after approval; only ever creates an answer.
//   3. ICE candidates are queued until remoteDescription is set.

const socket = io();
const localVideo = document.getElementById('localVideo');
const remoteVideo = document.getElementById('remoteVideo');
const toggleMicBtn = document.getElementById('toggleMic');
const toggleCamBtn = document.getElementById('toggleCam');
const shareScreenBtn = document.getElementById('shareScreen');
const toastContainer = document.getElementById('toast-container');

let localStream;
let peerConnection;
let screenStream;
let pendingIceCandidates = []; // queue candidates until remote desc is ready

const config = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        // Free TURN servers as fallback for LAN / NAT traversal
        {
            urls: 'turn:openrelay.metered.ca:80',
            username: 'openrelayproject',
            credential: 'openrelayproject'
        },
        {
            urls: 'turn:openrelay.metered.ca:443',
            username: 'openrelayproject',
            credential: 'openrelayproject'
        }
    ]
};

// ─── Toast Helper ────────────────────────────────────────────────────────────
function showToast(message) {
    if (!toastContainer) { console.log('Toast:', message); return; }
    const toast = document.createElement('div');
    toast.className = 'bg-purple-600 text-white px-4 py-2 rounded-lg shadow-lg mb-2 text-sm';
    toast.textContent = message;
    toast.style.animation = 'slideInUp 0.3s ease-out';
    toastContainer.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideOutDown 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ─── Peer Connection Factory ──────────────────────────────────────────────────
function createPeerConnection() {
    if (peerConnection) {
        peerConnection.close();
    }
    pendingIceCandidates = [];
    peerConnection = new RTCPeerConnection(config);

    peerConnection.onicecandidate = (event) => {
        if (event.candidate) {
            socket.emit('ice-candidate', { room: ROOM_ID, candidate: event.candidate });
        }
    };

    peerConnection.oniceconnectionstatechange = () => {
        console.log('ICE state:', peerConnection.iceConnectionState);
        if (peerConnection.iceConnectionState === 'failed') {
            console.warn('ICE failed — restarting ICE');
            peerConnection.restartIce();
        }
    };

    peerConnection.ontrack = (event) => {
        console.log('Remote track received:', event.streams[0]);
        if (remoteVideo.srcObject !== event.streams[0]) {
            remoteVideo.srcObject = event.streams[0];
        }
    };

    // Add local tracks
    if (localStream) {
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
        });
    }

    return peerConnection;
}

// Apply queued ICE candidates once remote description is ready
async function flushPendingCandidates() {
    for (const candidate of pendingIceCandidates) {
        try {
            await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
        } catch (e) {
            console.warn('Error flushing ICE candidate:', e);
        }
    }
    pendingIceCandidates = [];
}

// ─── Start Meeting (with graceful fallback) ───────────────────────────────────
function applyStream(stream, label) {
    localStream = stream;
    localVideo.srcObject = stream;
    const faceDetectionVideo = document.getElementById('faceDetectionVideo');
    if (faceDetectionVideo) faceDetectionVideo.srcObject = stream;
    socket.emit('join', { room: ROOM_ID, username: USERNAME });
    showToast(label);
}

function initializeMeeting() {
    console.log('Initializing meeting... ROLE =', ROLE);

    // Try video + audio
    navigator.mediaDevices.getUserMedia({ video: true, audio: true })
        .then(stream => applyStream(stream, '📹 Camera & microphone ready'))
        .catch(() => {
            console.warn('Video+audio failed — trying audio only...');
            // Try audio only
            navigator.mediaDevices.getUserMedia({ video: false, audio: true })
                .then(stream => {
                    showToast('⚠️ Camera unavailable — joining with microphone only');
                    applyStream(stream, '🎤 Microphone only (no camera)');
                    // Hide local video box since no camera
                    if (localVideo) localVideo.closest?.('.absolute.bottom-4.right-4') && 
                        (localVideo.closest('.absolute.bottom-4.right-4').style.display = 'none');
                })
                .catch(() => {
                    console.warn('Audio-only failed — trying video only...');
                    // Try video only
                    navigator.mediaDevices.getUserMedia({ video: true, audio: false })
                        .then(stream => {
                            showToast('⚠️ Microphone unavailable — joining with camera only');
                            applyStream(stream, '📷 Camera only (no microphone)');
                        })
                        .catch(() => {
                            // No devices at all — join without media
                            console.warn('No media devices available — joining without camera/mic');
                            showToast('⚠️ No camera or mic — joining in view-only mode');
                            localStream = null;
                            if (localVideo) localVideo.closest?.('.absolute.bottom-4.right-4') &&
                                (localVideo.closest('.absolute.bottom-4.right-4').style.display = 'none');
                            // Still join the room
                            socket.emit('join', { room: ROOM_ID, username: USERNAME });
                        });
                });
        });
}

// ─── Socket Events ────────────────────────────────────────────────────────────

// Fires on the person who just joined (self-join confirmation)
// We do NOT create the offer here — we wait for 'user-joined' instead.
socket.on('joined', () => {
    console.log('Joined room successfully. Waiting for the other participant...');
    showToast('Joined interview room');
});

// Fires on ALL OTHER people in the room when a new user joins.
// The INTERVIEWER listens for this and creates the offer.
socket.on('user-joined', async (data) => {
    showToast(`${data.username} joined the interview`);
    console.log('user-joined received, ROLE =', ROLE);

    // Only the interviewer initiates the offer
    if (ROLE === 'interviewer') {
        console.log('Interviewer creating offer...');
        createPeerConnection();
        try {
            const offer = await peerConnection.createOffer({
                offerToReceiveAudio: true,
                offerToReceiveVideo: true
            });
            await peerConnection.setLocalDescription(offer);
            socket.emit('offer', { room: ROOM_ID, offer: peerConnection.localDescription });
            console.log('Offer sent');
        } catch (e) {
            console.error('Error creating offer:', e);
        }
    }
});

socket.on('user-left', (data) => {
    showToast(`${data.username} left the interview`);
    if (remoteVideo) remoteVideo.srcObject = null;
});

// Candidate receives the offer → creates answer
socket.on('offer', async (data) => {
    console.log('Received offer, ROLE =', ROLE);
    if (!peerConnection) createPeerConnection();

    try {
        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer));
        await flushPendingCandidates();

        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        socket.emit('answer', { room: ROOM_ID, answer: peerConnection.localDescription });
        console.log('Answer sent');
    } catch (e) {
        console.error('Error handling offer:', e);
    }
});

// Interviewer receives the answer
socket.on('answer', async (data) => {
    console.log('Received answer');
    if (!peerConnection) return;
    try {
        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
        await flushPendingCandidates();
    } catch (e) {
        console.error('Error setting answer:', e);
    }
});

// ICE candidates — queue if remote not yet set
socket.on('ice-candidate', async (data) => {
    if (!peerConnection || !peerConnection.remoteDescription) {
        pendingIceCandidates.push(data.candidate);
        return;
    }
    try {
        await peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
    } catch (e) {
        console.warn('Error adding ICE candidate:', e);
    }
});

// Candidate approved by interviewer → join main room + start stream
socket.on('join-approved', (data) => {
    console.log('Join approved, entering room...');
    // webrtc.js doesn't control the overlay; interview.html inline script does.
    // But we do need to emit join now if ROLE === 'candidate' and weren't in room yet.
});

// ─── Media Controls ───────────────────────────────────────────────────────────
if (toggleMicBtn) {
    toggleMicBtn.addEventListener('click', () => {
        if (!localStream) return;
        const audioTrack = localStream.getAudioTracks()[0];
        if (!audioTrack) return;
        audioTrack.enabled = !audioTrack.enabled;
        toggleMicBtn.classList.toggle('bg-red-700', !audioTrack.enabled);
        toggleMicBtn.classList.toggle('bg-gray-800', audioTrack.enabled);
    });
}

if (toggleCamBtn) {
    toggleCamBtn.addEventListener('click', () => {
        if (!localStream) return;
        const videoTrack = localStream.getVideoTracks()[0];
        if (!videoTrack) return;
        videoTrack.enabled = !videoTrack.enabled;
        toggleCamBtn.classList.toggle('bg-red-700', !videoTrack.enabled);
        toggleCamBtn.classList.toggle('bg-gray-800', videoTrack.enabled);
    });
}

if (shareScreenBtn) {
    shareScreenBtn.addEventListener('click', () => {
        const icon = shareScreenBtn.querySelector('.fa-desktop, .fa-stop');

        if (screenStream) {
            // Stop screen share — revert to camera
            const cameraVideoTrack = localStream.getVideoTracks()[0];
            if (peerConnection && cameraVideoTrack) {
                const sender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                if (sender) sender.replaceTrack(cameraVideoTrack);
            }
            screenStream.getTracks().forEach(t => t.stop());
            screenStream = null;
            localVideo.srcObject = localStream;

            if (icon) { icon.classList.remove('fa-stop'); icon.classList.add('fa-desktop'); }
            shareScreenBtn.title = 'Share Screen';
        } else {
            // Start screen share
            navigator.mediaDevices.getDisplayMedia({ video: true })
                .then(stream => {
                    screenStream = stream;
                    const screenTrack = stream.getVideoTracks()[0];

                    if (peerConnection) {
                        const sender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) sender.replaceTrack(screenTrack);
                    }

                    localVideo.srcObject = screenStream;
                    if (icon) { icon.classList.remove('fa-desktop'); icon.classList.add('fa-stop'); }
                    shareScreenBtn.title = 'Stop Sharing';

                    screenTrack.onended = () => {
                        if (screenStream) shareScreenBtn.click();
                    };
                })
                .catch(e => console.error('Screen share error:', e));
        }
    });
}

// ─── Cleanup on unload ────────────────────────────────────────────────────────
window.addEventListener('beforeunload', () => {
    socket.emit('leave', { room: ROOM_ID, username: USERNAME });
    if (peerConnection) peerConnection.close();
    if (localStream) localStream.getTracks().forEach(t => t.stop());
});
