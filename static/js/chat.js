const chatBtn = document.getElementById('toggleChat');
const chatSidebar = document.getElementById('chatSidebar');
const closeChatBtn = document.getElementById('closeChat');
const chatInput = document.getElementById('chatInput');
const sendChatBtn = document.getElementById('sendChat');
const chatMessages = document.getElementById('chatMessages');

// Toggle Chat Sidebar
chatBtn.addEventListener('click', () => {
    chatSidebar.classList.toggle('open');
    if (chatSidebar.classList.contains('open')) {
        chatBtn.classList.add('active-off'); // Highlight button when open
        chatInput.focus();
    } else {
        chatBtn.classList.remove('active-off');
    }
});

closeChatBtn.addEventListener('click', () => {
    chatSidebar.classList.remove('open');
    chatBtn.classList.remove('active-off');
});

// Send Message
function sendMessage() {
    const message = chatInput.value.trim();
    if (message) {
        const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        // Emit to server
        socket.emit('chat-message', {
            room: ROOM_ID,
            message: message,
            username: USERNAME,
            timestamp: timestamp
        });

        // Add to local UI immediately
        addMessageToUI(USERNAME, message, timestamp, true);

        chatInput.value = '';
    }
}

sendChatBtn.addEventListener('click', sendMessage);

chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

// Receive Message
socket.on('chat-message', (data) => {
    addMessageToUI(data.username, data.message, data.timestamp, false);

    // If sidebar is closed, show a notification dot or toast
    if (!chatSidebar.classList.contains('open')) {
        showToast(`New message from ${data.username}`);
    }
});

function addMessageToUI(username, message, timestamp, isSelf) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${isSelf ? 'self' : 'remote'}`;

    const metaDiv = document.createElement('div');
    metaDiv.className = 'message-meta';
    metaDiv.innerHTML = `<span class="message-user">${isSelf ? 'You' : username}</span> <span class="message-time">${timestamp}</span>`;

    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';
    contentDiv.textContent = message;

    messageDiv.appendChild(metaDiv);
    messageDiv.appendChild(contentDiv);

    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
