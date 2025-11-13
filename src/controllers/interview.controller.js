const { Interview } = require('../models/Interview');
const { SessionLog } = require('../models/SessionLog');

const startInterview = async (req, res, next) => {
  try {
    const { interviewId } = req.params;
    const interview = await Interview.findByIdAndUpdate(
      interviewId,
      { $set: { status: 'live' } },
      { new: true },
    );

    if (!interview) {
      return res.status(404).json({ message: 'Interview not found' });
    }

    await SessionLog.create({
      interview: interviewId,
      type: 'system',
      data: { action: 'interview_started', user: req.user._id },
    });

    return res.json({ interview });
  } catch (error) {
    return next(error);
  }
};

const endInterview = async (req, res, next) => {
  try {
    const { interviewId } = req.params;
    const { feedback, attentivenessScore } = req.body;

    const interview = await Interview.findByIdAndUpdate(
      interviewId,
      {
        $set: {
          status: 'completed',
          feedback,
          attentivenessScore,
        },
      },
      { new: true },
    );

    if (!interview) {
      return res.status(404).json({ message: 'Interview not found' });
    }

    await SessionLog.create({
      interview: interviewId,
      type: 'system',
      data: { action: 'interview_completed', user: req.user._id, feedbackProvided: !!feedback },
    });

    return res.json({ interview });
  } catch (error) {
    return next(error);
  }
};

const getInterviewLogs = async (req, res, next) => {
  try {
    const { interviewId } = req.params;
    const logs = await SessionLog.find({ interview: interviewId }).sort({ createdAt: -1 });
    return res.json({ logs });
  } catch (error) {
    return next(error);
  }
};

const updateInterviewSchedule = async (req, res, next) => {
  try {
    const { interviewId } = req.params;
    const updates = {};

    if (req.body.schedule?.start || req.body.schedule?.end) {
      updates['schedule.start'] = req.body.schedule?.start;
      updates['schedule.end'] = req.body.schedule?.end;
    }

    if (req.body.meetingRoomId) {
      updates.meetingRoomId = req.body.meetingRoomId;
    }

    if (req.body.status) {
      updates.status = req.body.status;
    }

    if (req.body.codingTaskId) {
      updates.codingTask = req.body.codingTaskId;
    }

    if (req.body.lotId) {
      updates.lot = req.body.lotId;
    }

    const interview = await Interview.findByIdAndUpdate(
      interviewId,
      { $set: updates },
      { new: true },
    );

    if (!interview) {
      return res.status(404).json({ message: 'Interview not found' });
    }

    await SessionLog.create({
      interview: interviewId,
      type: 'system',
      data: { action: 'interview_updated', updates, user: req.user._id },
    });

    return res.json({ interview });
  } catch (error) {
    return next(error);
  }
};

const cancelInterview = async (req, res, next) => {
  try {
    const { interviewId } = req.params;

    const interview = await Interview.findByIdAndUpdate(
      interviewId,
      { $set: { status: 'cancelled' } },
      { new: true },
    );

    if (!interview) {
      return res.status(404).json({ message: 'Interview not found' });
    }

    await SessionLog.create({
      interview: interviewId,
      type: 'system',
      data: { action: 'interview_cancelled', user: req.user._id },
    });

    return res.json({ interview });
  } catch (error) {
    return next(error);
  }
};

module.exports = {
  startInterview,
  endInterview,
  getInterviewLogs,
  updateInterviewSchedule,
  cancelInterview,
};
