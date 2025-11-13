import dotenv from 'dotenv';
import path from 'path';

const envFile = process.env.NODE_ENV === 'production' ? '.env' : `.env.${process.env.NODE_ENV ?? 'development'}`;

dotenv.config({ path: path.resolve(process.cwd(), envFile) });
dotenv.config(); // fallback to default .env if specific file missing

export const env = {
  nodeEnv: process.env.NODE_ENV ?? 'development',
  port: Number(process.env.PORT ?? 5050),
  clientOrigins: (process.env.CLIENT_ORIGINS ?? 'http://localhost:3000').split(','),
  livekit: {
    apiKey: process.env.LIVEKIT_API_KEY ?? 'sample-key',
    apiSecret: process.env.LIVEKIT_API_SECRET ?? 'sample-secret',
    wsUrl: process.env.LIVEKIT_WS_URL ?? 'wss://example.com',
  },
  redisUrl: process.env.REDIS_URL ?? 'redis://localhost:6379',
};

