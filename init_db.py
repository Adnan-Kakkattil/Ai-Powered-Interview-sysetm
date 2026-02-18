import sqlite3
import os
from config import Config

def init_db():
    try:
        db_path = Config.DATABASE
        os.makedirs(os.path.dirname(db_path) or '.', exist_ok=True)

        with open('schema.sql', 'r') as f:
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
