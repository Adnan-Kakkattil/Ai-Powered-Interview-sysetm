## Smart Interview Management Backend

Node.js/Express backend for a smart interview platform that combines live interviews, real-time coding challenges, and behavioral analytics. The service manages candidates, interview scheduling, lot selection, notifications, and Socket.io signaling to power the video/coding experience.

---

### Highlights

- **Role-based access**: JWT-protected endpoints for admins, interviewers, and candidates.
- **Candidate pipeline**: Manage pools, track status, assign to lots, and schedule interviews.
- **Interview lifecycle**: Persistent records, coding task assignment, WebRTC room IDs, and session logs.
- **Real-time integrations**: Socket.io namespace ready for interview signaling, code collaboration, chat, and eye-tracking telemetry.
- **Notifications**: SendGrid email and Twilio SMS delivery with Bull queue for retries and tracking.
- **Observability**: Structured error handling, health check endpoint, and session logging for audit.
- **Developer tooling**: ESLint (flat config) and Prettier for code consistency, nodemon for hot reload.

---

### Core Modules

- `auth`: Registration/login for admins, JWT issuance, password hashing with bcrypt.
- `admin`: Candidate CRUD, lot management, interview scheduling, notification queuing.
- `candidate`: Profile maintenance, interview list retrieval, status updates.
- `interview`: Start/end session hooks, feedback capture, session log retrieval.
- `notification`: SendGrid/Twilio dispatch via Bull queue (`notification-queue`).
- `sockets`: `/interview` namespace for WebRTC signaling, collaborative coding, and telemetry.
- `models`: MongoDB schemas for users, candidate profiles, lots, coding tasks, interviews, notifications, session logs, and eye-tracking events.

---

### Project Structure

```
src/
  app.js                  Express app setup
  index.js                Server bootstrap + Socket.io + job loading
  config/db.js            MongoDB connection helper
  controllers/            Route handlers (auth/admin/candidate/interview)
  jobs/                   Bull queue definitions (notification)
  middlewares/            Auth + error handling middleware
  models/                 Mongoose schemas
  routes/                 Express route definitions
  services/               External integrations (notifications)
  sockets/                Socket.io namespaces
  utils/                  JWT + password helpers
```

---

### Requirements

- Node.js 18+
- npm 9+
- MongoDB Atlas (connection string provided)
- Redis (local or hosted) for Bull queue processing
- Optional: SendGrid/Twilio credentials for live notifications

---

### Environment Configuration

Copy `env.example` to `.env` and update as needed:

```
NODE_ENV=development
PORT=5000

MONGO_URI=your-mongodb-uri
MONGO_DB_NAME=smart_interview

JWT_SECRET=your_jwt_secret
JWT_EXPIRES_IN=1d
BCRYPT_SALT_ROUNDS=10

CLIENT_ORIGIN=http://localhost:3000

SENDGRID_API_KEY=...
EMAIL_FROM=...
TWILIO_ACCOUNT_SID=...
TWILIO_AUTH_TOKEN=...
TWILIO_FROM_NUMBER=...

REDIS_URL=redis://127.0.0.1:6379
```

> For local testing without SendGrid/Twilio, leave credentials blank; notification jobs will fail gracefully.

---

### Installation

```bash
npm install
```

Install Redis locally or point `REDIS_URL` to a managed instance (e.g., Upstash, Redis Cloud).

---

### Scripts

| Script          | Description                                   |
|-----------------|-----------------------------------------------|
| `npm run dev`   | Start development server with nodemon         |
| `npm start`     | Start server in production mode               |
| `npm run lint`  | Run ESLint across `src/**/*.js`               |
| `npm run format`| Format code with Prettier                     |

---

### Running Locally

1. Ensure `.env` is configured.
2. Start Redis (if running locally):
   ```bash
   redis-server
   ```
3. Launch the backend:
   ```bash
   npm run dev
   ```
4. Verify health:
   ```
   GET http://localhost:5000/health
   → {"status":"ok"}
   ```
5. Bull queue listens automatically when the server boots (`notification-queue`). Monitor Redis for job entries if notifications are queued.

---

### API Overview

All routes under `/api`.

**Auth**
- `POST /api/auth/register-admin` — bootstrap an admin account.
- `POST /api/auth/login` — email/password login, returns JWT.

**Admin**
- `POST /api/admin/candidates` — create candidate + profile.
- `GET /api/admin/candidates` — list candidates with profiles.
- `POST /api/admin/lots` — create lot.
- `PATCH /api/admin/lots/:lotId` — update lot metadata.
- `POST /api/admin/lots/:lotId/candidates` — add candidates to lot.
- `POST /api/admin/interviews` — schedule interview (queues notification).
- `GET /api/admin/interviews` — view scheduled/completed interviews.

**Candidate**
- `GET /api/candidates/me` — view profile.
- `PUT /api/candidates/me` — update profile/resume/skills.
- `GET /api/candidates/interviews` — list scheduled interviews.

**Interview**
- `POST /api/interviews/:interviewId/start` — mark session live.
- `POST /api/interviews/:interviewId/end` — submit feedback/score.
- `GET /api/interviews/:interviewId/logs` — retrieve session logs.

> Authenticated routes require `Authorization: Bearer <token>`. Candidate/admin routes enforce role-based access via middleware.

---

### Socket.io Namespace (`/interview`)

Events:
- `joinRoom` — join interview room; notifies others with `userJoined`.
- `signal` — WebRTC SDP/ICE exchange.
- `codeUpdate` — sync coding editor changes.
- `eyeTracking` — broadcast telemetry metrics.
- `chatMessage` — simple text chat.
- `leaveRoom` — exit room; emits `userLeft`.

---

### Notification Queue

- Queue name: `notification-queue`
- Processor: `src/jobs/notification.queue.js`
- Supports email (SendGrid) and SMS (Twilio) dispatch.
- Retries failed jobs up to 3 times with exponential backoff.

To observe jobs:
```bash
redis-cli LRANGE bull:notification-queue:wait 0 -1
```

---

### Roadmap

- Integrate WebRTC signaling with real media servers (e.g., LiveKit).
- Implement collaborative coding editor persistence & replay.
- Add metrics dashboards for admins (live candidate engagement).
- Expand automated tests (unit + integration) and CI pipeline.
- Harden security (rate limiting, input sanitization, audit logging).

---

### License

ISC License. See `package.json` for details.
