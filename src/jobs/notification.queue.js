const Queue = require('bull');
const { Notification } = require('../models/Notification');
const { dispatchNotification } = require('../services/notification.service');

const REDIS_URL = process.env.REDIS_URL;

const createDisabledQueue = (reason) => {
  console.warn(`Notification queue disabled: ${reason}`);
  return {
    add: async () => {
      console.warn(`Skipping notification dispatch (queue disabled: ${reason})`);
    },
  };
};

let notificationQueue = null;

if (!REDIS_URL) {
  notificationQueue = createDisabledQueue('REDIS_URL not configured');
} else {
  try {
    const queue = new Queue('notification-queue', REDIS_URL, {
      defaultJobOptions: {
        attempts: 3,
        backoff: {
          type: 'exponential',
          delay: 1000,
        },
        removeOnComplete: true,
        removeOnFail: false,
      },
      redis: {
        maxRetriesPerRequest: null,
        enableReadyCheck: false,
      },
    });

    queue.process(async (job) => {
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

    queue.on('failed', (job, err) => {
      console.error(`Notification job ${job.id} failed:`, err.message);
    });

    queue.on('error', (err) => {
      console.error('Notification queue connection error:', err.message);
    });

    queue.isReady().catch(async (err) => {
      console.warn('Notification queue not ready. Falling back to disabled mode:', err.message);
      try {
        await queue.close();
      } catch (closeErr) {
        console.warn('Error closing notification queue:', closeErr.message);
      }
      queue.add = async () => {
        console.warn(`Skipping notification dispatch (queue disabled: ${err.message})`);
      };
    });

    notificationQueue = queue;
  } catch (error) {
    notificationQueue = createDisabledQueue(error.message);
  }
}

module.exports = notificationQueue;
