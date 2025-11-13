import { createJoinToken } from '../config/livekit';
import { roomService } from './roomService';
import { logger } from '../utils/logger';

export type JoinRequest = {
  interviewId: string;
  participantId: string;
  role: 'candidate' | 'interviewer';
  displayName: string;
};

export const interviewService = {
  registerParticipant({ interviewId, participantId, role, displayName }: JoinRequest) {
    const roomName = roomService.assignLivekitRoom(interviewId);
    const identity = `${role}-${participantId}`;
    const token = createJoinToken({
      roomName,
      identity,
      metadata: { interviewId, role, displayName },
    });

    logger.info('Issued LiveKit token', { interviewId, role });
    return { token, roomName };
  },
};
