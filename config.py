import os
from datetime import timedelta
from urllib.parse import quote_plus


class Config:
    """Base Flask configuration."""

    SECRET_KEY = os.getenv("FLASK_SECRET_KEY", "change-me")
    SESSION_COOKIE_SECURE = False
    REMEMBER_COOKIE_DURATION = timedelta(days=7)

    # Database
    DB_USER = os.getenv("DB_USER", "root")
    DB_PASSWORD = os.getenv("DB_PASSWORD", "Adnan@66202")
    DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
    DB_PORT = os.getenv("DB_PORT", "3306")
    DB_NAME = os.getenv("DB_NAME", "ai_interview_system")

    _encoded_password = quote_plus(DB_PASSWORD)

    SQLALCHEMY_DATABASE_URI = os.getenv(
        "DATABASE_URL",
        f"mysql+pymysql://{DB_USER}:{_encoded_password}@{DB_HOST}:{DB_PORT}/{DB_NAME}",
    )
    SQLALCHEMY_TRACK_MODIFICATIONS = False

    # File storage paths
    UPLOAD_FOLDER = os.getenv("UPLOAD_FOLDER", "uploads")
    SNAPSHOT_FOLDER = os.getenv("SNAPSHOT_FOLDER", "snapshots")

    # Security
    PASSWORD_POLICY = {
        "min_length": int(os.getenv("PASSWORD_MIN_LENGTH", 8)),
        "require_uppercase": os.getenv("PASSWORD_REQUIRE_UPPERCASE", "1") == "1",
        "require_digit": os.getenv("PASSWORD_REQUIRE_DIGIT", "1") == "1",
        "require_special": os.getenv("PASSWORD_REQUIRE_SPECIAL", "0") == "1",
    }

    # Eye detection / AI services
    EYE_DETECTION_ENABLED = os.getenv("EYE_DETECTION_ENABLED", "1") == "1"
    EYE_DETECTION_ENDPOINT = os.getenv(
        "EYE_DETECTION_ENDPOINT", "http://localhost:8000/detect"
    )

    # Code execution sandbox (to be wired later)
    CODE_SANDBOX_ENDPOINT = os.getenv(
        "CODE_SANDBOX_ENDPOINT", "http://localhost:9000/execute"
    )

    @staticmethod
    def init_app(app):
        os.makedirs(app.config["UPLOAD_FOLDER"], exist_ok=True)
        os.makedirs(app.config["SNAPSHOT_FOLDER"], exist_ok=True)


class DevelopmentConfig(Config):
    DEBUG = True


class TestingConfig(Config):
    TESTING = True
    SQLALCHEMY_DATABASE_URI = "sqlite:///:memory:"
    WTF_CSRF_ENABLED = False


class ProductionConfig(Config):
    SESSION_COOKIE_SECURE = True
    REMEMBER_COOKIE_SECURE = True


config_by_name = {
    "development": DevelopmentConfig,
    "testing": TestingConfig,
    "production": ProductionConfig,
    "default": Config,
}


