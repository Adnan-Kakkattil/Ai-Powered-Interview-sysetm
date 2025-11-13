## PHP UI Plan

We will build a lightweight PHP front-end that consumes the existing Node.js API. The UI lives in the `UI/` directory and follows a simple layout pattern with shared configuration and reusable partials.

### Goals
- Serve HTML pages that mirror the provided dashboard design.
- Centralize API access (base URL, auth) in `config.php`.
- Fetch data from backend endpoints using cURL (or file_get_contents with stream context).
- Keep views modular so additional pages (interviews, candidates, analytics) can be added easily.

### Directory Structure

```
UI/
  assets/             # CSS/JS helpers (if needed)
  includes/           # shared partials (header.php, footer.php, api.php)
  config.php          # base configuration (API URL, session setup)
  index.php           # landing page / login redirect
  setup.php           # initial admin creation
  dashboard.php       # admin dashboard
  login.php           # (placeholder) authentication
  helpers.php         # utility functions (optional)
```

### API Integration Strategy
- Store base API URL and endpoints in `config.php`.
- Implement `fetchFromApi($path, $method = 'GET', $payload = null)` using cURL with JWT stored in PHP session.
- Handle token refresh/login in `login.php`; redirect unauthorized users.
- Use backend routes:
  - `POST /api/auth/login` → obtain JWT
  - `GET /api/admin/interviews` → scheduled/completed interviews
  - `GET /api/admin/candidates` → candidate stats
  - `GET /api/interviews/{id}/logs` → detail pages (future)

### Implementation Steps
1. Create `UI/` folder and base files (`config.php`, `includes/header.php`, `includes/footer.php`, etc.).
2. Build `index.php` that checks session and redirects to `dashboard.php` or `login.php`.
3. Add `setup.php` for first-run admin creation (`POST /api/auth/register-admin`), redirecting to login once done.
4. Move provided dashboard HTML into `dashboard.php`, extracting navigation/header/footer into partials.
5. Replace static dashboard metrics with API-driven data (fall back to sample data if API unreachable).
6. Expand with additional pages as needed (interviews list, candidate profiles, analytics).

### Upcoming UI Pages
- `interviews.php`: list upcoming/past interviews, trigger scheduling modal, cancel/reschedule actions.
- `interview_schedule.php`: server-rendered form pulling candidates (`GET /api/admin/candidates`), interviewers (`GET /api/admin/interviewers` – TBD), coding tasks.
- `candidates.php`: searchable table of candidate profiles with status tags.
- `notifications.php`: queue and status overview (optional).

Each page will reuse shared components and API helpers, keeping logic thin on the view.

### Next Actions
- [ ] Create initial file structure.
- [ ] Implement configuration and API helper.
- [ ] Port dashboard template into PHP views.
- [ ] Hook up real API data and authentication flow.

