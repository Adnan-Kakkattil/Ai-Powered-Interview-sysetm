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

@socketio.on('join')
def on_join(data):
    room = data['room']
    join_room(room)
    # Notify others in the room that a user has joined, 
    # but for simple P2P, the second person joining triggers the offer flow usually initiated by the new comer or the existing one.
    # Here we'll just emit 'user-connected' to let existing users know, 
    # OR simpler: if there are 2 people, the second one triggers the flow?
    # Actually, standard pattern: 
    # Client A joins. Client B joins. 
    # If we want Client A (already there) to offer to Client B, we need to tell A that B joined.
    emit('joined', room=room, include_self=False) # Tell others I joined

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
