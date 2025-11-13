from flask import jsonify, render_template
from flask_login import current_user, login_required

from models import InterviewAssignment
from routes import candidate_bp


@candidate_bp.before_request
@login_required
def require_candidate():
    if not current_user.is_candidate:
        return render_template("errors/403.html"), 403


@candidate_bp.route("/dashboard")
def dashboard():
    assignments = (
        InterviewAssignment.query.filter_by(candidate_id=current_user.id)
        .order_by(InterviewAssignment.invited_at.desc())
        .all()
    )
    return render_template("candidate/dashboard.html", assignments=assignments)


@candidate_bp.route("/interview/<int:assignment_id>")
def interview_session(assignment_id: int):
    assignment = InterviewAssignment.query.filter_by(
        id=assignment_id, candidate_id=current_user.id
    ).first_or_404()
    return render_template("candidate/interview_session.html", assignment=assignment)


@candidate_bp.route("/interview/<int:assignment_id>/heartbeat", methods=["POST"])
def heartbeat(assignment_id: int):
    # Placeholder for tracking candidate activity from the browser.
    return jsonify({"status": "ok"})


