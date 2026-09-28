# Agent instructions

Gratis AI is a WordPress plugin. Read `README.md` for the current scope and setup, and distinguish implemented behavior from items marked as planned. `gratis-ai.php` is the plugin entry point; follow the existing structure in `autoload.php` and `src/` for implementation changes. `readme.txt` is the WordPress plugin listing.

No test or lint workflow is tracked. For PHP changes, run `php -l` on changed files and report the checks actually performed. Do not claim a WordPress integration check unless one was run.

`.github/workflows/notify-deploy.yml` sends a `plugin-updated` deployment notification on every push to `main`. Merging any PR to `main`, including a documentation-only change, triggers that notification. Do not use the workflow for validation.
