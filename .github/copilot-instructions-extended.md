## WorkConnect PH — Copilot / AI agent instructions (extended)

This companion file expands the quick examples and checklists for maintainers and AI agents.

### Add a new API action (step-by-step example)
Pattern: keep API files thin; place business logic in `includes/functions.php`.

Example: Add a `publish` action to `api/jobs.php` to mark a job as published.

1) In `api/jobs.php` (follow existing `POST` / `action` pattern):

```php
case 'publish':
    if (!hasRole('employer')) {
        jsonResponse(false, 'Access denied.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $job_id = (int) ($input['job_id'] ?? 0);
    $employer_id = getCurrentEmployerId();

    if (!$job_id || !$employer_id) {
        jsonResponse(false, 'Invalid request.');
    }

    $result = publishJob($job_id, $employer_id); // function in includes/functions.php
    jsonResponse($result['success'], $result['message']);
    break;
```

2) In `includes/functions.php` (new helper):

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
- Use `db_prepare_or_error()` to ensure API-friendly errors are returned.
- Use `getCurrentEmployerId()` or `getCurrentJobSeekerId()` rather than raw `$_SESSION['user_id']` for role-specific data.
- Keep authorization checks in the API file (`hasRole()`), and keep DB/business logic in `includes/functions.php` for reusability and testability.

### Safe DB change checklist (expanded)
1) Edit `database/workconnect_ph.sql` and add a clear comment block at top of your change with date and purpose.

2) Import into a non-production test DB first. Example PowerShell command (adjust credentials):

```powershell
# From repo root
mysql -u root workconnect_ph_test < .\database\workconnect_ph.sql
```

3) Run smoke tests and any targeted integration tests:

```powershell
.\tests\run_smoke.ps1
```

4) Run verification SQL queries to validate schema and seed data:

```sql
SHOW TABLES LIKE 'query_log';
DESCRIBE job;
SELECT COUNT(*) FROM user;
```

5) Update PHP code that reads new columns/tables (usually `includes/functions.php` and any `api/*.php` endpoints).

6) Commit SQL and code changes together. Example commit message:

"db: add query_log table; update job logging (workconnect_ph.sql, includes/Database.php)"

7) If you maintain migrations elsewhere, add a migration entry or README note describing the change.

### PR body template (copy into GitHub PR)
Title: docs: expand Copilot instructions (examples + DB checklist)

Body:
- Added extended Copilot instructions with a concrete example for adding API actions.
- Added a safe DB change checklist for local testing and verification.
- Included recommended branch/PR workflow and verification commands.

Testing:
- Ran smoke tests script locally (see `tests/run_smoke.ps1`).

Notes for reviewers:
- Confirm the example matches current patterns in `api/jobs.php` and `includes/functions.php`.
- Check DB checklist for local environment considerations (XAMPP MySQL user/password).

---

If you'd like, I can also:
- Create a PR description file in `.github/` with the text above.
- Run `php -l` (syntax check) across modified PHP files before committing.

