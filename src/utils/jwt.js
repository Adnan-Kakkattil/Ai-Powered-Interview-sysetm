const jwt = require('jsonwebtoken');

const JWT_SECRET = process.env.JWT_SECRET;
const JWT_EXPIRES_IN = process.env.JWT_EXPIRES_IN || '1d';

if (!JWT_SECRET) {
  console.warn('Warning: JWT_SECRET is not set. Authentication tokens will be insecure.');
}

const signToken = (payload, options = {}) =>
  jwt.sign(payload, JWT_SECRET, { expiresIn: JWT_EXPIRES_IN, ...options });

const verifyToken = (token) => jwt.verify(token, JWT_SECRET);

module.exports = {
  signToken,
  verifyToken,
};
