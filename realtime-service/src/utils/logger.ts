type LogLevel = 'info' | 'warn' | 'error' | 'debug';

const shouldLogDebug = process.env.LOG_LEVEL === 'debug';

const log = (level: LogLevel, message: string, meta?: Record<string, unknown>) => {
  if (level === 'debug' && !shouldLogDebug) {
    return;
  }
  const payload = meta ? `${message} ${JSON.stringify(meta)}` : message;
  const timestamp = new Date().toISOString();
  // eslint-disable-next-line no-console
  console[level](`[${timestamp}] [${level.toUpperCase()}] ${payload}`);
};

export const logger = {
  info: (message: string, meta?: Record<string, unknown>) => log('info', message, meta),
  warn: (message: string, meta?: Record<string, unknown>) => log('warn', message, meta),
  error: (message: string, meta?: Record<string, unknown>) => log('error', message, meta),
  debug: (message: string, meta?: Record<string, unknown>) => log('debug', message, meta),
};

