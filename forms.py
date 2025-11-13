from __future__ import annotations

from datetime import time

from flask_wtf import FlaskForm
from wtforms import (
    BooleanField,
    DateField,
    PasswordField,
    SelectField,
    SelectMultipleField,
    StringField,
    TextAreaField,
    TimeField,
)
from wtforms.validators import DataRequired, Email, EqualTo, Length, Optional, URL


class LoginForm(FlaskForm):
    email = StringField("Email", validators=[DataRequired(), Email()])
    password = PasswordField("Password", validators=[DataRequired()])
    remember_me = BooleanField("Remember Me")


class AdminRegistrationForm(FlaskForm):
    first_name = StringField("First Name", validators=[DataRequired(), Length(max=100)])
    last_name = StringField("Last Name", validators=[DataRequired(), Length(max=100)])
    email = StringField("Email", validators=[DataRequired(), Email()])
    password = PasswordField(
        "Password",
        validators=[DataRequired(), Length(min=8)],
    )
    confirm_password = PasswordField(
        "Confirm Password",
        validators=[DataRequired(), EqualTo("password")],
    )


class CreateInterviewForm(FlaskForm):
    title = StringField("Title", validators=[DataRequired(), Length(max=255)])
    description = TextAreaField("Description", validators=[Optional()])
    duration_minutes = SelectField(
        "Duration (minutes)",
        choices=[(str(mins), f"{mins} minutes") for mins in (30, 45, 60, 90, 120)],
        coerce=int,
        validators=[DataRequired()],
    )
    scheduled_date = DateField("Scheduled Date", validators=[Optional()])
    scheduled_time = TimeField(
        "Scheduled Time",
        validators=[Optional()],
        default=time(hour=10, minute=0),
    )


class InviteCandidateForm(FlaskForm):
    interview_id = SelectField("Interview", coerce=int, validators=[DataRequired()])
    candidate_ids = SelectMultipleField(
        "Candidates",
        coerce=int,
        validators=[DataRequired()],
    )


class CandidateCreationForm(FlaskForm):
    first_name = StringField("First Name", validators=[DataRequired(), Length(max=100)])
    last_name = StringField("Last Name", validators=[DataRequired(), Length(max=100)])
    email = StringField("Email", validators=[DataRequired(), Email()])
    password = PasswordField(
        "Temporary Password",
        validators=[DataRequired(), Length(min=8)],
    )
    confirm_password = PasswordField(
        "Confirm Password",
        validators=[DataRequired(), EqualTo("password")],
    )
    resume_url = StringField("Resume URL", validators=[Optional(), URL()])


