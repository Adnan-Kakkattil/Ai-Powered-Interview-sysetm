const { Router } = require('express');
const { body, param } = require('express-validator');
const {
  createCandidate,
  listCandidates,
  createLot,
  updateLot,
  addCandidatesToLot,
  scheduleInterview,
  listInterviews,
} = require('../controllers/admin.controller');
const { authenticate, authorize } = require('../middlewares/auth');
const { USER_ROLES } = require('../models/User');

const router = Router();

router.use(authenticate, authorize(USER_ROLES.ADMIN));

router.post(
  '/candidates',
  [
    body('name').notEmpty().withMessage('Name is required'),
    body('email').isEmail().withMessage('Valid email is required'),
    body('password').optional().isLength({ min: 8 }),
    body('resumeUrl').optional().isURL(),
  ],
  createCandidate,
);

router.get('/candidates', listCandidates);

router.post(
  '/lots',
  [body('name').notEmpty().withMessage('Name is required'), body('criteria').optional().isObject()],
  createLot,
);

router.patch(
  '/lots/:lotId',
  [param('lotId').isMongoId(), body().isObject().withMessage('Update payload must be an object')],
  updateLot,
);

router.post(
  '/lots/:lotId/candidates',
  [
    param('lotId').isMongoId(),
    body('candidateIds')
      .isArray({ min: 1 })
      .withMessage('candidateIds array with at least one id is required'),
  ],
  addCandidatesToLot,
);

router.post(
  '/interviews',
  [
    body('candidateId').isMongoId(),
    body('interviewerId').isMongoId(),
    body('lotId').optional().isMongoId(),
    body('codingTaskId').optional().isMongoId(),
    body('meetingRoomId').notEmpty(),
    body('schedule.start').isISO8601(),
    body('schedule.end').isISO8601(),
  ],
  scheduleInterview,
);

router.get('/interviews', listInterviews);

module.exports = router;
