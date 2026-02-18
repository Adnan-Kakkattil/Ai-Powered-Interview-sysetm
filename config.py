import os
from dotenv import load_dotenv

load_dotenv()

# SQLite database path (default: instance folder or project root)
def _db_path():
    env_path = os.environ.get('DATABASE_PATH')
    if env_path:
        return os.path.abspath(env_path)
    # Prefer instance folder so it's outside the app tree
    instance_path = os.environ.get('INSTANCE_PATH')
    if instance_path:
        os.makedirs(instance_path, exist_ok=True)
        return os.path.join(instance_path, 'interview_system.db')
    return os.path.join(os.path.dirname(os.path.abspath(__file__)), 'instance', 'interview_system.db')

class Config:
    SECRET_KEY = os.environ.get('SECRET_KEY', 'dev')
    DATABASE = _db_path()
    MAX_CONTENT_LENGTH = int(os.environ.get('MAX_CONTENT_LENGTH', str(10 * 1024 * 1024)))  # 10MB
    # Optional: secret for /reset-admin?key=... (allows removing admin so setup-admin can run again)
    RESET_ADMIN_SECRET = os.environ.get('RESET_ADMIN_SECRET', '')
