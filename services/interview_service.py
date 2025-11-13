from __future__ import annotations

from datetime import datetime
from typing import Iterable, Optional

from flask import current_app

from models import Interview, InterviewAssignment, User, db


def create_interview(
    *,
    title: str,
    description: str,
    scheduled_at: Optional[datetime],
    duration_minutes: int,
    created_by: User,
) -> Interview:
    interview = Interview(
        title=title,
        description=description,
        scheduled_at=scheduled_at,
        duration_minutes=duration_minutes,
        created_by=created_by.id,
    )
    db.session.add(interview)
    db.session.commit()
    current_app.logger.info("Interview %s created by %s", interview.id, created_by.email)
    return interview


def assign_interview(interview: Interview, candidates: Iterable[User]) -> list[InterviewAssignment]:
    assignments: list[InterviewAssignment] = []
    for candidate in candidates:
        assignment = InterviewAssignment(
            interview_id=interview.id,
            candidate_id=candidate.id,
        )
        db.session.add(assignment)
        assignments.append(assignment)

    db.session.commit()
    current_app.logger.info(
        "Interview %s assigned to candidates: %s",
        interview.id,
        ", ".join(str(c.id) for c in candidates),
    )
    return assignments


def update_assignment_status(assignment: InterviewAssignment, status: str) -> InterviewAssignment:
    assignment.status = status
    if status == "in_progress":
        assignment.started_at = datetime.utcnow()
    elif status == "completed":
        assignment.completed_at = datetime.utcnow()
    db.session.commit()
    return assignment


