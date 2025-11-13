const bcrypt = require('bcryptjs');

const SALT_ROUNDS = parseInt(process.env.BCRYPT_SALT_ROUNDS || '10', 10);

const hashPassword = async (plainPassword) => {
  const salt = await bcrypt.genSalt(SALT_ROUNDS);
  return bcrypt.hash(plainPassword, salt);
};

module.exports = {
  hashPassword,
};
