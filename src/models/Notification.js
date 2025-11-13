const mongoose = require('mongoose');

const { Schema } = mongoose;

const notificationSchema = new Schema(
  {
    recipient: {
      type: Schema.Types.ObjectId,
      ref: 'User',
      required: true,
    },
    channel: {
      type: String,
      enum: ['email', 'sms', 'push'],
      required: true,
    },
    template: {
      type: String,
      required: true,
    },
    payload: Schema.Types.Mixed,
    scheduleAt: Date,
    status: {
      type: String,
      enum: ['pending', 'queued', 'sent', 'failed'],
      default: 'pending',
    },
    attempts: {
      type: Number,
      default: 0,
    },
    lastError: String,
  },
  { timestamps: true },
);

const Notification = mongoose.model('Notification', notificationSchema);

module.exports = { Notification };
