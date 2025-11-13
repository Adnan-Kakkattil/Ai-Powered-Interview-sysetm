const mongoose = require('mongoose');

const MONGO_URI = process.env.MONGO_URI;

if (!MONGO_URI) {
  console.warn('Warning: MONGO_URI is not set. Database connection will fail.');
}

const connectDB = async () => {
  try {
    mongoose.set('strictQuery', true);
    await mongoose.connect(MONGO_URI, {
      dbName: process.env.MONGO_DB_NAME || 'smart-interview',
    });
    console.log('Connected to MongoDB');
  } catch (error) {
    console.error('MongoDB connection error:', error);
    throw error;
  }
};

module.exports = connectDB;
