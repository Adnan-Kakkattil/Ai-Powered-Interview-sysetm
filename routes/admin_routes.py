from datetime import datetime

from flask import flash, redirect, render_template, url_for
from flask_login import current_user, login_required

from forms import CreateInterviewForm, InviteCandidateForm
from models import Interview, InterviewAssignment, User, db
from routes import admin_bp
from services import interview_service


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

    if form.validate_on_submit():
        interview = Interview.query.get(form.interview_id.data)
        selected_candidates = User.query.filter(User.id.in_(form.candidate_ids.data)).all()
        interview_service.assign_interview(interview, selected_candidates)
        flash("Candidates assigned successfully.", "success")
        return redirect(url_for("admin.manage_assignments"))

    return render_template(
        "admin/assignments.html",
        form=form,
        interviews=interviews,
        candidates=candidates,
    )


