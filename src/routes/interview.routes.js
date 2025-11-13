const { Router } = require('express');
const { body, param } = require('express-validator');
const {
  startInterview,
  endInterview,
  getInterviewLogs,
  updateInterviewSchedule,
  cancelInterview,
} = require('../controllers/interview.controller');
const { authenticate, authorize } = require('../middlewares/auth');
const { USER_ROLES } = require('../models/User');

const router = Router();

router.use(authenticate);

router.post(
  '/:interviewId/start',
  param('interviewId').isMongoId(),
  authorize(USER_ROLES.ADMIN, USER_ROLES.INTERVIEWER),
  startInterview,
);

router.post(
  '/:interviewId/end',
  param('interviewId').isMongoId(),
  authorize(USER_ROLES.ADMIN, USER_ROLES.INTERVIEWER),
  body('feedback').optional().isString(),
  body('attentivenessScore').optional().isNumeric(),
  endInterview,
);

router.get(
  '/:interviewId/logs',
  param('interviewId').isMongoId(),
  authorize(USER_ROLES.ADMIN),
  getInterviewLogs,
);

router.patch(
  '/:interviewId',
  [
    param('interviewId').isMongoId(),
    authorize(USER_ROLES.ADMIN),
    body('schedule.start').optional().isISO8601(),
    body('schedule.end').optional().isISO8601(),
    body('meetingRoomId').optional().notEmpty(),
    body('status').optional().isIn(['scheduled', 'live', 'completed', 'cancelled']),
    body('codingTaskId').optional().isMongoId(),
    body('lotId').optional().isMongoId(),
  ],
  updateInterviewSchedule,
);

router.post(
  '/:interviewId/cancel',
  param('interviewId').isMongoId(),
  authorize(USER_ROLES.ADMIN),
  cancelInterview,
);

module.exports = router;
