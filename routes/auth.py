from flask import Blueprint, render_template, request, redirect, url_for, flash, session
from werkzeug.security import generate_password_hash, check_password_hash

from extensions import get_db

bp = Blueprint('auth', __name__)


@bp.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']

        db = get_db()
        cursor = db.cursor()
        cursor.execute('SELECT * FROM users WHERE username = ? OR email = ?', (username, username))
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
    db = get_db()
    cursor = db.cursor()
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
            cursor.execute(
                'INSERT INTO users (username, password_hash, role, name, email) VALUES (?, ?, "admin", ?, ?)',
                (username, hashed_password, name, email)
            )
            db.commit()
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
