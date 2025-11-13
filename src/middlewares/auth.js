const { verifyToken } = require('../utils/jwt');
const { User } = require('../models/User');

const authenticate = async (req, res, next) => {
  try {
    const authHeader = req.headers.authorization || req.cookies?.token;

    if (!authHeader) {
      return res.status(401).json({ message: 'Authentication required' });
    }

    const token = authHeader.startsWith('Bearer ') ? authHeader.split(' ')[1] : authHeader;
    const decoded = verifyToken(token);

    const user = await User.findById(decoded.sub);

    if (!user) {
      return res.status(401).json({ message: 'User not found' });
    }

    req.user = user;
    return next();
  } catch (error) {
    if (process.env.NODE_ENV !== 'production') {
      console.warn('Auth middleware failed:', error.message);
    }
    return res.status(401).json({ message: 'Invalid or expired token' });
  }
};

const authorize =
  (...roles) =>
  (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ message: 'Authentication required' });
    }

    if (!roles.includes(req.user.role)) {
      return res.status(403).json({ message: 'Insufficient permissions' });
    }

    return next();
  };

module.exports = {
  authenticate,
  authorize,
};
