Title: docs: expand Copilot instructions (examples + DB checklist)

Description:
- Adds consolidated Copilot instructions in `.github/copilot-instructions.md` with a concrete example for adding API actions and a safe DB-change checklist.
- Adds a small `tools/run_php_lint.ps1` helper for local Windows/XAMPP PHP syntax checks.
- Fixes `includes/auth.php` so its internal API handler only runs when executed directly (prevents interference with `api/auth.php`).

Testing performed:
- Ran project smoke tests (`.\tests\run_smoke.ps1`) — jobs endpoints succeeded; auth endpoints returned expected error responses for the tested cases.
- Ran PHP syntax checks across the repo (php -l) using `tools/run_php_lint.ps1` — no syntax errors found.

Notes for reviewers:
- Review the `includes/auth.php` change: we now check `SCRIPT_FILENAME` to decide direct execution. This prevents the include from running its own endpoint logic when required by `api/auth.php`.
- The smoke tests still expect a local XAMPP environment and DB; CI smoke-run is not included by default because it requires services.

If approved, push the branch and open a PR using this body.
