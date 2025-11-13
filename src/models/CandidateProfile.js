const mongoose = require('mongoose');

const { Schema } = mongoose;

const candidateProfileSchema = new Schema(
  {
    user: {
      type: Schema.Types.ObjectId,
      ref: 'User',
      required: true,
      unique: true,
    },
    resumeUrl: String,
    portfolioUrl: String,
    skills: [String],
    tags: [String],
    lotStatus: {
      type: String,
      enum: ['unassigned', 'shortlisted', 'pending', 'interviewed', 'rejected', 'hired'],
      default: 'unassigned',
    },
    notes: String,
  },
  { timestamps: true },
);

const CandidateProfile = mongoose.model('CandidateProfile', candidateProfileSchema);

module.exports = { CandidateProfile };
