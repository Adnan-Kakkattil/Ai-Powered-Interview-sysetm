const mongoose = require('mongoose');

const { Schema } = mongoose;

const lotSchema = new Schema(
  {
    name: {
      type: String,
      required: true,
      trim: true,
    },
    description: String,
    criteria: {
      experienceMin: Number,
      experienceMax: Number,
      skills: [String],
      tags: [String],
    },
    candidates: [
      {
        type: Schema.Types.ObjectId,
        ref: 'CandidateProfile',
      },
    ],
    status: {
      type: String,
      enum: ['draft', 'active', 'archived'],
      default: 'draft',
    },
    createdBy: {
      type: Schema.Types.ObjectId,
      ref: 'User',
      required: true,
    },
  },
  { timestamps: true },
);

const Lot = mongoose.model('Lot', lotSchema);

module.exports = { Lot };
