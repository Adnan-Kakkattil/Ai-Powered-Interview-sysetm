const { validationResult } = require('express-validator');
const { User, USER_ROLES } = require('../models/User');
const { CandidateProfile } = require('../models/CandidateProfile');
const { Lot } = require('../models/Lot');
const { Interview } = require('../models/Interview');
const { Notification } = require('../models/Notification');
const { hashPassword } = require('../utils/password');
const notificationQueue = require('../jobs/notification.queue');
const { queueNotification } = require('../services/notification.service');

const createCandidate = async (req, res, next) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { name, email, password = 'Temp@1234', resumeUrl, skills = [] } = req.body;

    const existingUser = await User.findOne({ email });
    if (existingUser) {
      return res.status(409).json({ message: 'Candidate already exists' });
    }

    const passwordHash = await hashPassword(password);

    const user = await User.create({
      name,
      email,
      passwordHash,
      role: USER_ROLES.CANDIDATE,
      status: 'invited',
    });

    const profile = await CandidateProfile.create({
      user: user._id,
      resumeUrl,
      skills,
    });

    return res.status(201).json({ user, profile });
  } catch (error) {
    return next(error);
  }
};

const listCandidates = async (req, res, next) => {
  try {
    const candidates = await User.find({ role: USER_ROLES.CANDIDATE })
      .lean()
      .select('name email status createdAt');

    const profiles = await CandidateProfile.find({
      user: { $in: candidates.map((candidate) => candidate._id) },
    }).lean();

    const profileMap = profiles.reduce((acc, profile) => {
      acc[profile.user.toString()] = profile;
      return acc;
    }, {});

    const merged = candidates.map((candidate) => ({
      ...candidate,
      profile: profileMap[candidate._id.toString()] || null,
    }));

    return res.json({ candidates: merged });
  } catch (error) {
    return next(error);
  }
};

const createLot = async (req, res, next) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { name, description, criteria } = req.body;

    const lot = await Lot.create({
      name,
      description,
      criteria,
      createdBy: req.user._id,
      status: 'active',
    });

    return res.status(201).json({ lot });
  } catch (error) {
    return next(error);
  }
};

const updateLot = async (req, res, next) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { lotId } = req.params;
    const updates = req.body;

    const lot = await Lot.findByIdAndUpdate(lotId, updates, { new: true });

    if (!lot) {
      return res.status(404).json({ message: 'Lot not found' });
    }

    return res.json({ lot });
  } catch (error) {
    return next(error);
  }
};

const addCandidatesToLot = async (req, res, next) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { lotId } = req.params;
    const { candidateIds } = req.body;

    const lot = await Lot.findByIdAndUpdate(
      lotId,
      {
        $addToSet: {
          candidates: { $each: candidateIds },
        },
      },
      { new: true },
    );

    if (!lot) {
      return res.status(404).json({ message: 'Lot not found' });
    }

    await CandidateProfile.updateMany(
      { _id: { $in: candidateIds } },
      { $set: { lotStatus: 'shortlisted' } },
    );

    return res.json({ lot });
  } catch (error) {
    return next(error);
  }
};

const scheduleInterview = async (req, res, next) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { candidateId, interviewerId, lotId, codingTaskId, schedule, meetingRoomId } = req.body;

    const interview = await Interview.create({
      candidate: candidateId,
      interviewer: interviewerId,
      lot: lotId,
      codingTask: codingTaskId,
      schedule,
      meetingRoomId,
    });

    const notification = await Notification.create({
      recipient: candidateId,
      channel: 'email',
      template: 'interview_scheduled',
      payload: {
        meetingRoomId,
        schedule,
      },
      status: 'queued',
    });

    await queueNotification(notification._id, notificationQueue);

    return res.status(201).json({ interview });
  } catch (error) {
    return next(error);
  }
};

const listInterviews = async (req, res, next) => {
  try {
    const interviews = await Interview.find()
      .populate('candidate', 'name email')
      .populate('interviewer', 'name email')
      .populate('codingTask', 'title difficulty')
      .populate('lot', 'name');

    return res.json({ interviews });
  } catch (error) {
    return next(error);
  }
};

module.exports = {
  createCandidate,
  listCandidates,
  createLot,
  updateLot,
  addCandidatesToLot,
  scheduleInterview,
  listInterviews,
};
