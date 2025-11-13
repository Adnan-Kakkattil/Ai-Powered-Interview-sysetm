from datetime import datetime

from flask import flash, redirect, render_template, url_for
from flask_login import current_user, login_required

from forms import CandidateCreationForm, CreateInterviewForm, InviteCandidateForm
from models import CandidateProfile, Interview, InterviewAssignment, User, db
from sqlalchemy.exc import IntegrityError
from routes import admin_bp
from services import auth_service, interview_service


@admin_bp.before_request
@login_required
def require_admin():
    if not current_user.is_admin:
        flash("Administrator access required.", "warning")
        return redirect(url_for("auth.login"))


@admin_bp.route("/dashboard")
def dashboard():
    interviews = Interview.query.order_by(Interview.created_at.desc()).limit(20).all()

    stats = {
        "ongoing": Interview.query.filter_by(status="in_progress").count(),
        "upcoming": Interview.query.filter_by(status="scheduled").count(),
        "completed": Interview.query.filter_by(status="completed").count(),
        "total_candidates": User.query.filter_by(role="candidate").count(),
    }

    recent_assignments = (
        InterviewAssignment.query.order_by(InterviewAssignment.invited_at.desc())
        .limit(10)
        .all()
    )

    return render_template(
        "admin/dashboard.html",
        interviews=interviews,
        stats=stats,
        recent_assignments=recent_assignments,
    )


@admin_bp.route("/interviews/new", methods=["GET", "POST"])
def create_interview():
    form = CreateInterviewForm()
    if form.validate_on_submit():
        scheduled_at = (
            datetime.combine(form.scheduled_date.data, form.scheduled_time.data)
            if form.scheduled_date.data and form.scheduled_time.data
            else None
        )
        interview_service.create_interview(
            title=form.title.data,
            description=form.description.data,
            scheduled_at=scheduled_at,
            duration_minutes=form.duration_minutes.data,
            created_by=current_user,
        )
        flash("Interview created.", "success")
        return redirect(url_for("admin.dashboard"))
    return render_template("admin/create_interview.html", form=form)


@admin_bp.route("/assignments", methods=["GET", "POST"])
def manage_assignments():
    form = InviteCandidateForm()
    interviews = Interview.query.order_by(Interview.scheduled_at.desc()).all()
    candidates = User.query.filter_by(role="candidate").all()

    form.interview_id.choices = [(interview.id, interview.title) for interview in interviews]
    form.candidate_ids.choices = [
        (candidate.id, candidate.get_full_name()) for candidate in candidates
    ]

    assignments = (
        InterviewAssignment.query.with_entities(
            InterviewAssignment.interview_id, InterviewAssignment.candidate_id
        ).all()
    )
    assignments_map: dict[int, set[int]] = {}
    for interview_id, candidate_id in assignments:
        assignments_map.setdefault(interview_id, set()).add(candidate_id)

    if interviews and form.interview_id.data is None:
        form.interview_id.data = interviews[0].id

    selected_interview_id = form.interview_id.data

    if form.validate_on_submit():
        interview = Interview.query.get(form.interview_id.data)
        if not interview:
            flash("Selected interview could not be found.", "danger")
            return redirect(url_for("admin.manage_assignments"))

        selected_candidates = User.query.filter(User.id.in_(form.candidate_ids.data)).all()

        created, skipped = interview_service.assign_interview(interview, selected_candidates)

        if created:
            flash(
                f"Assigned {len(created)} candidate(s) to {interview.title}.",
                "success",
            )

        if skipped:
            skipped_names = ", ".join(candidate.get_full_name() for candidate in skipped[:5])
            more = len(skipped) - 5
            if more > 0:
                skipped_names += f" and {more} more"
            flash(
                f"Skipped {len(skipped)} already assigned candidate(s): {skipped_names}.",
                "warning",
            )

        if not created and not skipped:
            flash("No candidates were selected for assignment.", "info")

        return redirect(url_for("admin.manage_assignments"))

    return render_template(
        "admin/assignments.html",
        form=form,
        interviews=interviews,
        candidates=candidates,
        assignments_map={key: list(value) for key, value in assignments_map.items()},
        selected_interview_id=selected_interview_id,
    )


@admin_bp.route("/candidates/new", methods=["GET", "POST"])
def create_candidate():
    form = CandidateCreationForm()
    if form.validate_on_submit():
        try:
            user = auth_service.create_user(
                email=form.email.data,
                password=form.password.data,
                first_name=form.first_name.data,
                last_name=form.last_name.data,
                role="candidate",
            )
        except IntegrityError:
            db.session.rollback()
            flash("A user with that email already exists.", "danger")
        else:
            CandidateProfile.create_for_user(
                user=user,
                resume_url=form.resume_url.data or None,
            )
            flash(f"Candidate {user.get_full_name()} created.", "success")
            return redirect(url_for("admin.manage_assignments"))

    candidates = User.query.filter_by(role="candidate").order_by(User.created_at.desc()).limit(10).all()
    return render_template(
        "admin/create_candidate.html",
        form=form,
        recent_candidates=candidates,
    )


