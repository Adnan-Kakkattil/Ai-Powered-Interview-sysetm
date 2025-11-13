export const config = {
  realtimeApiBase: import.meta.env.VITE_REALTIME_API_BASE ?? 'http://localhost:5050',
  restApiBase: import.meta.env.VITE_REST_API_BASE ?? 'http://localhost:5000',
  socketNamespace: import.meta.env.VITE_SOCKET_NAMESPACE ?? '/interview',
};

