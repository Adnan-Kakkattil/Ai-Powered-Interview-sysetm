"""Run tests with: python -m pytest tests/test_app.py -v (or run this file)."""
import os
import sys
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

def test_home():
    from app import app
    with app.test_client() as c:
        r = c.get('/')
        assert r.status_code == 200

def test_login_page():
    from app import app
    with app.test_client() as c:
        r = c.get('/login')
        assert r.status_code == 200

def test_setup_admin_get():
    from app import app
    with app.test_client() as c:
        r = c.get('/setup-admin')
        assert r.status_code in (200, 302)
        if r.status_code == 302:
            assert 'login' in (r.headers.get('Location') or '')

def test_setup_admin_post_and_login():
    from app import app
    os.environ['RESET_ADMIN_SECRET'] = 'testkey'
    try:
        with app.test_client() as c:
            # Reset so we can create fresh admin
            c.get('/reset-admin?key=testkey')
            r = c.post('/setup-admin', data={
                'username': 'authtest',
                'password': 'authpass123',
                'name': 'Auth Test',
                'email': 'authtest@example.com'
            }, follow_redirects=False)
            assert r.status_code == 302
            assert 'login' in (r.headers.get('Location') or '')
            r2 = c.post('/login', data={'username': 'authtest', 'password': 'authpass123'}, follow_redirects=False)
            assert r2.status_code == 302
            assert 'dashboard' in (r2.headers.get('Location') or '')
            r3 = c.get('/dashboard', follow_redirects=False)
            assert r3.status_code == 200
    finally:
        os.environ.pop('RESET_ADMIN_SECRET', None)

def test_reset_admin_wrong_key():
    from app import app
    with app.test_client() as c:
        r = c.get('/reset-admin?key=wrongkey', follow_redirects=False)
        assert r.status_code == 302
        assert 'login' in (r.headers.get('Location') or '')

def test_reset_admin_correct_key():
    from app import app
    os.environ['RESET_ADMIN_SECRET'] = 'correctkey'
    try:
        with app.test_client() as c:
            r = c.get('/reset-admin?key=correctkey', follow_redirects=False)
            assert r.status_code == 302
            assert 'setup-admin' in (r.headers.get('Location') or '')
    finally:
        os.environ.pop('RESET_ADMIN_SECRET', None)

def test_dashboard_requires_login():
    from app import app
    with app.test_client() as c:
        r = c.get('/dashboard', follow_redirects=False)
        assert r.status_code == 302
        assert 'login' in (r.headers.get('Location') or '')

def test_add_candidate_requires_admin():
    from app import app
    with app.test_client() as c:
        r = c.get('/add-candidate', follow_redirects=False)
        assert r.status_code == 302

def test_schedule_requires_admin():
    from app import app
    with app.test_client() as c:
        r = c.get('/schedule-interview', follow_redirects=False)
        assert r.status_code == 302

def test_interview_details_requires_login():
    from app import app
    with app.test_client() as c:
        r = c.get('/interview/some-uuid/details', follow_redirects=False)
        assert r.status_code == 302

if __name__ == '__main__':
    import subprocess
    sys.exit(subprocess.run([sys.executable, '-m', 'pytest', __file__, '-v'], cwd=os.path.dirname(os.path.dirname(os.path.abspath(__file__)))).returncode)
