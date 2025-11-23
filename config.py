import os
from dotenv import load_dotenv

load_dotenv()

class Config:
    SECRET_KEY = os.environ.get('SECRET_KEY') or 'dev_secret_key_123'
    MYSQL_HOST = os.environ.get('MYSQL_HOST') or '127.0.0.1'
    MYSQL_USER = os.environ.get('MYSQL_USER') or 'root'
    MYSQL_PASSWORD = os.environ.get('MYSQL_PASSWORD') or ''
    MYSQL_DB = os.environ.get('MYSQL_DB') or 'interview_system'
    MYSQL_CURSORCLASS = 'DictCursor'
