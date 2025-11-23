from flask import Blueprint, render_template, request, redirect, url_for, flash, session
from flask_mysqldb import MySQL
from werkzeug.security import generate_password_hash, check_password_hash
import MySQLdb.cursors

bp = Blueprint('auth', __name__)

# We need to access mysql from the main app, but circular imports are tricky.
# A common pattern is to use current_app, or pass mysql to the blueprint, 
# or just import it if it's initialized in a separate extensions file.
# For simplicity in this structure, we'll import from app inside the function or use current_app extension pattern.
# However, since app.py imports this, we can't import app here easily.
# Let's use the 'current_app' context or a separate extensions.py. 
# For now, I'll use a helper to get the db connection or just import mysql from app inside functions if needed, 
# but better to move mysql init to a separate file or use current_app.extensions.

from extensions import mysql

def get_db():
    return mysql

@bp.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        
        cursor = get_db().connection.cursor(MySQLdb.cursors.DictCursor)
        cursor.execute('SELECT * FROM users WHERE username = %s OR email = %s', (username, username))
        user = cursor.fetchone()
        
        if user and check_password_hash(user['password_hash'], password):
            session['user_id'] = user['id']
            session['role'] = user['role']
            session['name'] = user['name']
            flash('Logged in successfully.', 'success')
            return redirect(url_for('main.dashboard'))
        else:
            flash('Invalid username or password.', 'danger')
            
    return render_template('login.html')

@bp.route('/setup-admin', methods=['GET', 'POST'])
def setup_admin():
    cursor = get_db().connection.cursor(MySQLdb.cursors.DictCursor)
    cursor.execute('SELECT * FROM users WHERE role = "admin"')
    admin = cursor.fetchone()
    
    if admin:
        flash('Admin already exists.', 'warning')
        return redirect(url_for('auth.login'))
        
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        name = request.form['name']
        email = request.form['email']
        
        hashed_password = generate_password_hash(password)
        
        try:
            cursor.execute('INSERT INTO users (username, password_hash, role, name, email) VALUES (%s, %s, "admin", %s, %s)',
                           (username, hashed_password, name, email))
            get_db().connection.commit()
            flash('Admin created successfully. Please login.', 'success')
            return redirect(url_for('auth.login'))
        except Exception as e:
            flash(f'Error creating admin: {e}', 'danger')
            
    return render_template('setup_admin.html')

@bp.route('/logout')
def logout():
    session.clear()
    flash('Logged out successfully.', 'info')
    return redirect(url_for('auth.login'))
