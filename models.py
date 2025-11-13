from __future__ import annotations

from datetime import datetime

from flask_login import UserMixin
from flask_sqlalchemy import SQLAlchemy

db = SQLAlchemy()


class User(UserMixin, db.Model):
    __tablename__ = "users"

    id = db.Column(db.BigInteger, primary_key=True)
    email = db.Column(db.String(255), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    first_name = db.Column(db.String(100), nullable=False)
    last_name = db.Column(db.String(100), nullable=False)
    role = db.Column(
        db.Enum("superadmin", "admin", "candidate", name="user_role"),
        nullable=False,
        default="candidate",
    )
    is_active = db.Column(db.Boolean, default=True, nullable=False)
    last_login_at = db.Column(db.DateTime, nullable=True)
    created_at = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)
    updated_at = db.Column(
        db.DateTime,
        nullable=False,
        default=datetime.utcnow,
        onupdate=datetime.utcnow,
    )

    candidate_profile = db.relationship(
        "CandidateProfile",
        uselist=False,
        back_populates="user",
        cascade="all, delete-orphan",
    )

    def get_full_name(self) -> str:
        return f"{self.first_name} {self.last_name}"

    @property
    def is_admin(self) -> bool:
        return self.role in {"admin", "superadmin"}

    @property
    def is_candidate(self) -> bool:
        return self.role == "candidate"


class CandidateProfile(db.Model):
    __tablename__ = "candidates"

    user_id = db.Column(db.BigInteger, db.ForeignKey("users.id"), primary_key=True)
    resume_url = db.Column(db.String(500), nullable=True)
    notes = db.Column(db.Text, nullable=True)

    user = db.relationship("User", back_populates="candidate_profile")

    @classmethod
    def create_for_user(cls, *, user: User, resume_url: str | None = None, notes: str | None = None) -> "CandidateProfile":
        profile = cls(user=user, resume_url=resume_url, notes=notes)
        db.session.add(profile)
        db.session.commit()
        return profile


class Interview(db.Model):
    __tablename__ = "interviews"

    id = db.Column(db.BigInteger, primary_key=True)
    title = db.Column(db.String(255), nullable=False)
    description = db.Column(db.Text, nullable=True)
    scheduled_at = db.Column(db.DateTime, nullable=True)
    duration_minutes = db.Column(db.Integer, nullable=False, default=60)
    status = db.Column(
        db.Enum(
            "draft",
            "scheduled",
            "in_progress",
            "completed",
            "cancelled",
            name="interview_status",
        ),
        default="draft",
        nullable=False,
    )
    created_by = db.Column(db.BigInteger, db.ForeignKey("users.id"), nullable=False)
    created_at = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)
    updated_at = db.Column(
        db.DateTime, nullable=False, default=datetime.utcnow, onupdate=datetime.utcnow
    )

    creator = db.relationship("User", backref="created_interviews", foreign_keys=[created_by])
    assignments = db.relationship(
        "InterviewAssignment",
        back_populates="interview",
        cascade="all, delete-orphan",
    )


class InterviewAssignment(db.Model):
    __tablename__ = "interview_assignments"

    id = db.Column(db.BigInteger, primary_key=True)
    interview_id = db.Column(db.BigInteger, db.ForeignKey("interviews.id"), nullable=False)
    candidate_id = db.Column(db.BigInteger, db.ForeignKey("users.id"), nullable=False)
    status = db.Column(
        db.Enum(
            "invited",
            "accepted",
            "in_progress",
            "completed",
            "no_show",
            name="assignment_status",
        ),
        default="invited",
        nullable=False,
    )
    invited_at = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)
    started_at = db.Column(db.DateTime, nullable=True)
    completed_at = db.Column(db.DateTime, nullable=True)
    score = db.Column(db.Numeric(5, 2), nullable=True)
    feedback = db.Column(db.Text, nullable=True)

    interview = db.relationship("Interview", back_populates="assignments")
    candidate = db.relationship("User", foreign_keys=[candidate_id], backref="assignments")
    activity_logs = db.relationship(
        "InterviewActivityLog",
        back_populates="assignment",
        cascade="all, delete-orphan",
    )
    code_submissions = db.relationship(
        "CodeSubmission",
        back_populates="assignment",
        cascade="all, delete-orphan",
    )
    session_snapshots = db.relationship(
        "SessionSnapshot",
        back_populates="assignment",
        cascade="all, delete-orphan",
    )


class InterviewActivityLog(db.Model):
    __tablename__ = "interview_activity_logs"

    id = db.Column(db.BigInteger, primary_key=True)
    assignment_id = db.Column(
        db.BigInteger,
        db.ForeignKey("interview_assignments.id"),
        nullable=False,
    )
    event_type = db.Column(db.String(100), nullable=False)
    event_metadata = db.Column(db.JSON, nullable=True)
    recorded_at = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)

    assignment = db.relationship("InterviewAssignment", back_populates="activity_logs")


class CodeSubmission(db.Model):
    __tablename__ = "code_submissions"

    id = db.Column(db.BigInteger, primary_key=True)
    assignment_id = db.Column(
        db.BigInteger,
        db.ForeignKey("interview_assignments.id"),
        nullable=False,
    )
    language = db.Column(db.String(50), nullable=False, default="python")
    code = db.Column(db.Text, nullable=False)
    submitted_at = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)

    assignment = db.relationship("InterviewAssignment", back_populates="code_submissions")


class SessionSnapshot(db.Model):
    __tablename__ = "session_snapshots"

    id = db.Column(db.BigInteger, primary_key=True)
    assignment_id = db.Column(
        db.BigInteger,
        db.ForeignKey("interview_assignments.id"),
        nullable=False,
    )
    image_path = db.Column(db.String(500), nullable=True)
    analysis_result = db.Column(db.JSON, nullable=True)
    captured_at = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)

    assignment = db.relationship("InterviewAssignment", back_populates="session_snapshots")


