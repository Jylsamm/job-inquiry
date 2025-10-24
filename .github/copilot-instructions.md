## WorkConnect PH — Copilot / AI agent instructions

This project is a small PHP web application (procedural + a few classes) served via XAMPP. The guidance below is focused, actionable, and specific to this repository so an AI agent can be productive immediately.

1. Big picture
 - Frontend: static pages in the repo root (e.g. `index.php`, `home.php`, `admin.php`) plus JS under `assets/js/` (auth, home, main, employer, jobseeker). Forms call backend endpoints in `api/` or submit to PHP pages.
 - API surface: `api/*.php` (e.g. `api/auth.php`, `api/jobs.php`, `api/applications.php`). Each file uses an `action` query parameter to route operations (GET/POST/PUT). Responses are JSON via `jsonResponse()` / `ApiResponse` helpers.
 - Shared code: `includes/` contains core helpers and classes: `config.php` (bootstrap, DB helpers, CSRF), `Database.php` (singleton DB wrapper), `functions.php` (business logic like `searchJobs`, `applyForJob`), `ApiResponse.php`, `ApiMiddleware.php`, `Security.php`, `Logger.php`.
 - Data: MySQL schema is `database/workconnect_ph.sql`. Uploads go to `uploads/` (companies/profiles/resumes).

2. Key patterns & conventions (do not invent alternatives)
 - Routing: APIs rely on query `action` and HTTP method. Example: `GET /api/jobs.php?action=search&q=developer`.
 - DB access: prefer `db_prepare_or_error($conn, $sql)` or `Database::getInstance()->prepareOrFail()` to get a prepared statement; many helper functions in `includes/functions.php` call `getDBConnection()` (defined in `includes/config.php`).
 - Auth: web sessions are used for UI pages (`$_SESSION['user_id']`, `user_role`). APIs use a token verified by `Security::verifyJwt()` inside `includes/ApiMiddleware.php` and set session fields. When changing auth, update both session logic and API middleware.
 - Error/response: use `jsonResponse()` (procedural code) or `ApiResponse::success()/error()` (class) for consistent JSON output. For API errors prefer the existing helpers so frontends and tests expect the same shape.
 - Security: CSRF helpers `generateCsrfToken()` / `validateCsrfToken()` live in `includes/config.php`. API headers & rate limiting are applied in `includes/ApiMiddleware.php`.

3. Where to make changes
 - Business rules: `includes/functions.php` (search, apply, profile save). Add high-level logic here and keep endpoint files thin.
 - New API endpoints: add a case to `api/<area>.php` with an `action` name and call into `includes/functions.php` or a new class under `includes/`.
 - DB changes: modify `database/workconnect_ph.sql` and update migrations/tests. The app expects certain tables (user, job, application, employer, job_seeker, rate_limits).

4. Developer workflows / commands
 - Local dev: run XAMPP (Apache + MySQL). Import `database/workconnect_ph.sql` into MySQL (database name: `workconnect_ph`).
 - Smoke tests: from project root run the PowerShell smoke script:

```powershell
# Run in PowerShell from repo root
.\tests\run_smoke.ps1
```

 - Manual API testing: use curl or Postman to hit `http://localhost/job-inquiry/api/<file>.php?action=<action>`; include `Authorization: Bearer <token>` for protected endpoints.

5. Examples to reference
 - Search jobs: `api/jobs.php?action=search` calls `searchJobs()` in `includes/functions.php`.
 - Auth flows: `api/auth.php` uses `loginUser()` / `registerUser()` from `includes/auth.php` and returns JSON via `ApiResponse`.
 - DB access: `includes/Database.php` is a singleton; it sets strict SQL mode and logs slow queries.

6. Important constraints / gotchas
 - No composer or JS build step — it's a plain PHP app. Do not add build tooling unless requested.
 - Many API endpoints expect JSON and return specific response shapes. Preserve the shape when editing (`success|status`, `message`, `data`).
 - Rate limiting and security headers are enforced in `includes/ApiMiddleware.php`. If adding new public endpoints, add them to the publicEndpoints list.
 - Sessions are used widely; tests and endpoints expect `$_SESSION['user_id']` for role checks. When simulating API calls in tests, set the token or session appropriately.

7. Files worth reading first
 - `includes/config.php` — bootstrap, DB helpers, CSRF, session handling
 - `includes/Database.php` — DB singleton, query helpers
 - `includes/functions.php` — business logic for jobs, applications, profiles
 - `api/*.php` — examples of how action routing is implemented
 - `tests/run_smoke.ps1` — how the maintainer runs quick smoke tests
 - `database/workconnect_ph.sql` — schema and seeded data expectations

If anything in this summary is unclear or you want more details (e.g., specific functions, where to add tests, or to expand examples), tell me which sections to improve and I will iterate.

---

## Quick examples and checklists (concrete)

### A — Add a new API action (example)
When adding a new API action, keep the endpoint thin and add business logic to `includes/functions.php`.

Steps (example: add `publish` action to `api/jobs.php` to mark a job as published):

1. Add a small case in `api/jobs.php` (use existing patterns):

```php
// inside POST switch ($action)
case 'publish':
	if (!hasRole('employer')) {
		jsonResponse(false, 'Access denied.');
	}
	$job_id = $input['job_id'] ?? 0;
	$employer_id = getCurrentEmployerId();
	$result = publishJob($job_id, $employer_id); // business logic in includes/functions.php
	jsonResponse($result['success'], $result['message']);
	break;
```

2. Implement the business logic in `includes/functions.php`:

```php
function publishJob($job_id, $employer_id) {
	$conn = getDBConnection();
	$stmt = db_prepare_or_error($conn, "UPDATE job SET status = 'published' WHERE job_id = ? AND employer_id = ?");
	$stmt->bind_param('ii', $job_id, $employer_id);
	if ($stmt->execute()) {
		return ['success' => true, 'message' => 'Job published.'];
	}
	return ['success' => false, 'message' => 'Failed to publish job.'];
}
```

Notes:
 - Use `db_prepare_or_error()` or `Database::getInstance()->prepareOrFail()` for consistent error handling.
 - Validate `hasRole()` and use `getCurrentEmployerId()` / `getCurrentJobSeekerId()` for ownership checks.
 - Return consistent JSON using `jsonResponse()` or `ApiResponse::success()/error()`.

### B — Safe DB change checklist
Follow these steps when updating schema or seeded data in `database/workconnect_ph.sql`:

1. Add schema changes to `database/workconnect_ph.sql` with a clear header comment and timestamp.
2. Locally: stop any dependent services if needed (or ensure MySQL is writable). Import the SQL into a test database first.

PowerShell example (from repo root):

```powershell
# Import file into local MySQL (XAMPP) - adjust username/password as needed
# mysql -u root workconnect_ph < .\database\workconnect_ph.sql
```

3. Run smoke tests: `.	ests
un_smoke.ps1` and check for obvious regressions.
4. Verify with targeted queries, for example:

```sql
-- Check new table or column
SHOW TABLES LIKE 'your_table';
DESCRIBE job;
SELECT COUNT(*) FROM job;
```

5. If adding columns used by PHP code, update `includes/functions.php` and any affected `api/*.php` files.
6. When ready, commit the SQL change and provide a short migration note in the commit message.

### C — PR / branch guidance (what I did)
I cannot open a remote PR for you, but I created these local changes and committed them on a dedicated branch so you can push and open a PR.

Recommended branch workflow (PowerShell):

```powershell
# create branch locally
git checkout -b update/copilot-instructions
# stage and commit
git add .github/copilot-instructions.md
git commit -m "docs: expand copilot instructions with API example, DB checklist, PR notes"
# push to origin and open a PR from the remote branch
git push -u origin update/copilot-instructions
```

If you'd like, I can also prepare a short PR description file under `.github/` that you can copy into the PR body.

