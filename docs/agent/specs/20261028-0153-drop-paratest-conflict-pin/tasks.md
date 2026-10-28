# Tasks: Drop the paratest conflict pin

## Group 1: Composer constraints and dependency update
depends on: none
files: composer.json
criteria: 1, 2, 3, 4, 5, 6, 7

- [x] 1.1 Record the baseline: `composer show -l` (COMPOSER unset), the serial `vendor/bin/pest` test count, and whether `vendor/composer/installed.json` holds path-repository symlinks for sibling packages
- [x] 1.2 Edit `composer.json`: set `pestphp/pest` to `^5.3.4` and delete the `brianium/paratest` conflict entry (and the `conflict` key if it ends up empty)
- [x] 1.3 Run `composer update pestphp/pest brianium/paratest --with-dependencies` with `COMPOSER` unset (widen to `--with-all-dependencies` only if resolution fails); compare `composer show -l` to the baseline, confirm only Pest-related packages changed, and check `composer show brianium/paratest` reports `>=7.27.4 <8`
- [x] 1.4 If sibling symlinks were in use before, rerun `composer local:install` to restore them
- [x] 1.5 Run `composer validate --strict`, then `vendor/bin/pest --parallel` and confirm zero failures, errors or risky tests and the same test count as the serial run
- [x] 1.6 Run `composer test`; all green, and `git diff --name-only` lists only `composer.json`

## Group 2: Rework 1: Rebuild from task 1.3 with a fresh worktree lock
depends on: 1
files: composer.json
criteria: 1, 2, 3, 4, 5, 6, 7

- [x] R1.1 Prepare failed because the worktree's composer.local.lock still pinned pest 5.3.1; `worktrees --setup` now updates a stale lock (Ringside #9). Rebuild from task 1.3.

## Group 3: Rework 2: Retry with current sibling checkouts
depends on: 1
files: composer.json
criteria: 1, 2, 3, 4, 5, 6, 7

- [x] R2.1 User note: all sibling checkouts are current now. Retry from task 1.3 (the previous run escalated, likely on stale siblings).
