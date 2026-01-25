# AI-Powered Interview System (NeuroHire)

A Flask + MySQL + Socket.IO web app for conducting **live, proctored technical interviews** with:

- **Scheduling & user management** (admin/interviewer vs candidate)
- **WebRTC video calling + screen share**
- **Collaborative code editor** (real-time sync)
- **Chat** (persisted to MySQL)
- **Proctoring/anti-cheat** via browser-based face/eye detection (face-api.js) + copy/paste blocking
- **Resume upload + in-room resume viewer** (PDF preview in modal)

---

## Table of contents

- [Tech stack](#tech-stack)
- [Repository structure](#repository-structure)
- [Interview system features](#interview-system-features)
- [Database schema](#database-schema)
- [Configuration](#configuration)
- [Run with Docker (recommended)](#run-with-docker-recommended)
- [Run without Docker (Windows / XAMPP)](#run-without-docker-windows--xampp)
- [Usage walkthrough](#usage-walkthrough)
- [Key HTTP routes](#key-http-routes)
- [Socket.IO events](#socketio-events)
- [Resume upload & preview details](#resume-upload--preview-details)
- [Troubleshooting](#troubleshooting)

---

## Tech stack

- **Backend**
  - Python (tested with the repo’s `Dockerfile`: Python 3.9)
  - Flask
  - Flask-MySQLdb (MySQL connector)
  - Flask-SocketIO + eventlet (realtime)
- **Database**
  - MySQL 8 (Docker uses `mysql:8.0`)
- **Frontend**
  - Jinja2 templates (server-rendered HTML)
  - Tailwind CSS (CDN)
  - Vanilla JavaScript
  - CodeMirror 5 (editor, CDN)
- **Realtime**
  - Socket.IO (WebRTC signaling, chat, code sync, join approval, alerts)
- **Video**
  - WebRTC peer-to-peer with STUN: `stun:stun.l.google.com:19302`
- **Proctoring**
  - face-api.js (CDN: `@vladmandic/face-api`) + local JS logic

---

## Repository structure

```
app.py                  # Flask app + Socket.IO events
config.py               # Env-based configuration
extensions.py           # mysql + socketio singletons
routes/
  auth.py               # login/logout/setup-admin
  main.py               # dashboard/add-candidate/schedule/interview/resume endpoints
templates/              # Jinja templates (UI)
static/js/              # webrtc/editor/chat/face detection
schema.sql              # Database schema
init_db.py              # Initialize DB from schema.sql
update_db.py            # Apply schema updates (adds missing columns/tables)
update_db_join_status.py# Adds join-status column (older DB support)
uploads/resumes/        # Stored candidate resumes (gitignored recommended)
```

---

## Interview system features

### Roles
- **Admin / Interviewer**
  - Create candidates
  - Schedule interviews (generates a unique meeting link)
  - Join interview room and approve/deny candidate join requests
  - Mark interview as completed
  - View candidate resume inside the interview room

- **Candidate**
  - Login
  - View scheduled interviews
  - Request to join interview room (waiting-room flow)
  - Participate in interview (video + code + chat)
  - View their own resume inside the interview room

### Scheduling & access control
- **Session-based auth** (Flask sessions)
- **Magic meeting link** per interview: `interviews.meeting_link` (UUID)
- **Authorization checks**: only the scheduled interviewer/candidate can join the room

### Live interview room
- **Video call**
  - Camera + microphone
  - Screen sharing toggle
- **Collaborative code editor**
  - CodeMirror editor
  - Realtime sync via Socket.IO
  - Last code is persisted in DB (`interviews.code_content`)
  - Basic anti-cheat: copy/paste disabled in editor
- **Chat**
  - Realtime messages
  - Persisted in `chat_messages`
  - Chat history loaded when opening the interview room
- **Candidate waiting-room / approval**
  - Candidate requests to join
  - Interviewer admits/denies
  - Status persisted in `interviews.candidate_join_status`
- **Resume in interview room**
  - Resume uploaded on candidate creation
  - “View Resume” opens a modal in the interview room (PDF preview supported)

### Proctoring / cheat detection
- **No face detected**
- **Multiple faces detected**
- **Looking away** (eye landmark heuristic)
- **Face too far** (bounding-box size heuristic)
- Candidate-side detections trigger **`cheat-detected`** → interviewer receives **`cheat-alert`**

---

## Database schema

The schema is defined in `schema.sql`. Core tables:

- **`users`**
  - `role`: `admin` or `candidate`
  - Candidate profile fields (optional): `phone`, `target_role`, `experience_level`
  - Resume fields (optional): `resume_path`, `resume_original_name`

- **`interviews`**
  - `meeting_link`: unique UUID used as the room id
  - `code_content`: last saved editor content
  - `candidate_join_status`: `pending/requested/approved/rejected`

- **`chat_messages`**
  - Linked to an interview via `interview_id`

---

## Configuration

Environment variables are loaded via `python-dotenv` (`.env`) and read in `config.py`.

### Required/commonly used
- **`SECRET_KEY`**: Flask session secret
- **`MYSQL_HOST`**
- **`MYSQL_USER`**
- **`MYSQL_PASSWORD`**
- **`MYSQL_DB`** (default: `interview_system`)

### Upload limit
- **`MAX_CONTENT_LENGTH`**: max request size in bytes (default: 10MB)

---

## Run with Docker (recommended)

### 1) Start containers

```bash
docker compose up --build
```

### 2) Open the app
- App: `http://127.0.0.1:5000`

The MySQL container applies `schema.sql` automatically on first start.

---

## Run locally (XAMPP / local MySQL)

### 1) Create & activate a virtualenv (optional but recommended)

```bash
python -m venv .venv
.\.venv\Scripts\activate
```

### 2) Install dependencies

```bash
pip install -r requirements.txt
```

### 3) Configure `.env`

Set `MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DB`, `SECRET_KEY`.

### 4) Initialize the DB

```bash
python init_db.py
```

If you already created the DB earlier and need new columns/tables:

```bash
python update_db.py
python update_db_join_status.py
```

### 5) Run the server

```bash
python app.py
```

The server is configured to bind to **`0.0.0.0:5000`** (LAN/Docker friendly).

---

## Run without Docker (Windows / XAMPP)

This is the full “no Docker” setup for Windows.

### Prerequisites
- **Python 3.9+** installed and available in PATH (`python --version`)
- **MySQL running**
  - If using **XAMPP**: start **Apache** (optional) and **MySQL** from the XAMPP control panel
- **Pip** available (`pip --version`)

### 1) Create & activate a virtual environment

```powershell
cd c:\xampp1\htdocs\Ai-Powered-Interview-sysetm
python -m venv .venv
.\.venv\Scripts\Activate.ps1
```

### 2) Install dependencies

```powershell
pip install -r requirements.txt
```

If `flask-mysqldb` fails to install on Windows, it usually means MySQL client build deps are missing.
Common fixes:
- Install **Microsoft C++ Build Tools**
- Install **MySQL / MariaDB C connector** (so `mysqlclient` can build)

### 3) Configure `.env`

Create/update `.env` in the project root. Example:

```env
SECRET_KEY=dev
MYSQL_HOST=127.0.0.1
MYSQL_USER=root
MYSQL_PASSWORD=
MYSQL_DB=interview_system
MAX_CONTENT_LENGTH=10485760
```

### 4) Initialize the database schema

```powershell
python init_db.py
```

If you already had a DB and need to add the newer columns/tables:

```powershell
python update_db.py
python update_db_join_status.py
```

### 5) Run the server

```powershell
python app.py
```

Open:
- `http://127.0.0.1:5000`

> Note: the server binds to `0.0.0.0`, so you can also access it from another device on your LAN using your PC’s IP (e.g. `http://192.168.x.x:5000`).

---

## Usage walkthrough

### 1) Create the first admin
- Visit: `/setup-admin`
- Create an admin account

### 2) Login
- Visit: `/login`

### 3) Add a candidate (with resume)
- Visit: `/add-candidate`
- Upload a PDF/DOC/DOCX resume (optional)
- Submit the form

Resumes are stored on disk under `uploads/resumes/` and the path is stored in MySQL.

### 4) Schedule an interview
- Visit: `/schedule-interview`
- Pick the candidate and set date/time
- A `meeting_link` UUID is generated and stored in `interviews.meeting_link`

### 5) Join the interview room
- From dashboard, click **Join**
- URL format:
  - `/interview/<meeting_link>`

### 6) Candidate join approval (waiting room)
- Candidate clicks “Ask to Join”
- Interviewer receives a popup to **Admit** or **Deny**

### 7) View resume in the interview room
- Click **View Resume**
  - PDF: previews in a modal iframe
  - DOC/DOCX: offers an “Open resume” link (browser download/open behavior)

---

## Key HTTP routes

### Auth
- `GET/POST /login`
- `GET/POST /setup-admin`
- `GET /logout`

### App
- `GET /dashboard`
- `GET/POST /add-candidate`
- `GET/POST /schedule-interview`
- `GET /interview/<meeting_link>`
- `POST /interview/<meeting_link>/complete`
- `GET /interview/<meeting_link>/resume` (authorized users only)

---

## Socket.IO events

### Room membership / join approval
- **Client → Server**
  - `join` `{ room, username }`
  - `leave` `{ room, username }`
  - `request-join` `{ room, username }` (candidate)
  - `approve-join` `{ room }` (interviewer)
  - `reject-join` `{ room }` (interviewer)

- **Server → Client**
  - `joined`
  - `user-joined`, `user-left`
  - `join-request`
  - `join-approved`
  - `join-rejected`
  - `candidate-approved`

### WebRTC signaling
- `offer`, `answer`, `ice-candidate`

### Collaborative editor
- `code-change` `{ room, code }` (also persists to `interviews.code_content`)
- `code-update` `{ code }`

### Chat
- `chat-message` `{ room, message, username, timestamp }` (also persists to `chat_messages`)

### Proctoring alerts
- `cheat-detected` `{ room, type, message, timestamp }`
- `cheat-alert` `{ type, message, timestamp }` (server broadcasts to interviewer)

---

## Resume upload & preview details

- Upload accepted types: **PDF, DOC, DOCX**
- Max size: **10MB**
- Stored on disk: `uploads/resumes/<uuid>_<originalname>`
- DB fields (in `users`):
  - `resume_path`
  - `resume_original_name`

Resume serving endpoint is scoped to the interview:
- `GET /interview/<meeting_link>/resume`
  - Only the interview’s interviewer/candidate may access it

---

## Troubleshooting

### MySQL connection issues
- Confirm `.env` matches your MySQL settings
- Ensure the DB exists and schema has been applied (`python init_db.py`)

### Resume button not visible in interview room
- The candidate must have a stored resume (`users.resume_path` not NULL)
- Re-upload the resume via **Add Candidate** if needed

### PDF preview is blank
- Check the resume endpoint opens directly in browser:
  - `/interview/<meeting_link>/resume`
- Some browsers/extensions block iframes; try “Open resume” link.

### WebRTC video not connecting
- Both peers must allow camera/mic permissions
- If behind strict NAT/firewall, you may need a TURN server (STUN-only is not always enough)

---

## Security notes (recommended improvements)

- Store uploads outside the repo and add `uploads/` to `.gitignore`.
- Consider virus scanning for uploaded files (production).
- Add stricter MIME validation (not only file extension).
- Add CSRF protection for forms (Flask-WTF).
- Use HTTPS in production (required for many WebRTC features).

---

## License

Add a license if you plan to publish/distribute this project.

