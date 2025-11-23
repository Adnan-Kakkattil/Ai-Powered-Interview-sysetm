import MySQLdb
from config import Config

def init_db():
    try:
        # Connect to MySQL server (not specific DB yet to create it)
        db = MySQLdb.connect(
            host=Config.MYSQL_HOST,
            user=Config.MYSQL_USER,
            passwd=Config.MYSQL_PASSWORD
        )
        cursor = db.cursor()
        
        # Read schema.sql
        with open('schema.sql', 'r') as f:
            schema = f.read()
            
        # Execute schema commands
        # Split by ';' to execute statements one by one, as some drivers don't support multi-statement
        statements = schema.split(';')
        for statement in statements:
            if statement.strip():
                cursor.execute(statement)
        
        db.commit()
        print("Database initialized successfully.")
        
    except Exception as e:
        print(f"Error initializing database: {e}")
    finally:
        if 'cursor' in locals():
            cursor.close()
        if 'db' in locals():
            db.close()

if __name__ == '__main__':
    init_db()
