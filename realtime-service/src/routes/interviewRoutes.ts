import { Router } from 'express';
import { createJoinToken } from '../controllers/interviewController';

const router = Router();

router.post('/join-token', createJoinToken);

export default router;

