from flask import Blueprint, render_template, session, redirect, url_for, flash, request
from werkzeug.security import generate_password_hash
import MySQLdb.cursors
from extensions import mysql

bp = Blueprint('main', __name__)

def get_db():
    return mysql

@bp.route('/')
def index():
    return render_template('base.html')

@bp.route('/dashboard')
def dashboard():
    if 'user_id' not in session:
        return redirect(url_for('auth.login'))
    
    role = session.get('role')
    user_id = session.get('user_id')
    
    cursor = get_db().connection.cursor(MySQLdb.cursors.DictCursor)
    
    if role == 'admin':
        # Fetch all interviews
        cursor.execute('SELECT i.*, u.name as candidate_name FROM interviews i JOIN users u ON i.candidate_id = u.id ORDER BY i.scheduled_time DESC')
        interviews = cursor.fetchall()
        return render_template('dashboard.html', interviews=interviews, role='admin')
    else:
        # Fetch candidate's interviews
        cursor.execute('SELECT i.*, u.name as interviewer_name FROM interviews i JOIN users u ON i.interviewer_id = u.id WHERE i.candidate_id = %s ORDER BY i.scheduled_time DESC', (user_id,))
        interviews = cursor.fetchall()
        return render_template('dashboard.html', interviews=interviews, role='candidate')

@bp.route('/add-candidate', methods=['GET', 'POST'])
def add_candidate():
    if 'user_id' not in session or session.get('role') != 'admin':
        return redirect(url_for('auth.login'))
        
    if request.method == 'POST':
        name = request.form['name']
        email = request.form['email']
        username = request.form['username']
        password = request.form['password']
        
        hashed_password = generate_password_hash(password)
        cursor = get_db().connection.cursor(MySQLdb.cursors.DictCursor)
        
        try:
            cursor.execute('INSERT INTO users (username, password_hash, role, name, email) VALUES (%s, %s, "candidate", %s, %s)',
                           (username, hashed_password, name, email))
            get_db().connection.commit()
            flash('Candidate added successfully.', 'success')
            return redirect(url_for('main.dashboard'))
        except Exception as e:
            flash(f'Error adding candidate: {e}', 'danger')
            
    return render_template('add_candidate.html')

@bp.route('/schedule-interview', methods=['GET', 'POST'])
def schedule_interview():
    if 'user_id' not in session or session.get('role') != 'admin':
        return redirect(url_for('auth.login'))
        
    cursor = get_db().connection.cursor(MySQLdb.cursors.DictCursor)
    
    if request.method == 'POST':
        title = request.form['title']
        scheduled_time = request.form['scheduled_time']
        candidate_id = request.form['candidate_id']
        interviewer_id = session['user_id']
        
        # Generate a unique meeting link (simple UUID for now)
        import uuid
        meeting_link = str(uuid.uuid4())
        
        try:
            cursor.execute('INSERT INTO interviews (title, scheduled_time, interviewer_id, candidate_id, meeting_link) VALUES (%s, %s, %s, %s, %s)',
                           (title, scheduled_time, interviewer_id, candidate_id, meeting_link))
            get_db().connection.commit()
            flash('Interview scheduled successfully.', 'success')
            return redirect(url_for('main.dashboard'))
        except Exception as e:
            flash(f'Error scheduling interview: {e}', 'danger')
            
    # Fetch candidates for the dropdown
    cursor.execute('SELECT id, name FROM users WHERE role = "candidate"')
    candidates = cursor.fetchall()
    
    return render_template('schedule_interview.html', candidates=candidates)

@bp.route('/interview/<meeting_link>')
def interview(meeting_link):
    if 'user_id' not in session:
        return redirect(url_for('auth.login'))
        
    # Verify interview exists and user is participant
    # ... logic here ...
    
    return render_template('interview.html', meeting_link=meeting_link)
