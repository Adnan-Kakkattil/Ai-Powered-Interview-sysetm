const { Router } = require('express');
const { body } = require('express-validator');
const { getProfile, upsertProfile, getInterviews } = require('../controllers/candidate.controller');
const { authenticate, authorize } = require('../middlewares/auth');
const { USER_ROLES } = require('../models/User');

const router = Router();

router.use(authenticate, authorize(USER_ROLES.CANDIDATE));

router.get('/me', getProfile);

router.put(
  '/me',
  [
    body('resumeUrl').optional().isURL().withMessage('resumeUrl must be a valid URL'),
    body('portfolioUrl').optional().isURL().withMessage('portfolioUrl must be a valid URL'),
    body('skills').optional().isArray(),
    body('tags').optional().isArray(),
  ],
  upsertProfile,
);

router.get('/interviews', getInterviews);

module.exports = router;
