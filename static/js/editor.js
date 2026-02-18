// Initialize CodeMirror
const editor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
    mode: 'python',
    theme: 'dracula',
    lineNumbers: true,
    autoCloseBrackets: true,
    indentUnit: 4,
    tabSize: 4,
    lineWrapping: true,
});

// Set initial size
editor.setSize("100%", "100%");

// Anti-cheating: Disable copy/paste/cut/contextmenu
const editorWrapper = editor.getWrapperElement();
['copy', 'paste', 'cut', 'contextmenu'].forEach(event => {
    editorWrapper.addEventListener(event, (e) => {
        e.preventDefault();
        showToast('Copy/Paste is disabled for this interview.');
        return false;
    });
});

// Real-time sync
let isRemoteUpdate = false;

editor.on('change', (instance, changeObj) => {
    if (isRemoteUpdate) return;

    const code = instance.getValue();
    socket.emit('code-change', { room: ROOM_ID, code: code });
});

socket.on('code-update', (data) => {
    isRemoteUpdate = true;
    const cursor = editor.getCursor();
    editor.setValue(data.code);
    editor.setCursor(cursor);
    isRemoteUpdate = false;
});

// --- Run Code ---
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function setTerminalContent(html) {
    const el = document.getElementById('terminalOutput');
    if (el) el.innerHTML = html;
}

const runCodeBtn = document.getElementById('runCodeBtn');
const languageSelect = document.getElementById('languageSelect');

if (runCodeBtn) {
    runCodeBtn.addEventListener('click', async () => {
        const code = editor.getValue();
        const language = (languageSelect && languageSelect.value) ? languageSelect.value : 'python';
        const terminalEl = document.getElementById('terminalOutput');
        if (!terminalEl) return;

        setTerminalContent('<span class="text-yellow-400">➜</span> Running code...');
        runCodeBtn.disabled = true;
        runCodeBtn.classList.add('opacity-70', 'cursor-not-allowed');

        try {
            const res = await fetch(`/interview/${ROOM_ID}/run-code`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code, language: language })
            });
            const data = await res.json().catch(() => ({}));

            let out = '';
            if (data.stdout) {
                out += '<span class="text-gray-300">' + escapeHtml(data.stdout).replace(/\n/g, '<br>') + '</span>';
            }
            if (data.stderr) {
                out += (out ? '<br>' : '') + '<span class="text-red-400">' + escapeHtml(data.stderr).replace(/\n/g, '<br>') + '</span>';
            }
            const exitPart = '<br><span class="text-gray-500">Exit code: ' + (data.exit_code !== undefined ? data.exit_code : '—') + '</span>';
            setTerminalContent('<span class="text-green-400">➜</span> ' + (out || '<span class="text-gray-500">No output</span>') + exitPart);
        } catch (err) {
            setTerminalContent('<span class="text-red-400">➜</span> Request failed: ' + escapeHtml(err.message));
        } finally {
            runCodeBtn.disabled = false;
            runCodeBtn.classList.remove('opacity-70', 'cursor-not-allowed');
        }
    });
}
