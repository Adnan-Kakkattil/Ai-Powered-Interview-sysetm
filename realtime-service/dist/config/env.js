"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.env = void 0;
const dotenv_1 = __importDefault(require("dotenv"));
const path_1 = __importDefault(require("path"));
const envFile = process.env.NODE_ENV === 'production' ? '.env' : `.env.${process.env.NODE_ENV ?? 'development'}`;
dotenv_1.default.config({ path: path_1.default.resolve(process.cwd(), envFile) });
dotenv_1.default.config(); // fallback to default .env if specific file missing
exports.env = {
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
//# sourceMappingURL=env.js.map