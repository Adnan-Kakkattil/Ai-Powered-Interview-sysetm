const Queue = require('bull');
const { Notification } = require('../models/Notification');
const { dispatchNotification } = require('../services/notification.service');

const REDIS_URL = process.env.REDIS_URL || 'redis://127.0.0.1:6379';

const notificationQueue = new Queue('notification-queue', REDIS_URL, {
  defaultJobOptions: {
    attempts: 3,
    backoff: {
      type: 'exponential',
      delay: 1000,
    },
    removeOnComplete: true,
    removeOnFail: false,
  },
});

notificationQueue.process(async (job) => {
  const { notificationId } = job.data;
  const notification = await Notification.findById(notificationId).populate('recipient');
  if (!notification) {
    throw new Error(`Notification ${notificationId} not found`);
  }

  try {
    await dispatchNotification(notification);
  } catch (error) {
    notification.status = 'failed';
    notification.attempts += 1;
    notification.lastError = error.message;
    await notification.save();
    throw error;
  }
});

notificationQueue.on('failed', (job, err) => {
  console.error(`Notification job ${job.id} failed:`, err.message);
});

module.exports = notificationQueue;
