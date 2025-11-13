const INTERVIEW_NAMESPACE = '/interview';

const registerInterviewNamespace = (io) => {
  const interviewNamespace = io.of(INTERVIEW_NAMESPACE);

  interviewNamespace.on('connection', (socket) => {
    socket.on('joinRoom', ({ interviewId, role }) => {
      if (!interviewId) return;
      socket.join(interviewId);
      interviewNamespace.to(interviewId).emit('userJoined', { socketId: socket.id, role });
    });

    socket.on('signal', ({ interviewId, payload }) => {
      if (!interviewId) return;
      socket.to(interviewId).emit('signal', { socketId: socket.id, payload });
    });

    socket.on('codeUpdate', ({ interviewId, codeSnapshot }) => {
      if (!interviewId) return;
      socket.to(interviewId).emit('codeUpdate', { codeSnapshot });
    });

    socket.on('eyeTracking', ({ interviewId, metrics }) => {
      if (!interviewId) return;
      socket.to(interviewId).emit('eyeTracking', { metrics, source: socket.id });
    });

    socket.on('chatMessage', ({ interviewId, message }) => {
      if (!interviewId) return;
      interviewNamespace.to(interviewId).emit('chatMessage', {
        message,
        from: socket.id,
        timestamp: Date.now(),
      });
    });

    socket.on('leaveRoom', ({ interviewId }) => {
      if (!interviewId) return;
      socket.leave(interviewId);
      interviewNamespace.to(interviewId).emit('userLeft', { socketId: socket.id });
    });
  });
};

module.exports = registerInterviewNamespace;
