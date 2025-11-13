"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.roomHelpers = exports.roomService = void 0;
const crypto_1 = require("crypto");
const logger_1 = require("../utils/logger");
const rooms = new Map();
const ensureRoom = (interviewId) => {
    let room = rooms.get(interviewId);
    if (!room) {
        room = {
            interviewId,
            roomName: `interview-${interviewId}`,
            participants: new Map(),
        };
        rooms.set(interviewId, room);
    }
    return room;
};
const pruneRoom = (interviewId) => {
    const room = rooms.get(interviewId);
    if (room && room.participants.size === 0) {
        rooms.delete(interviewId);
        logger_1.logger.info('Disposed empty interview room', { interviewId });
    }
};
exports.roomService = {
    join(interviewId, participant) {
        const room = ensureRoom(interviewId);
        room.participants.set(participant.socketId, participant);
        logger_1.logger.debug('Participant joined room', { interviewId, participant });
        return room;
    },
    leave(interviewId, socketId) {
        const room = rooms.get(interviewId);
        if (!room)
            return;
        room.participants.delete(socketId);
        logger_1.logger.debug('Participant left room', { interviewId, socketId });
        pruneRoom(interviewId);
    },
    leaveAll(socketId) {
        rooms.forEach((room, interviewId) => {
            if (room.participants.has(socketId)) {
                room.participants.delete(socketId);
                pruneRoom(interviewId);
            }
        });
    },
    listParticipants(interviewId) {
        const room = rooms.get(interviewId);
        if (!room)
            return [];
        return Array.from(room.participants.values());
    },
    getRoom(interviewId) {
        return rooms.get(interviewId);
    },
    assignLivekitRoom(interviewId, roomName) {
        const room = ensureRoom(interviewId);
        room.livekitRoom = roomName ?? `lk-${(0, crypto_1.randomUUID)()}`;
        return room.livekitRoom;
    },
};
exports.roomHelpers = {
    areBothSidesPresent(interviewId) {
        const room = rooms.get(interviewId);
        if (!room)
            return false;
        const roles = new Set(Array.from(room.participants.values()).map((p) => p.role));
        return roles.has('candidate') && roles.has('interviewer');
    },
};
//# sourceMappingURL=roomService.js.map