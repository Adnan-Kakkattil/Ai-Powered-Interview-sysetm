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

module.exports = {
  startInterview,
  endInterview,
  getInterviewLogs,
};
