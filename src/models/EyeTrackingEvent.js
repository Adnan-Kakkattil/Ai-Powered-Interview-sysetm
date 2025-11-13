const mongoose = require('mongoose');

const { Schema } = mongoose;

const eyeTrackingEventSchema = new Schema(
  {
    interview: {
      type: Schema.Types.ObjectId,
      ref: 'Interview',
      required: true,
    },
    timestamp: {
      type: Date,
      default: Date.now,
    },
    gazeData: Schema.Types.Mixed,
    focusScore: Number,
    metadata: Schema.Types.Mixed,
  },
  { timestamps: true },
);

const EyeTrackingEvent = mongoose.model('EyeTrackingEvent', eyeTrackingEventSchema);

module.exports = { EyeTrackingEvent };
