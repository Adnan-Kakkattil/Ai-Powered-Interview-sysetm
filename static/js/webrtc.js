const socket = io();
const localVideo = document.getElementById('localVideo');
const remoteVideo = document.getElementById('remoteVideo');
const toggleMicBtn = document.getElementById('toggleMic');
const toggleCamBtn = document.getElementById('toggleCam');

let localStream;
let peerConnection;
const config = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' }
    ]
};

// Get user media
navigator.mediaDevices.getUserMedia({ video: true, audio: true })
    .then(stream => {
        localStream = stream;
        localVideo.srcObject = stream;
        socket.emit('join', { room: ROOM_ID });
    })
    .catch(error => {
        console.error('Error accessing media devices:', error);
        alert('Could not access camera/microphone. Please allow permissions.');
    });

socket.on('joined', () => {
    // Initiator creates offer
    createPeerConnection();
    localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

    peerConnection.createOffer()
        .then(offer => peerConnection.setLocalDescription(offer))
        .then(() => {
            socket.emit('offer', { room: ROOM_ID, offer: peerConnection.localDescription });
        });
});

socket.on('offer', (data) => {
    if (!peerConnection) {
        createPeerConnection();
        localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));
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

socket.on('user-connected', () => {
    console.log('User connected');
});

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
}

// Controls
toggleMicBtn.addEventListener('click', () => {
    const audioTrack = localStream.getAudioTracks()[0];
    audioTrack.enabled = !audioTrack.enabled;
    toggleMicBtn.textContent = audioTrack.enabled ? 'Mute Mic' : 'Unmute Mic';
});

toggleCamBtn.addEventListener('click', () => {
    const videoTrack = localStream.getVideoTracks()[0];
    videoTrack.enabled = !videoTrack.enabled;
    toggleCamBtn.textContent = videoTrack.enabled ? 'Turn Off Camera' : 'Turn On Camera';
});
