# AI Powered Interview System

Flask-based platform for conducting remote coding interviews with real-time monitoring, role-based administration, and MySQL persistence.

## Features

- Admin, candidate, and superadmin roles
- Admin dashboard to create interviews and assign candidates
- Candidate portal with embedded code editor (Monaco placeholder) and webcam eye-tracking hook
- Audit logs for interview activity, code submissions, and optional snapshot storage
- Bootstrap script to initialize the database and create the first superadmin account

## Tech Stack

- Python 3.11+
- Flask, Flask-Login, Flask-Migrate, SQLAlchemy
- MySQL 8 (or compatible)
- WTForms for server-side validation
- Monaco editor (front-end asset placeholder)

## Getting Started

1. **Clone the repository**

   ```bash
   git clone https://github.com/your-org/ai-powered-interview-system.git
   cd ai-powered-interview-system
   ```

2. **Create and activate a virtual environment**

   ```bash
   python -m venv .venv
   .venv\Scripts\activate  # Windows
   # source .venv/bin/activate  # Linux / macOS
   ```

3. **Install dependencies**

   ```bash
   pip install -r requirements.txt
   ```

4. **Configure environment variables**

   Create a `.env` file (or set system environment variables):

   ```
   FLASK_CONFIG=development
   FLASK_SECRET_KEY=your-secret
   DB_USER=root
   DB_PASSWORD=yourpassword
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=ai_interview_system
   ```

5. **Create the database schema**

   ```bash
   mysql -u root -p < schema.sql
   ```

   or run the setup command:

   ```bash
   python setup.py init-db
   ```

6. **Create the first superadmin**

   ```bash
   python setup.py create-superadmin
   ```

7. **Run the development server**

   ```bash
   flask --app app:create_app --debug run
   ```

   Visit `http://127.0.0.1:5000` in your browser.

## Project Structure

```
.
├─ app.py                # Flask app factory and entry point
├─ config.py             # Configuration classes and helpers
├─ models.py             # SQLAlchemy models
├─ forms.py              # WTForms definitions
├─ services/             # Business logic layer
├─ routes/               # Blueprint route handlers
├─ templates/            # Jinja templates
├─ static/               # CSS/JS assets (Monaco placeholder)
├─ schema.sql            # MySQL schema
├─ setup.py              # CLI for bootstrap tasks
└─ README.md
```

## Next Steps

- Integrate real eye-tracking SDK/ML service
- Wire code submission endpoint and sandbox execution
- Add REST API endpoints for SPA integrations
- Implement unit/integration tests (pytest + coverage)


