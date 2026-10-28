# Build: Drop the paratest conflict pin

## Group 1

Status: partial. Tasks 1.1 and 1.2 are done (commit 60e2702 on `ring-master/20261028-0153-drop-paratest-conflict-pin`). Tasks 1.3 to 1.6 are blocked on a decision about the sibling Impex checkout (see below).

### Files

- `composer.json`: `pestphp/pest` is now `^5.3.4`, and the `conflict` key, which held only `brianium/paratest: >=7.26.1`, is gone. `git diff --name-only main` lists only `composer.json`.

### Baseline (1.1)

- The worktree has no `composer.lock`. `vendor/` was installed from `composer.local.lock` by `composer local:install`.
- `vendor/composer/installed.json` holds path repositories for all eight siblings, and `vendor/refactor-circus/*` are symlinks to `../Atrium`, `../Cortex`, `../Impex`, `../Keen`, `../Keystone`, `../PennantPlus`, `../Ringside` and `../Standards`.
- `composer show -l` (COMPOSER unset) shows `pestphp/pest` at 5.3.1 (latest 5.3.4) and `brianium/paratest` at 7.26.0 (latest 7.27.4). Everything else is as installed by the local lock: laravel/framework 13.38.0, orchestra/testbench 11.3.0, phpunit 13.4.1, and dev-main siblings at their local commits.
- Test count: `vendor/bin/pest --list-tests` lists 544 tests. Per the job prompt, the suite was not run.

### Dependency update (1.3): blocked

- With COMPOSER unset, `composer update pestphp/pest brianium/paratest --with-dependencies` fails: "Cannot update only a partial set of packages without a lock file present". The worktree has no `composer.lock`, and a full `composer update` would replace the sibling symlinks and move every package.
- Local mode (`composer run-script local:update -- pestphp/pest brianium/paratest --with-dependencies`, which regenerates `composer.local.json` and updates against `composer.local.lock`) fails: `refactor-circus/impex dev-main conflicts with brianium/paratest v7.27.4`. The sibling checkout `../Impex` is on `main` but two commits behind `origin/main`, so its `composer.json` still has `"conflict": {"brianium/paratest": ">=7.26.1"}`. GitHub's Impex `main` has already dropped the pin and requires `pestphp/pest ^5.3.4`. `--with-all-dependencies` cannot help, because the path repository serves the checkout as it is.
- No vendor package changed: both update attempts failed during resolution. `composer.local.json` (ignored) was regenerated from the edited `composer.json`.

### Tests

- No test files were added. AC7 requires the diff to be exactly `composer.json`, and the change is a dependency constraint, so the acceptance criteria are proved by composer commands and the existing suite, not by new tests.
- `composer validate --strict --no-check-lock` passes ("./composer.json is valid").

### Deviations

- No tests were written first: a test file would break AC7.
- The baseline test count comes from `--list-tests`, not a serial run, because the job prompt says not to run the suite.

## Group 1

Status: done. This run picks up at task 1.3, after D-003 and the rework notes: the Impex sibling is now current with `origin/main`, and Ringside #9's `worktrees --setup` refreshed the stale `composer.local.lock`. No sibling `composer.json` declares a `brianium/paratest` conflict anymore.

### Files

- `composer.json` only. It was already committed in 60e2702, so this run made no new commit. `git diff --name-only main...HEAD` lists exactly `composer.json`, and the working tree is clean. A plain `git diff --name-only main` also shows `.github/workflows/tests.yml`, but that comes from local `main` moving ahead (dependabot merge 6abe547), not from this branch.

### Dependency update (1.3, 1.4)

- Following D-003, `composer run-script local:update -- pestphp/pest brianium/paratest --with-dependencies` resolved cleanly: "Nothing to modify in lock file", "Nothing to install, update or remove". The setup refresh had already brought the local lock to the target versions.
- `composer show brianium/paratest` reports v7.27.4 (AC3: `>=7.27.4 <8`). `pestphp/pest` is v5.3.4, and `phpunit/phpunit` is 13.4.5, which Pest 5.3.4 needs (`^13.4.5`).
- `pestphp/pest-plugin-laravel` 5.0.1 and `pestphp/pest-plugin-type-coverage` 5.0.3 stay at their constraints. `orchestra/testbench` stays at 11.3.0, and the dev-main siblings stay at their local checkouts.
- The sibling symlinks are intact: `vendor/refactor-circus/*` still link to `../Atrium`, `../Cortex`, `../Impex`, `../Keen`, `../Keystone`, `../PennantPlus`, `../Ringside` and `../Standards`. Local mode kept them, so no `composer local:install` rerun was needed (1.4).

### Tests (1.5, 1.6)

- `composer validate --strict --no-check-lock`: "./composer.json is valid" (AC4). The lock check is skipped because the worktree has no `composer.lock`, only the ignored `composer.local.lock`.
- `vendor/bin/pest --parallel` and `composer test` (AC5, AC6) are left to the gate, as the job prompt says. The baseline count to match is 544 tests, from `--list-tests` in group 1's first run.
- No test files were added, because AC7 limits the diff to `composer.json`.

### Deviations

- `laravel/framework` is 13.39.0, up from 13.38.0 at the baseline. The update in this run changed nothing, so the bump came from the `worktrees --setup` lock refresh before the run. It affects only the untracked local lock, not the diff.

## Group 1

Status: done (retry of tasks 1.3 to 1.6). The Impex sibling is on `main`, level with `origin/main`. This run re-checked the earlier result and found no changes to make.

### Files

- `composer.json` only, still from commit 60e2702: `pestphp/pest ^5.3.4`, and there is no `conflict` key and no `brianium/paratest` entry (AC1, AC2). `git diff --name-only 330090a HEAD` lists exactly `composer.json` (AC7). The working tree is clean, so this run has no new commit.

### Dependency update (1.3, 1.4)

- Following D-003, `composer run-script local:update -- pestphp/pest brianium/paratest --with-dependencies` reported "Nothing to modify in lock file" and "Nothing to install, update or remove". It found no security advisories.
- `composer show brianium/paratest` reports v7.27.4 (AC3). `pestphp/pest` is v5.3.4.
- `vendor/refactor-circus/*` still symlink to the eight sibling checkouts, so `composer local:install` did not need to run again (1.4).

### Tests (1.5, 1.6)

- `composer validate --strict --no-check-lock` printed "./composer.json is valid" (AC4).
- As the job prompt says, `vendor/bin/pest --parallel` and `composer test` (AC5, AC6) are left to the gate. The baseline count to match is 544 tests.
- No test files were added, because AC7 limits the diff to `composer.json`.

### Deviations

- None in this run.

## Group 2

Status: done (R1.1, rebuild from task 1.3 with a fresh worktree lock). Worktree `Showroom--20261028-0153-drop-paratest-conflict-pin--g2`, branch `ring-master/20261028-0153-drop-paratest-conflict-pin--g2`.

### Files

- `composer.json` only, from commit 60e2702 inherited from group 1: `pestphp/pest ^5.3.4`, no `conflict` key, no `brianium/paratest` entry (AC1, AC2). `git diff --name-only 330090a HEAD` lists exactly `composer.json` (AC7). The update changed no tracked file and the working tree is clean, so this run has no new commit.

### Dependency update (1.3, 1.4)

- Following D-003, `composer --working-dir=<worktree> run-script local:update -- pestphp/pest brianium/paratest --with-dependencies` resolved without `-W`. This worktree's `composer.local.lock` was still at the old versions, so it updated: `brianium/paratest` v7.26.0 => v7.27.4, `pestphp/pest` v5.3.1 => v5.3.4, `phpunit/phpunit` 13.4.1 => 13.4.5, `phpstan/phpdoc-parser` 2.3.6 => 2.3.9 (all Pest dependencies). The seven `refactor-circus/*` dev-main siblings only refreshed their recorded commit to the current symlinked checkouts ("Source already present").
- `laravel/framework` stays at v13.38.0 and `orchestra/testbench` at v11.3.0. `pestphp/pest-plugin-laravel` stays at v5.0.1 and `pestphp/pest-plugin-type-coverage` at v5.0.3, so no constraint bumps were needed.
- `composer show brianium/paratest` reports v7.27.4 (AC3).
- `vendor/refactor-circus/*` still symlink to the eight sibling checkouts, so `composer local:install` did not need to run again (1.4).

### Tests (1.5, 1.6)

- `composer validate --strict --no-check-lock` printed "./composer.json is valid" (AC4). The lock check is skipped because the worktree has only the ignored `composer.local.lock`.
- As the job prompt says, `vendor/bin/pest --parallel` and `composer test` (AC5, AC6) are left to the gate. The baseline count to match is 544 tests.
- No test files were added, because AC7 limits the diff to `composer.json`.

### Deviations

- None to the spec or standards. A first `local:update` attempt ran in the main checkout by mistake (the shell `cd` did not persist). It failed during resolution against main's `^5.3.1` and pin, so nothing was installed there; only main's ignored `composer.local.json` was regenerated from its own `composer.json`, and `git status` on main shows no tracked change.

## Group 3

Status: done (R2.1: retry from task 1.3 with current sibling checkouts). Worktree `Showroom--20261028-0153-drop-paratest-conflict-pin--g3`, branch `ring-master/20261028-0153-drop-paratest-conflict-pin--g3`.

### Files

- `composer.json` only, from commit 60e2702: `pestphp/pest ^5.3.4`, with no `conflict` key and no `brianium/paratest` entry (AC1, AC2). `git diff --name-only 330090a HEAD` lists exactly `composer.json` (AC7). The working tree stayed clean after the update (`composer.local.json`, `composer.local.lock` and `vendor/` are ignored), so this run made no new commit.

### Dependency update (1.3, 1.4)

- The siblings are current: `../Impex` is level with `origin/main`, and no sibling `composer.json` in the Refactor Circus workspace declares a `brianium/paratest` conflict anymore.
- Following D-003, `composer run-script local:update -- pestphp/pest brianium/paratest --with-dependencies` resolved and installed 11 updates:
  - Pest-related: `brianium/paratest` v7.26.0 => v7.27.4, `pestphp/pest` v5.3.1 => v5.3.4, `phpunit/phpunit` 13.4.1 => 13.4.5 (Pest 5.3.4 requires `^13.4.5`) and `phpstan/phpdoc-parser` 2.3.6 => 2.3.9.
  - Path-repository siblings (atrium, cortex, impex, keen, keystone, pennantplus, ringside) moved to the commits checked out next door. The path repository serves each checkout as it is, so the local lock records whatever those checkouts are on. These were "Source already present" symlink updates, not downloads.
  - `laravel/framework`, `orchestra/testbench`, `pestphp/pest-plugin-laravel` and `pestphp/pest-plugin-type-coverage` did not change.
- `composer show brianium/paratest` reports v7.27.4 (AC3: `>=7.27.4 <8`). `composer show pestphp/pest` reports v5.3.4.
- `vendor/refactor-circus/*` still symlink to `../Atrium`, `../Cortex`, `../Impex`, `../Keen`, `../Keystone`, `../PennantPlus`, `../Ringside` and `../Standards`. Local mode kept them, so `composer local:install` did not need to run again (1.4).

### Tests (1.5, 1.6)

- `composer validate --strict --no-check-lock` printed "./composer.json is valid" (AC4). The lock check is skipped because the worktree has only the ignored `composer.local.lock`.
- As the job prompt says, `vendor/bin/pest --parallel` and `composer test` (AC5, AC6) are left to the gate. The baseline count to match is 544 tests (`--list-tests`, group 1).
- No test files were added, because AC7 limits the diff to `composer.json`.

### Deviations

- The dev-main siblings moved in the local lock. Design says to confirm they don't change, but they are path repositories that follow the sibling checkouts, which the user brought up to date for this retry. Only the ignored local lock records this, not the diff.

## Group 3

Status: done (R2.1 retry, re-checked from task 1.3). This run found the earlier group 3 result still in place and had nothing to change.

### Files

- `composer.json` only, from commit 60e2702: `pestphp/pest ^5.3.4`, with no `conflict` key and no `brianium/paratest` entry (AC1, AC2). `git diff --name-only 330090a HEAD` lists exactly `composer.json` (AC7). The working tree is clean before and after the update, so this run made no new commit.

### Dependency update (1.3, 1.4)

- `../Impex` is on `main`, level with `origin/main`. No sibling dependency's `composer.json` declares a `brianium/paratest` conflict. The only workspace match is the main checkout's own `composer.json`, which the merge of this branch replaces.
- Following D-003, `composer run-script local:update -- pestphp/pest brianium/paratest --with-dependencies` printed "Nothing to modify in lock file" and "Nothing to install, update or remove". It found no security advisories.
- `composer show brianium/paratest` reports v7.27.4 (AC3: `>=7.27.4 <8`). `pestphp/pest` is v5.3.4, and its `phpunit/phpunit ^13.4.5` requirement is met.
- `vendor/refactor-circus/*` still symlink to the eight sibling checkouts, so `composer local:install` did not need to run again (1.4).

### Tests (1.5, 1.6)

- `composer validate --strict --no-check-lock` printed "./composer.json is valid" (AC4).
- As the job prompt says, `vendor/bin/pest --parallel` and `composer test` (AC5, AC6) are left to the gate. The baseline count to match is 544 tests.
- No test files were added, because AC7 limits the diff to `composer.json`.

### Deviations

- None in this run.

## Group 2

Status: done (retry of R1.1). The earlier group 2 run had already finished the work, and this run re-checked it and found nothing to change.

### Files

- `composer.json` only, from commit 60e2702: `pestphp/pest ^5.3.4`, no `conflict` key, no `brianium/paratest` entry (AC1, AC2). `git diff --name-only 330090a HEAD` lists exactly `composer.json` (AC7). The working tree is clean, so this run has no new commit.

### Dependency update (1.3, 1.4)

- Following D-003, `composer --working-dir=<g2 worktree> run-script local:update -- pestphp/pest brianium/paratest --with-dependencies` reported "Nothing to modify in lock file" and "Nothing to install, update or remove". It found no security advisories.
- `composer show brianium/paratest` reports v7.27.4 (AC3).
- `vendor/refactor-circus/*` still symlink to the eight sibling checkouts, so `composer local:install` did not need to run again (1.4).

### Tests (1.5, 1.6)

- `composer validate --strict --no-check-lock` printed "./composer.json is valid" (AC4).
- As the job prompt says, `vendor/bin/pest --parallel` and `composer test` (AC5, AC6) are left to the gate. The baseline count to match is 544 tests.
- No test files were added, because AC7 limits the diff to `composer.json`.

### Deviations

- None in this run.
