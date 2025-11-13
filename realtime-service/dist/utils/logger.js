"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.logger = void 0;
const shouldLogDebug = process.env.LOG_LEVEL === 'debug';
const log = (level, message, meta) => {
    if (level === 'debug' && !shouldLogDebug) {
        return;
    }
    const payload = meta ? `${message} ${JSON.stringify(meta)}` : message;
    const timestamp = new Date().toISOString();
    // eslint-disable-next-line no-console
    console[level](`[${timestamp}] [${level.toUpperCase()}] ${payload}`);
};
exports.logger = {
    info: (message, meta) => log('info', message, meta),
    warn: (message, meta) => log('warn', message, meta),
    error: (message, meta) => log('error', message, meta),
    debug: (message, meta) => log('debug', message, meta),
};
//# sourceMappingURL=logger.js.map