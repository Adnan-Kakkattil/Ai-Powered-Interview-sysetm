# SmartHire Realtime Service Architecture

## Overview

The `realtime-service/` package hosts all signalling, room and token orchestration for SmartHire interview sessions. It exposes:

- **REST APIs** for issuing LiveKit tokens and coordinating invites.
- **Socket.IO namespaces** for realtime events (presence, WebRTC signalling, chat, collaborative tools).
- Integration hooks for LiveKit (or another SFU provider) so clients can connect to audio/video rooms.

## Key Components

| Path | Responsibility |
|------|----------------|
| `src/index.ts` | Express entrypoint, HTTP server + Socket.IO bootstrapping. |
| `src/config/env.ts` | Environment variable loading with support for multiple `.env` files. |
| `src/config/livekit.ts` | Helper for issuing LiveKit access tokens. Replace with your video provider if needed. |
| `src/services/roomService.ts` | In-memory room registry tracking participants and presence. Swap for Redis or DB-based persistence in production. |
| `src/services/interviewService.ts` | Issues LiveKit tokens and binds interview metadata. |
| `src/socket/interviewNamespace.ts` | Socket.IO namespace for `/interview` events (join/leave/offer/answer/ICE, presence updates). |
| `tests/interviewRoutes.test.ts` | Vitest integration tests using Supertest. |

## Environment Variables

Create `realtime-service/.env.development` (or `.env`) with:

```
PORT=5050
CLIENT_ORIGINS=http://localhost:3000,http://localhost:5173
LIVEKIT_API_KEY=lk_api_key
LIVEKIT_API_SECRET=lk_api_secret
LIVEKIT_WS_URL=wss://your-livekit-host
REDIS_URL=redis://localhost:6379
LOG_LEVEL=debug
```

## Starting the Service

```bash
cd realtime-service
npm install
npm run dev
```

The health check is available at `http://localhost:5050/health`.

## Socket Events

- `joinRoom` – payload `{ interviewId, role, identity }`. Adds participant, emits `presence:update`, and when both candidate + interviewer are present emits `call:ready`.
- `offer` / `answer` / `iceCandidate` – forwarded to the other peers within the same interview.
- `leaveRoom` – removes participant, updates presence.
- Future events (`chat:message`, `editor:update`, `screen:share`) can be plugged into `registerInterviewNamespace`.

## Next Steps

1. **Persist room state** – swap in Redis/DB for `roomService` to support horizontal scaling and reliability after restarts.
2. **Auth middleware** – validate JWT/Cookies before allowing `joinRoom` and REST requests.`
3. **LiveKit integration** – deploy LiveKit server (or another SFU) and update `.env` with real credentials.
4. **Client SDK** – migrate candidate/interviewer UIs to consume the new `/api/interviews/join-token` endpoint and Socket.IO events, then connect to LiveKit using the returned token.
5. **Extend tests** – add Vitest suites covering socket interactions (Vitest + `socket.io-client`) and failure cases.
6. **Deployment** – configure HTTPS, TURN servers, and CI/CD to build (`npm run build`) and launch the service (`npm start`).

