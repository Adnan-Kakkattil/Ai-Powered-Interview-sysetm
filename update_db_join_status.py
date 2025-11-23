import MySQLdb
from config import Config

def update_db_join_status():
    try:
        db = MySQLdb.connect(
            host=Config.MYSQL_HOST,
            user=Config.MYSQL_USER,
            passwd=Config.MYSQL_PASSWORD,
            db=Config.MYSQL_DB
        )
        cursor = db.cursor()
        
        print("Applying schema updates for join status...")
        
        # Add candidate_join_status column
        try:
            cursor.execute("ALTER TABLE interviews ADD COLUMN candidate_join_status ENUM('pending', 'requested', 'approved', 'rejected') DEFAULT 'pending'")
            print("Added candidate_join_status column to interviews table.")
        except Exception as e:
            print(f"Column candidate_join_status might already exist: {e}")

        db.commit()
        print("Database updated successfully.")
        
    except Exception as e:
        print(f"Connection error: {e}")
    finally:
        if 'cursor' in locals():
            cursor.close()
        if 'db' in locals():
            db.close()

if __name__ == '__main__':
    update_db_join_status()
