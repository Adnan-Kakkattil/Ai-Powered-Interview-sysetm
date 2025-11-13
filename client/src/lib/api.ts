import axios from 'axios';
import { config } from '../config';

type JoinTokenPayload = {
  interviewId: string;
  participantId: string;
  role: 'candidate' | 'interviewer';
  displayName: string;
};

type JoinTokenResponse = {
  token: string;
  roomName: string;
};

const client = axios.create({
  baseURL: `${config.realtimeApiBase}/api`,
  timeout: 10_000,
});

export const api = {
  async createJoinToken(payload: JoinTokenPayload): Promise<JoinTokenResponse> {
    const { data } = await client.post<JoinTokenResponse>('/interviews/join-token', payload);
    return data;
  },
};

