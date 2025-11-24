from flask import Flask, render_template
from config import Config
from extensions import mysql, socketio

app = Flask(__name__)
app.config.from_object(Config)

mysql.init_app(app)
socketio.init_app(app)

# Import routes (will be created later)
from routes import auth, main
app.register_blueprint(auth.bp)
app.register_blueprint(main.bp)

@app.route('/')
def index():
    return render_template('base.html')

from flask_socketio import join_room, leave_room, emit

from flask import request

connected_users = {}

@socketio.on('join')
def on_join(data):
    room = data['room']
    username = data.get('username')
    join_room(room)
    
    # If user was in waiting room, leave it
    user_info = connected_users.get(request.sid)
    if user_info and user_info.get('waiting'):
        waiting_room = user_info.get('room', '')
        if waiting_room and waiting_room.endswith('_waiting'):
            leave_room(waiting_room)
    
    # Store user info for disconnect handling
    connected_users[request.sid] = {'room': room, 'username': username, 'waiting': False}
    
    emit('user-joined', {'username': username}, room=room, include_self=False)
    emit('joined', room=room, include_self=False)

@socketio.on('disconnect')
def on_disconnect():
    user_info = connected_users.get(request.sid)
    if user_info:
        room = user_info['room']
        username = user_info['username']
        emit('user-left', {'username': username}, room=room, include_self=False)
        del connected_users[request.sid]

@socketio.on('leave')
def on_leave(data):
    room = data['room']
    username = data.get('username')
    leave_room(room)
    
    # Remove from connected_users if they explicitly leave
    if request.sid in connected_users:
        del connected_users[request.sid]
        
    emit('user-left', {'username': username}, room=room, include_self=False)

@socketio.on('code-change')
def on_code_change(data):
    room = data['room']
    code = data['code']
    
    # Save code to DB
    try:
        cursor = mysql.connection.cursor()
        cursor.execute('UPDATE interviews SET code_content = %s WHERE meeting_link = %s', (code, room))
        mysql.connection.commit()
        cursor.close()
    except Exception as e:
        print(f"Error saving code: {e}")
        
    # Broadcast code to everyone else in the room
    emit('code-update', {'code': code}, room=room, include_self=False)

@socketio.on('chat-message')
def on_chat_message(data):
    room = data['room']
    message = data['message']
    username = data['username']
    timestamp = data.get('timestamp')
    
    # Save chat to DB
    try:
        cursor = mysql.connection.cursor()
        # Get interview ID first
        cursor.execute('SELECT id FROM interviews WHERE meeting_link = %s', (room,))
        interview = cursor.fetchone()
        if interview:
            cursor.execute('INSERT INTO chat_messages (interview_id, sender_username, message) VALUES (%s, %s, %s)', 
                           (interview[0], username, message))
            mysql.connection.commit()
        cursor.close()
    except Exception as e:
        print(f"Error saving chat: {e}")
        
    emit('chat-message', {'message': message, 'username': username, 'timestamp': timestamp}, room=room, include_self=False)

    emit('chat-message', {'message': message, 'username': username, 'timestamp': timestamp}, room=room, include_self=False)

@socketio.on('request-join')
def on_request_join(data):
    room = data['room']
    username = data.get('username')
    
    # Join waiting room to receive approval
    join_room(f"{room}_waiting")
    
    # Store user info for waiting room
    connected_users[request.sid] = {'room': f"{room}_waiting", 'username': username, 'waiting': True}
    
    # Update DB status
    try:
        cursor = mysql.connection.cursor()
        cursor.execute("UPDATE interviews SET candidate_join_status = 'requested' WHERE meeting_link = %s", (room,))
        mysql.connection.commit()
        cursor.close()
    except Exception as e:
        print(f"Error updating join status: {e}")
        
    # Notify interviewer (who is in the main room)
    emit('join-request', {'username': username}, room=room)

@socketio.on('approve-join')
def on_approve_join(data):
    room = data['room']
    
    # Update DB status
    try:
        cursor = mysql.connection.cursor()
        cursor.execute("UPDATE interviews SET candidate_join_status = 'approved' WHERE meeting_link = %s", (room,))
        mysql.connection.commit()
        cursor.close()
    except Exception as e:
        print(f"Error approving join: {e}")
        
    # Notify candidate (in waiting room) and move them to main room
    emit('join-approved', {'room': room}, room=f"{room}_waiting")
    
    # Also emit to main room to notify interviewer
    emit('candidate-approved', {'message': 'Candidate has been approved and joined'}, room=room)

@socketio.on('reject-join')
def on_reject_join(data):
    room = data['room']
    
    # Update DB status
    try:
        cursor = mysql.connection.cursor()
        cursor.execute("UPDATE interviews SET candidate_join_status = 'rejected' WHERE meeting_link = %s", (room,))
        mysql.connection.commit()
        cursor.close()
    except Exception as e:
        print(f"Error rejecting join: {e}")
        
    # Notify candidate (in waiting room)
    emit('join-rejected', {'message': 'Your request to join was denied'}, room=f"{room}_waiting")

@socketio.on('offer')
def on_offer(data):
    room = data['room']
    emit('offer', data, room=room, include_self=False)

@socketio.on('answer')
def on_answer(data):
    room = data['room']
    emit('answer', data, room=room, include_self=False)

@socketio.on('ice-candidate')
def on_ice_candidate(data):
    room = data['room']
    emit('ice-candidate', data, room=room, include_self=False)

if __name__ == '__main__':
    socketio.run(app, debug=True)
