const mongoose = require('mongoose');

const { Schema } = mongoose;

const sessionLogSchema = new Schema(
  {
    interview: {
      type: Schema.Types.ObjectId,
      ref: 'Interview',
      required: true,
    },
    type: {
      type: String,
      enum: ['webrtc', 'code', 'eye-tracking', 'system'],
      required: true,
    },
    timestamp: {
      type: Date,
      default: Date.now,
    },
    data: Schema.Types.Mixed,
  },
  { timestamps: true },
);

const SessionLog = mongoose.model('SessionLog', sessionLogSchema);

module.exports = { SessionLog };
