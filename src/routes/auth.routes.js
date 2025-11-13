const { Router } = require('express');
const { body } = require('express-validator');
const { registerAdmin, login } = require('../controllers/auth.controller');

const router = Router();

router.post(
  '/register-admin',
  [
    body('name').notEmpty().withMessage('Name is required'),
    body('email').isEmail().withMessage('Valid email is required'),
    body('password').isLength({ min: 8 }).withMessage('Password must be at least 8 characters'),
  ],
  registerAdmin,
);

router.post(
  '/login',
  [
    body('email').isEmail().withMessage('Valid email is required'),
    body('password').notEmpty().withMessage('Password is required'),
  ],
  login,
);

module.exports = router;
