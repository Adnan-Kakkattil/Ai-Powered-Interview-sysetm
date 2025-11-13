import { AccessToken } from 'livekit-server-sdk';
import { env } from './env';

type LivekitTokenOptions = {
  roomName: string;
  identity: string;
  metadata?: Record<string, unknown> | string;
  ttl?: number;
};

export const createJoinToken = ({
  roomName,
  identity,
  metadata,
  ttl = 3600,
}: LivekitTokenOptions) => {
  const at = new AccessToken(env.livekit.apiKey, env.livekit.apiSecret, {
    identity,
    metadata: typeof metadata === 'string' ? metadata : JSON.stringify(metadata ?? {}),
    ttl,
  });

  at.addGrant({
    room: roomName,
    roomJoin: true,
    canPublish: true,
    canSubscribe: true,
    canPublishData: true,
  });

  return at.toJwt();
};
