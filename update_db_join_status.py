import sqlite3
from config import Config

def update_db_join_status():
    try:
        conn = sqlite3.connect(Config.DATABASE)
        cursor = conn.cursor()

        print("Applying schema updates for join status...")

        try:
            cursor.execute(
                "ALTER TABLE interviews ADD COLUMN candidate_join_status TEXT NOT NULL DEFAULT 'pending'"
            )
            conn.commit()
            print("Added candidate_join_status column to interviews table.")
        except sqlite3.OperationalError as e:
            if "duplicate column" in str(e).lower():
                print("Column candidate_join_status already exists.")
            else:
                print(f"Error: {e}")

        conn.close()
        print("Database updated successfully.")
    except Exception as e:
        print(f"Error: {e}")

if __name__ == '__main__':
    update_db_join_status()
