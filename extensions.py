import sqlite3
from flask import g
from flask_socketio import SocketIO

socketio = SocketIO()


def _make_row_factory(cursor, row):
    return dict(zip([col[0] for col in cursor.description], row))


def get_db():
    """Return the request-scoped SQLite connection (dict-like rows). Use in route handlers only."""
    from flask import current_app
    if 'db' not in g:
        g.db = sqlite3.connect(current_app.config['DATABASE'])
        g.db.row_factory = _make_row_factory
    return g.db


def get_sqlite_connection(database_path):
    """Return a new SQLite connection with dict-like rows. Use in Socket.IO or outside request context. Caller must close."""
    conn = sqlite3.connect(database_path)
    conn.row_factory = _make_row_factory
    return conn
