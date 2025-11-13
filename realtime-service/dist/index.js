"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const http_1 = __importDefault(require("http"));
const express_1 = __importDefault(require("express"));
const cors_1 = __importDefault(require("cors"));
const socket_io_1 = require("socket.io");
const interviewRoutes_1 = __importDefault(require("./routes/interviewRoutes"));
const env_1 = require("./config/env");
const interviewNamespace_1 = require("./socket/interviewNamespace");
const logger_1 = require("./utils/logger");
const app = (0, express_1.default)();
app.use(express_1.default.json());
app.use((0, cors_1.default)({ origin: env_1.env.clientOrigins, credentials: true }));
app.get('/health', (_req, res) => res.json({ status: 'ok', service: 'realtime-service' }));
app.use('/api/interviews', interviewRoutes_1.default);
const server = http_1.default.createServer(app);
const io = new socket_io_1.Server(server, {
    cors: {
        origin: env_1.env.clientOrigins,
        credentials: true,
    },
});
(0, interviewNamespace_1.registerInterviewNamespace)(io);
const start = async () => {
    server.listen(env_1.env.port, () => {
        logger_1.logger.info(`Realtime service listening on port ${env_1.env.port}`);
    });
};
start().catch((error) => {
    logger_1.logger.error('Failed to start realtime service', { error });
    process.exit(1);
});
//# sourceMappingURL=index.js.map