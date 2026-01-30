# Docker Setup Guide

This guide explains how to run the AI-Powered Interview System using Docker Compose.

## Prerequisites

- Docker Desktop (Windows/Mac) or Docker Engine + Docker Compose (Linux)
- At least 2GB of free disk space
- Ports 5000 and 3306 available

## Quick Start

### 1. Build and Start Services

```bash
docker compose up --build
```

This will:
- Build the web application container
- Start the MySQL database container
- Initialize the database with the schema
- Start the Flask application on port 5000

### 2. Access the Application

- **Web Application**: http://localhost:5000
- **MySQL Database**: localhost:3306
  - Username: `root`
  - Password: `rootpassword`
  - Database: `interview_system`

### 3. First-Time Setup

1. Visit http://localhost:5000/setup-admin
2. Create your admin account
3. Login and start using the system

## Docker Compose Services

### Web Service (`web`)
- **Port**: 5000
- **Container Name**: `interview_system_web`
- **Dependencies**: Waits for database to be healthy before starting
- **Volumes**: 
  - `./uploads` → `/app/uploads` (resume storage)

### Database Service (`db`)
- **Port**: 3306
- **Container Name**: `interview_system_db`
- **Image**: MySQL 8.0
- **Volumes**:
  - `db_data`: Persistent database storage
  - `./schema.sql`: Auto-initializes database schema

## Useful Commands

### Start services in background
```bash
docker compose up -d
```

### View logs
```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f web
docker compose logs -f db
```

### Stop services
```bash
docker compose down
```

### Stop and remove volumes (⚠️ deletes database data)
```bash
docker compose down -v
```

### Rebuild after code changes
```bash
docker compose up --build
```

### Access database directly
```bash
docker compose exec db mysql -u root -prootpassword interview_system
```

### Access web container shell
```bash
docker compose exec web bash
```

### Restart a specific service
```bash
docker compose restart web
docker compose restart db
```

## Environment Variables

You can customize the configuration by creating a `.env` file in the project root:

```env
MYSQL_HOST=db
MYSQL_USER=root
MYSQL_PASSWORD=rootpassword
MYSQL_DB=interview_system
SECRET_KEY=your-secret-key-here
MAX_CONTENT_LENGTH=10485760
```

Or modify the `environment` section in `docker-compose.yml`.

## Alternative: Using XAMPP MySQL

If you have XAMPP MySQL running on port 3306, you have two options:

### Option 1: Use Docker MySQL on Different Port (Recommended)
The default `docker-compose.yml` now uses port **3307** for MySQL to avoid conflicts.

Access MySQL at: `localhost:3307`

### Option 2: Use Your Existing XAMPP MySQL
1. Ensure XAMPP MySQL is running
2. Create the database:
   ```sql
   CREATE DATABASE interview_system;
   ```
3. Import the schema:
   ```bash
   python init_db.py
   ```
   Or manually import `schema.sql` via phpMyAdmin
4. Use the alternative compose file:
   ```bash
   docker compose -f docker-compose.xampp.yml up --build
   ```

## Troubleshooting

### Port Already in Use
If port 5000 or 3306 is already in use:
1. **For MySQL (3306)**: The default config now uses port 3307. If you need 3306:
   - Stop XAMPP MySQL, or
   - Change the port mapping in `docker-compose.yml`:
     ```yaml
     ports:
       - "3307:3306"  # Already configured
     ```
2. **For Web App (5000)**: Change the port mapping:
   ```yaml
   ports:
     - "5001:5000"  # Use 5001 instead of 5000
   ```

### Database Connection Issues
- Wait for the database to be healthy (check with `docker compose ps`)
- Verify database credentials in `docker-compose.yml`
- Check database logs: `docker compose logs db`

### Application Not Starting
- Check web service logs: `docker compose logs web`
- Verify all environment variables are set correctly
- Ensure the database is healthy before web starts

### Permission Issues (Linux/Mac)
If you encounter permission issues with uploads:
```bash
docker compose exec web chmod -R 755 /app/uploads
```

## Production Considerations

⚠️ **Important**: Before deploying to production:

1. **Change default passwords** in `docker-compose.yml`
2. **Set a strong SECRET_KEY** for Flask sessions
3. **Use environment variables** instead of hardcoded values
4. **Enable HTTPS** (requires reverse proxy like nginx)
5. **Set up proper backups** for the database volume
6. **Configure firewall rules** for exposed ports
7. **Use Docker secrets** for sensitive data
8. **Set resource limits** in docker-compose.yml

## Health Checks

Both services include health checks:
- **Database**: Checks MySQL connectivity every 10 seconds
- **Web**: Checks HTTP endpoint every 30 seconds

The web service waits for the database to be healthy before starting.

## Network

Services communicate via a Docker bridge network (`interview_network`). The web service connects to the database using the hostname `db`.
