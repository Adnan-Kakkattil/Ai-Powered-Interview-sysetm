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
    
    # Store user info for disconnect handling
    connected_users[request.sid] = {'room': room, 'username': username}
    
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
    # Broadcast code to everyone else in the room
    emit('code-update', {'code': code}, room=room, include_self=False)

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
