import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const realtimeBase = env.VITE_REALTIME_API_BASE ?? 'http://localhost:5050';

  return {
    plugins: [react()],
    server: {
      port: 5173,
      proxy: {
        '/api/interviews': {
          target: `${realtimeBase}/api/interviews`,
          changeOrigin: true,
          rewrite: (path) => path.replace(/^\/api\/interviews/, ''),
        },
      },
    },
    preview: {
      port: 4173,
    },
  };
});

