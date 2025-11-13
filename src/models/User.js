const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');

const { Schema } = mongoose;

const USER_ROLES = {
  ADMIN: 'admin',
  CANDIDATE: 'candidate',
  INTERVIEWER: 'interviewer',
};

const userSchema = new Schema(
  {
    name: {
      type: String,
      required: true,
      trim: true,
    },
    email: {
      type: String,
      required: true,
      unique: true,
      lowercase: true,
      trim: true,
    },
    passwordHash: {
      type: String,
      required: true,
    },
    role: {
      type: String,
      enum: Object.values(USER_ROLES),
      required: true,
    },
    status: {
      type: String,
      enum: ['active', 'inactive', 'invited'],
      default: 'active',
    },
    lastLoginAt: Date,
    metadata: Schema.Types.Mixed,
  },
  { timestamps: true },
);

userSchema.methods.comparePassword = async function comparePassword(plainPassword) {
  return bcrypt.compare(plainPassword, this.passwordHash);
};

const User = mongoose.model('User', userSchema);

module.exports = {
  User,
  USER_ROLES,
};
