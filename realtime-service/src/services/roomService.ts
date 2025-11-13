import { randomUUID } from 'crypto';
import { logger } from '../utils/logger';

type ParticipantRole = 'candidate' | 'interviewer' | 'observer';

type Participant = {
  socketId: string;
  identity: string;
  role: ParticipantRole;
};

type RoomState = {
  interviewId: string;
  roomName: string;
  participants: Map<string, Participant>; // keyed by socketId
  livekitRoom?: string;
  metadata?: Record<string, unknown>;
};

const rooms = new Map<string, RoomState>();

const ensureRoom = (interviewId: string): RoomState => {
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

const pruneRoom = (interviewId: string) => {
  const room = rooms.get(interviewId);
  if (room && room.participants.size === 0) {
    rooms.delete(interviewId);
    logger.info('Disposed empty interview room', { interviewId });
  }
};

export const roomService = {
  join(interviewId: string, participant: Participant) {
    const room = ensureRoom(interviewId);
    room.participants.set(participant.socketId, participant);
    logger.debug('Participant joined room', { interviewId, participant });
    return room;
  },

  leave(interviewId: string, socketId: string) {
    const room = rooms.get(interviewId);
    if (!room) return;
    room.participants.delete(socketId);
    logger.debug('Participant left room', { interviewId, socketId });
    pruneRoom(interviewId);
  },

  leaveAll(socketId: string) {
    rooms.forEach((room, interviewId) => {
      if (room.participants.has(socketId)) {
        room.participants.delete(socketId);
        pruneRoom(interviewId);
      }
    });
  },

  listParticipants(interviewId: string) {
    const room = rooms.get(interviewId);
    if (!room) return [];
    return Array.from(room.participants.values());
  },

  getRoom(interviewId: string) {
    return rooms.get(interviewId);
  },

  assignLivekitRoom(interviewId: string, roomName?: string) {
    const room = ensureRoom(interviewId);
    room.livekitRoom = roomName ?? `lk-${randomUUID()}`;
    return room.livekitRoom;
  },
};

export const roomHelpers = {
  areBothSidesPresent(interviewId: string) {
    const room = rooms.get(interviewId);
    if (!room) return false;
    const roles = new Set(Array.from(room.participants.values()).map((p) => p.role));
    return roles.has('candidate') && roles.has('interviewer');
  },
};
