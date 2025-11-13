import os

from flask import Flask
from flask_login import LoginManager
from flask_migrate import Migrate

from config import Config, config_by_name
from models import User, db
from routes import admin_bp, auth_bp, candidate_bp

migrate = Migrate()
login_manager = LoginManager()
login_manager.login_view = "auth.login"
login_manager.session_protection = "strong"


@login_manager.user_loader
def load_user(user_id: str):
    return User.query.get(int(user_id))


def create_app(config_name: str | None = None) -> Flask:
    app = Flask(__name__)

    config_name = config_name or os.getenv("FLASK_CONFIG", "development")
    config_class = config_by_name.get(config_name, Config)

    app.config.from_object(config_class)
    if hasattr(config_class, "init_app"):
        config_class.init_app(app)

    db.init_app(app)
    migrate.init_app(app, db)
    login_manager.init_app(app)

    app.register_blueprint(auth_bp)
    app.register_blueprint(admin_bp)
    app.register_blueprint(candidate_bp)

    @app.route("/")
    def index():
        return "AI Powered Interview System API"

    return app


if __name__ == "__main__":
    application = create_app()
    application.run(debug=application.config.get("DEBUG", False))


