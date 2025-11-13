import { Request, Response } from 'express';
import { interviewService } from '../services/interviewService';
import { logger } from '../utils/logger';

export const createJoinToken = (req: Request, res: Response) => {
  const { interviewId, participantId, role, displayName } = req.body ?? {};

  if (!interviewId || !participantId || !role || !displayName) {
    return res.status(400).json({ message: 'Missing required fields' });
  }

  try {
    const payload = interviewService.registerParticipant({
      interviewId,
      participantId,
      role,
      displayName,
    });
    return res.status(201).json(payload);
  } catch (error) {
    logger.error('Failed to issue join token', { error });
    return res.status(500).json({ message: 'Unable to issue join token' });
  }
};

