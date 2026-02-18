"""Quick test script. Run: python run_tests.py"""
import os
import sys

# Ensure app can be imported
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
os.environ.setdefault('RESET_ADMIN_SECRET', 'testkey')

def main():
    from app import app
    failed = []
    with app.test_client() as c:
        # Reset and create admin
        r = c.get('/reset-admin?key=testkey', follow_redirects=False)
        if r.status_code != 302:
            failed.append('reset-admin')
        r = c.post('/setup-admin', data={
            'username': 'testuser', 'password': 'testpass', 'name': 'Test', 'email': 'test@test.com'
        }, follow_redirects=False)
        if r.status_code != 302:
            failed.append('setup-admin')
        r = c.post('/login', data={'username': 'testuser', 'password': 'testpass'}, follow_redirects=False)
        if r.status_code != 302:
            failed.append('login')
        r = c.get('/dashboard')
        if r.status_code != 200:
            failed.append('dashboard')
        r = c.get('/add-candidate')
        if r.status_code != 200:
            failed.append('add-candidate')
        r = c.get('/schedule-interview')
        if r.status_code != 200:
            failed.append('schedule-interview')
    if failed:
        print('FAILED:', failed)
        sys.exit(1)
    print('All checks passed.')

if __name__ == '__main__':
    main()
