from flask import Blueprint, render_template, session, redirect, url_for, flash, request, jsonify, current_app, send_file, abort
from werkzeug.security import generate_password_hash
from werkzeug.utils import secure_filename
import MySQLdb.cursors
from extensions import mysql
import os
import uuid

bp = Blueprint('main', __name__)

def get_db():
    return mysql

ALLOWED_RESUME_EXTENSIONS = {"pdf", "doc", "docx"}
MAX_RESUME_BYTES = 10 * 1024 * 1024  # 10MB

def _allowed_resume_filename(filename: str) -> bool:
    if not filename or "." not in filename:
        return False
    ext = filename.rsplit(".", 1)[1].lower()
    return ext in ALLOWED_RESUME_EXTENSIONS

def _ensure_candidate_profile_columns(cursor):
    """
    Ensure optional candidate-profile columns exist on `users`.
    Keeps the app working even if the DB was created with an older schema.
    """
    cursor.execute("SHOW COLUMNS FROM users")
    rows = cursor.fetchall()
    existing = set()
    for r in rows:
        # DictCursor => {'Field': 'colname', ...}
        if isinstance(r, dict):
            existing.add(r.get("Field"))
        else:
            existing.add(r[0])

    additions = []
    if "phone" not in existing:
        additions.append("ADD COLUMN phone VARCHAR(30) NULL")
    if "target_role" not in existing:
        additions.append("ADD COLUMN target_role VARCHAR(100) NULL")
    if "experience_level" not in existing:
        additions.append("ADD COLUMN experience_level VARCHAR(100) NULL")
    if "resume_path" not in existing:
        additions.append("ADD COLUMN resume_path VARCHAR(255) NULL")
    if "resume_original_name" not in existing:
        additions.append("ADD COLUMN resume_original_name VARCHAR(255) NULL")

    if additions:
        cursor.execute(f"ALTER TABLE users {', '.join(additions)}")

@bp.route('/')
def index():
    return render_template('index.html')

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
        phone = request.form.get('phone') or None
        target_role = request.form.get('target_role') or None
        experience_level = request.form.get('experience_level') or None
        username = request.form['username']
        password = request.form['password']
        
        hashed_password = generate_password_hash(password)
        cursor = get_db().connection.cursor(MySQLdb.cursors.DictCursor)

        # Ensure DB has columns for optional profile info + resume
        try:
            _ensure_candidate_profile_columns(cursor)
            get_db().connection.commit()
        except Exception as e:
            # If ALTER fails (permissions), we can still create candidate without extra fields.
            print(f"Warning: could not ensure candidate profile columns: {e}")

        # Handle resume upload (optional)
        resume_file = request.files.get("resume")
        resume_rel_path = None
        resume_original_name = None

        try:
            if resume_file and resume_file.filename:
                if not _allowed_resume_filename(resume_file.filename):
                    flash('Invalid resume file type. Please upload PDF, DOC, or DOCX.', 'danger')
                    cursor.close()
                    return render_template('add_candidate.html')

                # Size check (best-effort; Flask MAX_CONTENT_LENGTH is even better)
                try:
                    resume_file.stream.seek(0, os.SEEK_END)
                    size = resume_file.stream.tell()
                    resume_file.stream.seek(0)
                    if size > MAX_RESUME_BYTES:
                        flash('Resume file is too large. Max allowed size is 10MB.', 'danger')
                        cursor.close()
                        return render_template('add_candidate.html')
                except Exception:
                    # If the stream isn't seekable, rely on server-side max config / web server limits
                    pass

                upload_dir = os.path.join(current_app.root_path, "uploads", "resumes")
                os.makedirs(upload_dir, exist_ok=True)

                original = resume_file.filename
                safe_name = secure_filename(original)
                unique_name = f"{uuid.uuid4().hex}_{safe_name}" if safe_name else f"{uuid.uuid4().hex}.bin"
                save_path = os.path.join(upload_dir, unique_name)
                resume_file.save(save_path)

                resume_rel_path = f"uploads/resumes/{unique_name}"
                resume_original_name = original
        except Exception as e:
            flash(f'Error uploading resume: {e}', 'danger')
            cursor.close()
            return render_template('add_candidate.html')
        
        try:
            # Build INSERT dynamically based on existing columns
            cursor.execute("SHOW COLUMNS FROM users")
            cols = cursor.fetchall()
            existing = set()
            for r in cols:
                if isinstance(r, dict):
                    existing.add(r.get("Field"))
                else:
                    existing.add(r[0])

            insert_cols = ["username", "password_hash", "role", "name", "email"]
            insert_vals = [username, hashed_password, "candidate", name, email]

            if "phone" in existing:
                insert_cols.append("phone")
                insert_vals.append(phone)
            if "target_role" in existing:
                insert_cols.append("target_role")
                insert_vals.append(target_role)
            if "experience_level" in existing:
                insert_cols.append("experience_level")
                insert_vals.append(experience_level)
            if "resume_path" in existing:
                insert_cols.append("resume_path")
                insert_vals.append(resume_rel_path)
            if "resume_original_name" in existing:
                insert_cols.append("resume_original_name")
                insert_vals.append(resume_original_name)

            placeholders = ", ".join(["%s"] * len(insert_cols))
            col_list = ", ".join(insert_cols)
            cursor.execute(
                f'INSERT INTO users ({col_list}) VALUES ({placeholders})',
                tuple(insert_vals)
            )
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
        
    cursor = get_db().connection.cursor(MySQLdb.cursors.DictCursor)
    
    # Fetch interview details with names
    cursor.execute('''
        SELECT i.*, 
               c.name as candidate_name, 
               intv.name as interviewer_name 
        FROM interviews i 
        JOIN users c ON i.candidate_id = c.id 
        JOIN users intv ON i.interviewer_id = intv.id 
        WHERE i.meeting_link = %s
    ''', (meeting_link,))
    interview_data = cursor.fetchone()
    
    if not interview_data:
        flash('Interview not found.', 'danger')
        return redirect(url_for('main.dashboard'))
        
    user_id = session['user_id']
    
    # Validate participation
    if user_id != interview_data['interviewer_id'] and user_id != interview_data['candidate_id']:
        flash('You are not authorized to join this interview.', 'danger')
        return redirect(url_for('main.dashboard'))
        
    # Determine role for this specific interview
    role = 'interviewer' if user_id == interview_data['interviewer_id'] else 'candidate'
    
    # Fetch chat history
    cursor.execute('SELECT sender_username, message, DATE_FORMAT(timestamp, "%%H:%%i") as timestamp FROM chat_messages WHERE interview_id = %s ORDER BY timestamp ASC', (interview_data['id'],))
    chat_history = cursor.fetchall()
    
    # Fetch join status
    join_status = interview_data.get('candidate_join_status', 'pending')

    # Fetch candidate resume info (optional; older DBs might not have columns)
    resume_path = None
    resume_original_name = None
    resume_ext = None
    try:
        cursor.execute('SELECT resume_path, resume_original_name FROM users WHERE id = %s', (interview_data['candidate_id'],))
        r = cursor.fetchone()
        if r:
            resume_path = r.get('resume_path')
            resume_original_name = r.get('resume_original_name')
            if resume_original_name and "." in resume_original_name:
                resume_ext = resume_original_name.rsplit(".", 1)[1].lower()
            elif resume_path and "." in resume_path:
                resume_ext = resume_path.rsplit(".", 1)[1].lower()
    except Exception as e:
        # Ignore if columns don't exist
        print(f"Resume lookup skipped: {e}")

    resume_url = url_for('main.interview_resume', meeting_link=meeting_link) if resume_path else None

    return render_template(
        'interview.html',
        meeting_link=meeting_link,
        interview=interview_data,
        role=role,
        chat_history=chat_history,
        join_status=join_status,
        resume_url=resume_url,
        resume_original_name=resume_original_name,
        resume_ext=resume_ext
    )


@bp.route('/interview/<meeting_link>/resume')
def interview_resume(meeting_link):
    """Serve the candidate resume for this interview (authorized users only)."""
    if 'user_id' not in session:
        return redirect(url_for('auth.login'))

    cursor = get_db().connection.cursor(MySQLdb.cursors.DictCursor)
    cursor.execute('SELECT * FROM interviews WHERE meeting_link = %s', (meeting_link,))
    interview_data = cursor.fetchone()
    if not interview_data:
        cursor.close()
        abort(404)

    user_id = session.get('user_id')
    if user_id != interview_data['interviewer_id'] and user_id != interview_data['candidate_id']:
        cursor.close()
        abort(403)

    try:
        cursor.execute('SELECT resume_path, resume_original_name FROM users WHERE id = %s', (interview_data['candidate_id'],))
        user_row = cursor.fetchone()
    except Exception:
        cursor.close()
        abort(404)

    cursor.close()

    if not user_row:
        abort(404)

    resume_path = user_row.get('resume_path')
    resume_original_name = user_row.get('resume_original_name') or 'resume'

    if not resume_path:
        abort(404)

    # Prevent path traversal: only allow files under uploads/resumes
    base_dir = os.path.abspath(os.path.join(current_app.root_path, 'uploads', 'resumes'))
    abs_path = os.path.abspath(os.path.join(current_app.root_path, resume_path))
    if not abs_path.startswith(base_dir + os.sep):
        abort(404)

    if not os.path.exists(abs_path):
        abort(404)

    # Inline for PDF preview; other formats will download/open depending on browser
    return send_file(abs_path, as_attachment=False, download_name=resume_original_name)

@bp.route('/interview/<meeting_link>/complete', methods=['POST'])
def complete_interview(meeting_link):
    """Mark interview as completed - only interviewer can do this"""
    if 'user_id' not in session:
        return jsonify({'success': False, 'message': 'Not authenticated'}), 401
    
    cursor = get_db().connection.cursor(MySQLdb.cursors.DictCursor)
    
    # Fetch interview details
    cursor.execute('SELECT * FROM interviews WHERE meeting_link = %s', (meeting_link,))
    interview_data = cursor.fetchone()
    
    if not interview_data:
        cursor.close()
        return jsonify({'success': False, 'message': 'Interview not found'}), 404
    
    user_id = session['user_id']
    
    # Only interviewer can mark as completed
    if user_id != interview_data['interviewer_id']:
        cursor.close()
        return jsonify({'success': False, 'message': 'Only interviewer can mark interview as completed'}), 403
    
    # Update status to completed
    try:
        cursor.execute('UPDATE interviews SET status = "completed" WHERE meeting_link = %s', (meeting_link,))
        get_db().connection.commit()
        cursor.close()
        return jsonify({'success': True, 'message': 'Interview marked as completed'})
    except Exception as e:
        cursor.close()
        return jsonify({'success': False, 'message': f'Error updating status: {str(e)}'}), 500