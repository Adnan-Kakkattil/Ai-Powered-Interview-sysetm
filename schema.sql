-- SQLite schema for NeuroHire Interview System

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('admin', 'candidate')),
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    phone TEXT,
    target_role TEXT,
    experience_level TEXT,
    resume_path TEXT,
    resume_original_name TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS interviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    scheduled_time TEXT NOT NULL,
    interviewer_id INTEGER NOT NULL,
    candidate_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'scheduled' CHECK(status IN ('scheduled', 'completed', 'cancelled')),
    meeting_link TEXT UNIQUE NOT NULL,
    code_content TEXT,
    candidate_join_status TEXT NOT NULL DEFAULT 'pending' CHECK(candidate_join_status IN ('pending', 'requested', 'approved', 'rejected')),
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (interviewer_id) REFERENCES users(id),
    FOREIGN KEY (candidate_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    interview_id INTEGER NOT NULL,
    sender_username TEXT NOT NULL,
    message TEXT NOT NULL,
    timestamp TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (interview_id) REFERENCES interviews(id)
);
