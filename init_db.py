import sqlite3
import os
from config import Config

def init_db():
    try:
        db_path = Config.DATABASE
        db_dir = os.path.dirname(db_path)
        if db_dir and db_dir != '/tmp':
            try:
                os.makedirs(db_dir, exist_ok=True)
            except OSError:
                pass
        schema_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'schema.sql')
        with open(schema_path, 'r') as f:
            schema = f.read()
        conn = sqlite3.connect(db_path)
        cursor = conn.cursor()
        cursor.executescript(schema)
        conn.commit()
        conn.close()
        print("Database initialized successfully.")
    except Exception as e:
        print(f"Error initializing database: {e}")

if __name__ == '__main__':
    init_db()
