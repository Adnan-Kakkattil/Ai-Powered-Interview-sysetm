const chatBtn = document.getElementById('toggleChat');
const chatSidebar = document.getElementById('chatSidebar');
const closeChatBtn = document.getElementById('closeChat');
const chatInput = document.getElementById('chatInput');
const sendChatBtn = document.getElementById('sendChat');
const chatMessages = document.getElementById('chatMessages');

// Toggle Chat Sidebar
if (chatBtn && chatSidebar) {
    chatBtn.addEventListener('click', () => {
        chatSidebar.classList.toggle('open');
        if (chatSidebar.classList.contains('open')) {
            chatBtn.classList.add('active-off'); // Highlight button when open
            if (chatInput) chatInput.focus();
        } else {
            chatBtn.classList.remove('active-off');
        }
    });
}

if (closeChatBtn && chatSidebar) {
    closeChatBtn.addEventListener('click', () => {
        chatSidebar.classList.remove('open');
        if (chatBtn) chatBtn.classList.remove('active-off');
    });
}

// Send Message
function sendMessage() {
    if (!chatInput) return;
    const message = chatInput.value.trim();
    if (message) {
        const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        // Emit to server
        if (typeof socket !== 'undefined') socket.emit('chat-message', {
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

if (sendChatBtn) sendChatBtn.addEventListener('click', sendMessage);

if (chatInput) {
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
}

// Receive Message
if (typeof socket !== 'undefined') {
    socket.on('chat-message', (data) => {
        addMessageToUI(data.username, data.message, data.timestamp, false);

        // If sidebar exists and is closed, show toast
        if (chatSidebar && !chatSidebar.classList.contains('open')) {
            if (typeof showToast === 'function') showToast(`New message from ${data.username}`);
        }
    });
}

function addMessageToUI(username, message, timestamp, isSelf) {
    if (!chatMessages) return;

    // Match current Tailwind chat layout in templates/interview.html
    const wrapper = document.createElement('div');
    wrapper.className = `flex flex-col ${isSelf ? 'items-end' : 'items-start'}`;

    const metaDiv = document.createElement('div');
    metaDiv.className = 'text-[10px] text-gray-500 mb-1';
    metaDiv.textContent = `${isSelf ? 'You' : username} • ${timestamp}`;

    const bubble = document.createElement('div');
    bubble.className = `max-w-[85%] px-3 py-2 rounded-lg text-xs ${
        isSelf
            ? 'bg-purple-600/20 text-purple-200 border border-purple-600/30'
            : 'bg-gray-800 text-gray-300 border border-gray-700'
    }`;
    bubble.textContent = message;

    wrapper.appendChild(metaDiv);
    wrapper.appendChild(bubble);
    chatMessages.appendChild(wrapper);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
