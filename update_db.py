import sqlite3
from config import Config

def update_db():
    try:
        conn = sqlite3.connect(Config.DATABASE)
        cursor = conn.cursor()

        print("Applying schema updates...")

        # Add optional candidate profile columns (SQLite: ADD COLUMN one at a time)
        for col, typ in [
            ("phone", "TEXT"),
            ("target_role", "TEXT"),
            ("experience_level", "TEXT"),
            ("resume_path", "TEXT"),
            ("resume_original_name", "TEXT"),
        ]:
            try:
                cursor.execute(f"ALTER TABLE users ADD COLUMN {col} {typ}")
                conn.commit()
                print(f"Added {col} column to users table.")
            except sqlite3.OperationalError as e:
                if "duplicate column" in str(e).lower():
                    print(f"Column {col} already exists.")
                else:
                    print(f"Column {col}: {e}")

        try:
            cursor.execute("ALTER TABLE interviews ADD COLUMN code_content TEXT")
            conn.commit()
            print("Added code_content column to interviews table.")
        except sqlite3.OperationalError as e:
            if "duplicate column" in str(e).lower():
                print("Column code_content already exists.")
            else:
                print(f"code_content: {e}")

        cursor.execute("""
            CREATE TABLE IF NOT EXISTS chat_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                interview_id INTEGER NOT NULL,
                sender_username TEXT NOT NULL,
                message TEXT NOT NULL,
                timestamp TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (interview_id) REFERENCES interviews(id)
            )
        """)
        conn.commit()
        print("Created chat_messages table (if not exists).")

        conn.close()
        print("Database updated successfully.")
    except Exception as e:
        print(f"Error: {e}")

if __name__ == '__main__':
    update_db()
