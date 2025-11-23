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
const config = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' }
    ]
};

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    toastContainer.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Define createPeerConnection function properly
function createPeerConnection() {
    peerConnection = new RTCPeerConnection(config);

    peerConnection.onicecandidate = (event) => {
        if (event.candidate) {
            socket.emit('ice-candidate', { room: ROOM_ID, candidate: event.candidate });
        }
    };

    peerConnection.ontrack = (event) => {
        remoteVideo.srcObject = event.streams[0];
    };

    // Add tracks
    if (localStream) {
        localStream.getAudioTracks().forEach(track => peerConnection.addTrack(track, localStream));

        if (screenStream) {
            peerConnection.addTrack(screenStream.getVideoTracks()[0], localStream);
        } else {
            peerConnection.addTrack(localStream.getVideoTracks()[0], localStream);
        }
    }
}

// Get user media
navigator.mediaDevices.getUserMedia({ video: true, audio: true })
    .then(stream => {
        localStream = stream;
        localVideo.srcObject = stream;
        socket.emit('join', { room: ROOM_ID, username: USERNAME });
    })
    .catch(error => {
        console.error('Error accessing media devices:', error);
        alert('Could not access camera/microphone. Please allow permissions.');
    });

socket.on('user-joined', (data) => {
    showToast(`${data.username} joined the interview`);
});

socket.on('user-left', (data) => {
    showToast(`${data.username} left the interview`);
});

socket.on('joined', () => {
    // Initiator creates offer
    createPeerConnection();

    peerConnection.createOffer()
        .then(offer => peerConnection.setLocalDescription(offer))
        .then(() => {
            socket.emit('offer', { room: ROOM_ID, offer: peerConnection.localDescription });
        });
});

socket.on('offer', (data) => {
    if (!peerConnection) {
        createPeerConnection();
    }

    peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer))
        .then(() => peerConnection.createAnswer())
        .then(answer => peerConnection.setLocalDescription(answer))
        .then(() => {
            socket.emit('answer', { room: ROOM_ID, answer: peerConnection.localDescription });
        });
});

socket.on('answer', (data) => {
    peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
});

socket.on('ice-candidate', (data) => {
    if (peerConnection) {
        peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
    }
});

// Controls
toggleMicBtn.addEventListener('click', () => {
    const audioTrack = localStream.getAudioTracks()[0];
    audioTrack.enabled = !audioTrack.enabled;
    // UI update handled in interview.html script block
});

toggleCamBtn.addEventListener('click', () => {
    const videoTrack = localStream.getVideoTracks()[0];
    videoTrack.enabled = !videoTrack.enabled;
    // UI update handled in interview.html script block
});

shareScreenBtn.addEventListener('click', () => {
    const icon = shareScreenBtn.querySelector('.material-icons');

    if (screenStream) {
        // Stop screen share
        const videoTrack = localStream.getVideoTracks()[0];

        if (peerConnection) {
            const sender = peerConnection.getSenders().find(s => s.track.kind === 'video');
            if (sender) {
                sender.replaceTrack(videoTrack);
            }
        }

        screenStream.getTracks().forEach(track => track.stop());
        screenStream = null;

        shareScreenBtn.classList.remove('active-off');
        icon.textContent = 'present_to_all';
        shareScreenBtn.title = 'Share Screen';

        localVideo.srcObject = localStream;
    } else {
        // Start screen share
        navigator.mediaDevices.getDisplayMedia({ video: true })
            .then(stream => {
                screenStream = stream;
                const screenTrack = screenStream.getVideoTracks()[0];

                if (peerConnection) {
                    const sender = peerConnection.getSenders().find(s => s.track.kind === 'video');
                    if (sender) {
                        sender.replaceTrack(screenTrack);
                    }
                }

                shareScreenBtn.classList.add('active-off');
                icon.textContent = 'cancel_presentation';
                shareScreenBtn.title = 'Stop Sharing';

                localVideo.srcObject = screenStream;

                screenTrack.onended = () => {
                    // Handle case where user stops sharing via browser UI
                    if (screenStream) {
                        shareScreenBtn.click();
                    }
                };
            })
            .catch(error => {
                console.error('Error sharing screen:', error);
            });
    }
});

window.addEventListener('beforeunload', () => {
    socket.emit('leave', { room: ROOM_ID, username: USERNAME });
});
