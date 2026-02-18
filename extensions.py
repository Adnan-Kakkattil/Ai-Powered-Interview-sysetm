import os
import sqlite3
from flask import g
from flask_socketio import SocketIO

socketio = SocketIO()


def _make_row_factory(cursor, row):
    return dict(zip([col[0] for col in cursor.description], row))


def _ensure_schema(conn, app):
    """Create tables if they don't exist (e.g. first run on Vercel with /tmp DB)."""
    cursor = conn.cursor()
    cursor.execute("SELECT 1 FROM sqlite_master WHERE type='table' AND name='users'")
    if cursor.fetchone() is not None:
        return
    schema_path = os.path.join(app.root_path, 'schema.sql')
    if not os.path.isfile(schema_path):
        return
    with open(schema_path, 'r') as f:
        conn.executescript(f.read())
    conn.commit()


def get_db():
    """Return the request-scoped SQLite connection (dict-like rows). Use in route handlers only."""
    from flask import current_app
    if 'db' not in g:
        g.db = sqlite3.connect(current_app.config['DATABASE'])
        g.db.row_factory = _make_row_factory
        _ensure_schema(g.db, current_app)
    return g.db


def get_sqlite_connection(database_path):
    """Return a new SQLite connection with dict-like rows. Use in Socket.IO or outside request context. Caller must close."""
    conn = sqlite3.connect(database_path)
    conn.row_factory = _make_row_factory
    return conn
