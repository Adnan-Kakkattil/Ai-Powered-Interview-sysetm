const mongoose = require('mongoose');

const { Schema } = mongoose;

const codingTaskSchema = new Schema(
  {
    title: {
      type: String,
      required: true,
    },
    description: String,
    starterCode: String,
    language: {
      type: String,
      default: 'javascript',
    },
    difficulty: {
      type: String,
      enum: ['easy', 'medium', 'hard'],
      default: 'medium',
    },
    tags: [String],
  },
  { timestamps: true },
);

const CodingTask = mongoose.model('CodingTask', codingTaskSchema);

module.exports = { CodingTask };
