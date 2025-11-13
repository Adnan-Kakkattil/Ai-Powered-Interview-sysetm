require('dotenv').config();

const http = require('http');
const app = require('./app');
const connectDB = require('./config/db');
const initSocketServer = require('./sockets');
require('./jobs');

const PORT = process.env.PORT || 5000;

async function startServer() {
  try {
    await connectDB();

    const server = http.createServer(app);

    initSocketServer(server);

    server.listen(PORT, () => {
      console.log(`Server listening on port ${PORT}`);
    });
  } catch (error) {
    console.error('Failed to start server', error);
    process.exit(1);
  }
}

startServer();
