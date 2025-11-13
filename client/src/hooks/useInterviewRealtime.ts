import { useEffect, useMemo, useState } from 'react';
import { Socket } from 'socket.io-client';
import { createInterviewSocket } from '../lib/socket';

type Role = 'candidate' | 'interviewer';

type Participant = {
  socketId: string;
  identity: string;
  role: Role;
};

type UseInterviewRealtimeOptions = {
  interviewId: string;
  identity: string;
  role: Role;
  enabled?: boolean;
};

export const useInterviewRealtime = ({ interviewId, identity, role, enabled = true }: UseInterviewRealtimeOptions) => {
  const [socket, setSocket] = useState<Socket | null>(null);
  const [participants, setParticipants] = useState<Participant[]>([]);
  const [callReady, setCallReady] = useState(false);

  useEffect(() => {
    if (!enabled) return undefined;

    const client = createInterviewSocket({ interviewId, identity, role });
    setSocket(client);

    client.on('presence:update', (payload: Participant[]) => {
      setParticipants(payload);
    });

    client.on('call:ready', () => {
      setCallReady(true);
    });

    return () => {
      client.emit('leaveRoom', { interviewId });
      client.disconnect();
      setSocket(null);
    };
  }, [enabled, identity, interviewId, role]);

  return useMemo(
    () => ({
      socket,
      participants,
      callReady,
    }),
    [socket, participants, callReady],
  );
};

