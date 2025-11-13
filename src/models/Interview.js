const mongoose = require('mongoose');

const { Schema } = mongoose;

const interviewSchema = new Schema(
  {
    candidate: {
      type: Schema.Types.ObjectId,
      ref: 'User',
      required: true,
    },
    interviewer: {
      type: Schema.Types.ObjectId,
      ref: 'User',
      required: true,
    },
    lot: {
      type: Schema.Types.ObjectId,
      ref: 'Lot',
    },
    codingTask: {
      type: Schema.Types.ObjectId,
      ref: 'CodingTask',
    },
    meetingRoomId: {
      type: String,
      required: true,
    },
    schedule: {
      start: {
        type: Date,
        required: true,
      },
      end: {
        type: Date,
        required: true,
      },
    },
    status: {
      type: String,
      enum: ['scheduled', 'live', 'completed', 'cancelled'],
      default: 'scheduled',
    },
    feedback: String,
    attentivenessScore: Number,
  },
  { timestamps: true },
);

const Interview = mongoose.model('Interview', interviewSchema);

module.exports = { Interview };
