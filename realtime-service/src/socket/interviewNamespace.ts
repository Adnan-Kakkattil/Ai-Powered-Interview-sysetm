import { Server, Socket } from 'socket.io';
import { roomHelpers, roomService } from '../services/roomService';
import { logger } from '../utils/logger';

export const INTERVIEW_NAMESPACE = '/interview';

export const registerInterviewNamespace = (io: Server) => {
  const namespace = io.of(INTERVIEW_NAMESPACE);

  const broadcastPresence = (interviewId: string) => {
    namespace.to(interviewId).emit('presence:update', roomService.listParticipants(interviewId));
    if (roomHelpers.areBothSidesPresent(interviewId)) {
      namespace.to(interviewId).emit('call:ready');
    }
  };

  namespace.on('connection', (socket: Socket) => {
    logger.debug('Socket connected', { socketId: socket.id });

    socket.on('joinRoom', ({ interviewId, role, identity }) => {
      if (!interviewId || !role || !identity) {
        return;
      }
      socket.join(interviewId);
      roomService.join(interviewId, { socketId: socket.id, role, identity });
      broadcastPresence(interviewId);
      logger.info('Participant joined', { interviewId, socketId: socket.id, role });
    });

    socket.on('signal', ({ interviewId, payload }) => {
      if (!interviewId || !payload) return;
      socket.to(interviewId).emit('signal', { socketId: socket.id, payload });
    });

    socket.on('offer', ({ interviewId, payload }) => {
      if (!interviewId || !payload) return;
      socket.to(interviewId).emit('offer', { socketId: socket.id, payload });
    });

    socket.on('answer', ({ interviewId, payload }) => {
      if (!interviewId || !payload) return;
      socket.to(interviewId).emit('answer', { socketId: socket.id, payload });
    });

    socket.on('iceCandidate', ({ interviewId, payload }) => {
      if (!interviewId || !payload) return;
      socket.to(interviewId).emit('iceCandidate', { socketId: socket.id, payload });
    });

    socket.on('leaveRoom', ({ interviewId }) => {
      if (!interviewId) return;
      socket.leave(interviewId);
      roomService.leave(interviewId, socket.id);
      broadcastPresence(interviewId);
    });

    socket.on('disconnect', () => {
      roomService.leaveAll(socket.id);
      logger.debug('Socket disconnected', { socketId: socket.id });
    });
  });
};
