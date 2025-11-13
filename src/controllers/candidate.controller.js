const { validationResult } = require('express-validator');
const { CandidateProfile } = require('../models/CandidateProfile');
const { Interview } = require('../models/Interview');

const getProfile = async (req, res, next) => {
  try {
    const profile = await CandidateProfile.findOne({ user: req.user._id }).populate('user');
    if (!profile) {
      return res.json({ profile: null });
    }

    return res.json({ profile });
  } catch (error) {
    return next(error);
  }
};

const upsertProfile = async (req, res, next) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { resumeUrl, portfolioUrl, skills, tags, notes } = req.body;

    const profile = await CandidateProfile.findOneAndUpdate(
      { user: req.user._id },
      {
        $set: {
          resumeUrl,
          portfolioUrl,
          skills,
          tags,
          notes,
        },
      },
      { new: true, upsert: true },
    );

    return res.json({ profile });
  } catch (error) {
    return next(error);
  }
};

const getInterviews = async (req, res, next) => {
  try {
    const interviews = await Interview.find({ candidate: req.user._id })
      .populate('interviewer', 'name email')
      .populate('codingTask')
      .sort({ 'schedule.start': 1 });

    return res.json({ interviews });
  } catch (error) {
    return next(error);
  }
};

module.exports = {
  getProfile,
  upsertProfile,
  getInterviews,
};
