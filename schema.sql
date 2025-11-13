-- MySQL schema for AI Powered Interview System
-- Run: mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS ai_interview_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ai_interview_system;

CREATE TABLE IF NOT EXISTS users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    role            ENUM('superadmin', 'admin', 'candidate') NOT NULL DEFAULT 'candidate',
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at   DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
);

CREATE TABLE IF NOT EXISTS candidates (
    user_id         BIGINT UNSIGNED PRIMARY KEY,
    resume_url      VARCHAR(500) NULL,
    notes           TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS interviews (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title               VARCHAR(255) NOT NULL,
    description         TEXT NULL,
    scheduled_at        DATETIME NULL,
    duration_minutes    INT NOT NULL DEFAULT 60,
    status              ENUM('draft', 'scheduled', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    created_by          BIGINT UNSIGNED NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_interviews_status (status),
    INDEX idx_interviews_scheduled_at (scheduled_at)
);

CREATE TABLE IF NOT EXISTS interview_assignments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    interview_id    BIGINT UNSIGNED NOT NULL,
    candidate_id    BIGINT UNSIGNED NOT NULL,
    status          ENUM('invited', 'accepted', 'in_progress', 'completed', 'no_show') NOT NULL DEFAULT 'invited',
    invited_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at      DATETIME NULL,
    completed_at    DATETIME NULL,
    score           DECIMAL(5,2) NULL,
    feedback        TEXT NULL,
    UNIQUE KEY uniq_assignment (interview_id, candidate_id),
    FOREIGN KEY (interview_id) REFERENCES interviews(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_assignments_status (status)
);

CREATE TABLE IF NOT EXISTS interview_activity_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id   BIGINT UNSIGNED NOT NULL,
    event_type      VARCHAR(100) NOT NULL,
    event_metadata  JSON NULL,
    recorded_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES interview_assignments(id) ON DELETE CASCADE,
    INDEX idx_activity_assignment (assignment_id),
    INDEX idx_activity_event_type (event_type)
);

CREATE TABLE IF NOT EXISTS code_submissions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id   BIGINT UNSIGNED NOT NULL,
    language        VARCHAR(50) NOT NULL DEFAULT 'python',
    code            MEDIUMTEXT NOT NULL,
    submitted_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES interview_assignments(id) ON DELETE CASCADE,
    INDEX idx_submissions_assignment (assignment_id)
);

CREATE TABLE IF NOT EXISTS session_snapshots (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id   BIGINT UNSIGNED NOT NULL,
    image_path      VARCHAR(500) NULL,
    analysis_result JSON NULL,
    captured_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES interview_assignments(id) ON DELETE CASCADE,
    INDEX idx_snapshots_assignment (assignment_id)
);


