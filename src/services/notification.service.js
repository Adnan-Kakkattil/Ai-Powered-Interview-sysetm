const sgMail = require('@sendgrid/mail');
const twilio = require('twilio');
const { Notification } = require('../models/Notification');

const SENDGRID_API_KEY = process.env.SENDGRID_API_KEY;
const TWILIO_ACCOUNT_SID = process.env.TWILIO_ACCOUNT_SID;
const TWILIO_AUTH_TOKEN = process.env.TWILIO_AUTH_TOKEN;
const TWILIO_FROM_NUMBER = process.env.TWILIO_FROM_NUMBER;
const EMAIL_FROM = process.env.EMAIL_FROM || 'no-reply@smart-interview.local';

if (SENDGRID_API_KEY) {
  sgMail.setApiKey(SENDGRID_API_KEY);
}

const twilioClient =
  TWILIO_ACCOUNT_SID && TWILIO_AUTH_TOKEN ? twilio(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN) : null;

const sendEmail = async ({ to, subject, html, text }) => {
  if (!SENDGRID_API_KEY) {
    throw new Error('SendGrid API key not configured');
  }

  await sgMail.send({
    to,
    from: EMAIL_FROM,
    subject,
    html,
    text,
  });
};

const sendSms = async ({ to, body }) => {
  if (!twilioClient || !TWILIO_FROM_NUMBER) {
    throw new Error('Twilio credentials not configured');
  }

  await twilioClient.messages.create({
    to,
    from: TWILIO_FROM_NUMBER,
    body,
  });
};

const dispatchNotification = async (notification) => {
  if (notification.channel === 'email') {
    await sendEmail({
      to: notification.payload.email,
      subject: notification.payload.subject,
      html: notification.payload.html,
      text: notification.payload.text,
    });
  } else if (notification.channel === 'sms') {
    await sendSms({
      to: notification.payload.phone,
      body: notification.payload.message,
    });
  } else {
    throw new Error(`Unsupported notification channel: ${notification.channel}`);
  }

  notification.status = 'sent';
  notification.attempts += 1;
  await notification.save();
};

const queueNotification = async (notificationId, queue) => {
  if (!queue) {
    await Notification.findByIdAndUpdate(notificationId, {
      status: 'pending',
      lastError: 'Notification queue unavailable',
    });
    return;
  }

  await Notification.findByIdAndUpdate(notificationId, { status: 'queued' });
  await queue.add('dispatch', { notificationId });
};

module.exports = {
  sendEmail,
  sendSms,
  dispatchNotification,
  queueNotification,
};
