import http from 'http';
import express from 'express';
import cors from 'cors';
import { Server as SocketIOServer } from 'socket.io';
import interviewRoutes from './routes/interviewRoutes';
import { env } from './config/env';
import { registerInterviewNamespace } from './socket/interviewNamespace';
import { logger } from './utils/logger';

const app = express();

app.use(express.json());
app.use(cors({ origin: env.clientOrigins, credentials: true }));
app.get('/health', (_req, res) => res.json({ status: 'ok', service: 'realtime-service' }));
app.use('/api/interviews', interviewRoutes);

const server = http.createServer(app);
const io = new SocketIOServer(server, {
  cors: {
    origin: env.clientOrigins,
    credentials: true,
  },
});

registerInterviewNamespace(io);

const start = async () => {
  server.listen(env.port, () => {
    logger.info(`Realtime service listening on port ${env.port}`);
  });
};

start().catch((error) => {
  logger.error('Failed to start realtime service', { error });
  process.exit(1);
});

