const { Server } = require('socket.io');

const registerInterviewNamespace = require('./interview.namespace');

const initSocketServer = (server) => {
  const io = new Server(server, {
    cors: {
      origin: process.env.CLIENT_ORIGIN || '*',
      credentials: true,
    },
  });

  registerInterviewNamespace(io);

  io.on('connection', (socket) => {
    console.log(`Socket connected: ${socket.id}`);

    socket.on('disconnect', (reason) => {
      console.log(`Socket disconnected: ${socket.id} (${reason})`);
    });
  });

  return io;
};

module.exports = initSocketServer;
