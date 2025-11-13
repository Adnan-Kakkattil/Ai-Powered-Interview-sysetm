import os
from getpass import getpass

import click

from app import create_app
from models import User, db
from services.auth_service import hash_password


def _get_app():
    config_name = os.getenv("FLASK_CONFIG", "development")
    return create_app(config_name)


@click.group()
def cli():
    """Setup commands for the AI Interview System."""


@cli.command("init-db")
def init_db():
    """Create all tables in the database."""
    app = _get_app()
    with app.app_context():
        db.create_all()
        click.echo("Database initialized.")


@cli.command("create-superadmin")
@click.option("--email", prompt=True)
@click.option("--first-name", prompt=True)
@click.option("--last-name", prompt=True)
def create_superadmin(email: str, first_name: str, last_name: str):
    """Create the initial superadmin user."""
    app = _get_app()
    with app.app_context():
        if User.query.filter_by(email=email).first():
            click.echo("User with that email already exists.")
            return

        password = getpass("Password: ")
        confirm = getpass("Confirm password: ")
        if password != confirm:
            click.echo("Passwords do not match.")
            return

        user = User(
            email=email.lower(),
            password_hash=hash_password(password),
            first_name=first_name,
            last_name=last_name,
            role="superadmin",
        )
        db.session.add(user)
        db.session.commit()
        click.echo(f"Superadmin {email} created.")


if __name__ == "__main__":
    cli()


