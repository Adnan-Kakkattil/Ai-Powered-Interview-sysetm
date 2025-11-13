"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.registerInterviewNamespace = exports.INTERVIEW_NAMESPACE = void 0;
const roomService_1 = require("../services/roomService");
const logger_1 = require("../utils/logger");
exports.INTERVIEW_NAMESPACE = '/interview';
const registerInterviewNamespace = (io) => {
    const namespace = io.of(exports.INTERVIEW_NAMESPACE);
    const broadcastPresence = (interviewId) => {
        namespace.to(interviewId).emit('presence:update', roomService_1.roomService.listParticipants(interviewId));
        if (roomService_1.roomHelpers.areBothSidesPresent(interviewId)) {
            namespace.to(interviewId).emit('call:ready');
        }
    };
    namespace.on('connection', (socket) => {
        logger_1.logger.debug('Socket connected', { socketId: socket.id });
        socket.on('joinRoom', ({ interviewId, role, identity }) => {
            if (!interviewId || !role || !identity) {
                return;
            }
            socket.join(interviewId);
            roomService_1.roomService.join(interviewId, { socketId: socket.id, role, identity });
            broadcastPresence(interviewId);
            logger_1.logger.info('Participant joined', { interviewId, socketId: socket.id, role });
        });
        socket.on('signal', ({ interviewId, payload }) => {
            if (!interviewId || !payload)
                return;
            socket.to(interviewId).emit('signal', { socketId: socket.id, payload });
        });
        socket.on('offer', ({ interviewId, payload }) => {
            if (!interviewId || !payload)
                return;
            socket.to(interviewId).emit('offer', { socketId: socket.id, payload });
        });
        socket.on('answer', ({ interviewId, payload }) => {
            if (!interviewId || !payload)
                return;
            socket.to(interviewId).emit('answer', { socketId: socket.id, payload });
        });
        socket.on('iceCandidate', ({ interviewId, payload }) => {
            if (!interviewId || !payload)
                return;
            socket.to(interviewId).emit('iceCandidate', { socketId: socket.id, payload });
        });
        socket.on('leaveRoom', ({ interviewId }) => {
            if (!interviewId)
                return;
            socket.leave(interviewId);
            roomService_1.roomService.leave(interviewId, socket.id);
            broadcastPresence(interviewId);
        });
        socket.on('disconnect', () => {
            roomService_1.roomService.leaveAll(socket.id);
            logger_1.logger.debug('Socket disconnected', { socketId: socket.id });
        });
    });
};
exports.registerInterviewNamespace = registerInterviewNamespace;
//# sourceMappingURL=interviewNamespace.js.map