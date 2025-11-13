from flask import flash, redirect, render_template, request, url_for
from flask_login import current_user, login_required, login_user, logout_user

from forms import AdminRegistrationForm, LoginForm
from models import User, db
from routes import auth_bp
from services import auth_service


@auth_bp.route("/login", methods=["GET", "POST"])
def login():
    if current_user.is_authenticated:
        return redirect(url_for("admin.dashboard") if current_user.is_admin else url_for("candidate.dashboard"))

    form = LoginForm()
    if form.validate_on_submit():
        result = auth_service.authenticate(form.email.data, form.password.data)
        if result.success and result.user:
            login_user(result.user, remember=form.remember_me.data)
            flash("Logged in successfully.", "success")
            next_url = request.args.get("next")
            return redirect(next_url or url_for("admin.dashboard" if result.user.is_admin else "candidate.dashboard"))
        flash(result.message or "Login failed.", "danger")

    return render_template("auth/login.html", form=form)


@auth_bp.route("/logout")
@login_required
def logout():
    logout_user()
    flash("You have been logged out.", "info")
    return redirect(url_for("auth.login"))


@auth_bp.route("/register/admin", methods=["GET", "POST"])
def register_admin():
    form = AdminRegistrationForm()
    if form.validate_on_submit():
        user = auth_service.create_user(
            email=form.email.data,
            password=form.password.data,
            first_name=form.first_name.data,
            last_name=form.last_name.data,
            role="admin",
        )
        flash(f"Admin account for {user.email} created.", "success")
        return redirect(url_for("auth.login"))
    return render_template("auth/register_admin.html", form=form)


@auth_bp.before_app_request
def update_last_login():
    if current_user.is_authenticated:
        current_user.last_login_at = db.func.now()
        db.session.commit()


