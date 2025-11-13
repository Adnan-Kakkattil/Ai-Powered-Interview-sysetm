import { io, Socket } from 'socket.io-client';
import { config } from '../config';

type JoinPayload = {
  interviewId: string;
  identity: string;
  role: 'candidate' | 'interviewer';
};

export const createInterviewSocket = (options: JoinPayload): Socket => {
  const socket = io(config.realtimeApiBase + config.socketNamespace, {
    transports: ['websocket'],
    withCredentials: true,
  });

  socket.on('connect', () => {
    socket.emit('joinRoom', options);
  });

  return socket;
};

