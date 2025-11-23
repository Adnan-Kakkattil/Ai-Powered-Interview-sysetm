import MySQLdb
from config import Config

def update_db():
    try:
        db = MySQLdb.connect(
            host=Config.MYSQL_HOST,
            user=Config.MYSQL_USER,
            passwd=Config.MYSQL_PASSWORD,
            db=Config.MYSQL_DB
        )
        cursor = db.cursor()
        
        print("Applying schema updates...")
        
        # Add code_content column
        try:
            cursor.execute("ALTER TABLE interviews ADD COLUMN code_content TEXT")
            print("Added code_content column to interviews table.")
        except Exception as e:
            print(f"Column code_content might already exist: {e}")

        # Create chat_messages table
        try:
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS chat_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    interview_id INT NOT NULL,
                    sender_username VARCHAR(50) NOT NULL,
                    message TEXT NOT NULL,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (interview_id) REFERENCES interviews(id)
                )
            """)
            print("Created chat_messages table.")
        except Exception as e:
            print(f"Error creating chat_messages table: {e}")

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
    update_db()
