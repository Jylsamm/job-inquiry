WorkConnect PH - Fixes and Suggested Cleanup

Summary of changes applied:
- Fixed CSRF function name mismatches in API endpoints (replaced old `validate_csrf_token` calls with `validateCsrfToken`).
- Removed duplicated upload API block in `api/profiles.php`.
- Corrected SQL table name references from `users` to `user` in `includes/functions.php` to match the DB schema.
- Added basic smoke test script at `tests/smoke_tests.php` to validate key API endpoints locally.

Archived (moved to `archive/`):
- `assets/js/main.js`  — moved to `archive/main.js` (no references found in project files).
- `assets/js/home.js`  — moved to `archive/home.js` (no references found).
- `assets/js/components/modal.js` — moved to `archive/modal.js` (project provides `modalManager` in `assets/js/config.js`).

Next steps recommended:
1. Run smoke tests locally with XAMPP running:
   php tests/smoke_tests.php

2. Manually review and, if safe, delete the suggested unused assets. Keep backups or use Git branches.

3. Add unit/integration tests for critical API flows (registration/login, password reset, job posting/apply).

4. Consider consolidating modal handling (either keep `Modal` class or `modalManager` in `config.js`) to avoid duplication.

5. Optional: add CI lint step (PHP lint) and a basic test runner.
