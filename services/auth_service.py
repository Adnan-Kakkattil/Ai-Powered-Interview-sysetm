from __future__ import annotations

from dataclasses import dataclass
from typing import Optional

from flask import current_app
from werkzeug.security import check_password_hash, generate_password_hash

from models import User, db


@dataclass
class AuthResult:
    success: bool
    message: Optional[str] = None
    user: Optional[User] = None


def hash_password(plain_password: str) -> str:
    return generate_password_hash(plain_password)


def verify_password(hashed_password: str, candidate_password: str) -> bool:
    return check_password_hash(hashed_password, candidate_password)


def authenticate(email: str, password: str) -> AuthResult:
    user = User.query.filter_by(email=email).first()
    if not user:
        current_app.logger.info("Authentication failed for %s: user not found", email)
        return AuthResult(False, "Invalid credentials.")

    if not user.is_active:
        return AuthResult(False, "Account disabled.")

    if not verify_password(user.password_hash, password):
        current_app.logger.info("Authentication failed for %s: wrong password", email)
        return AuthResult(False, "Invalid credentials.")

    return AuthResult(True, user=user)


def create_user(email: str, password: str, first_name: str, last_name: str, role: str) -> User:
    user = User(
        email=email.lower(),
        password_hash=hash_password(password),
        first_name=first_name,
        last_name=last_name,
        role=role,
    )
    db.session.add(user)
    db.session.commit()
    return user


