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
