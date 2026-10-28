---
description: "Dependency updates run in local mode against composer.local.json from the worktree, after checking that sibling checkouts are current; composer.lock is untracked"
tags: [showroom, composer, dependencies, worktrees, tooling]
kind: tooling
key: showroom/dependency-updates
target: project
action: add
status: pending
from_spec: 20261028-0153-drop-paratest-conflict-pin
evidence:
  - "spec.md Design (plain composer update ... --with-dependencies with COMPOSER unset)"
  - "build.md first Group 1 ('Cannot update only a partial set of packages without a lock file present')"
  - "build.md Group 2 Deviations (local:update ran in the main checkout because the shell cd did not persist) and Group 3 Deviations (dev-main siblings moved in the local lock)"
  - "decisions.md D-003 (local:update after fast-forwarding Impex)"
  - "composer.json scripts local:install / local:update; .gitignore (composer.lock, composer.local.json, composer.local.lock, /vendor)"
---

# Dependency Updates

Showroom updates dependencies in local mode (`composer run-script local:update -- <packages> --with-dependencies`) from the job's worktree, never with a plain `composer update`, because `composer.lock` is untracked and a worktree has only `composer.local.lock`.

## Why local mode

- `.gitignore` lists `composer.lock`, `composer.local.json`, `composer.local.lock` and `/vendor`. A fresh worktree has no `composer.lock`, so `composer update <pkg>` fails with "Cannot update only a partial set of packages without a lock file present", and a full `composer update` would replace the sibling symlinks and move every package.
- `local:update` regenerates `composer.local.json` from `composer.json` (`bin/composer-local.php`) and updates `composer.local.lock`, which keeps `vendor/refactor-circus/*` linked to the sibling checkouts. No `composer local:install` rerun is needed afterwards.
- The committed guarantee is the constraint in `composer.json`. Specs never list the lock, `vendor/` or `installed.json` as criteria or as part of the diff.

## Before updating

- Each sibling checkout under `../` is on `main` and level with `origin/main` (`git -C ../<Sibling> status -sb`). Path repositories serve the checkout as it is, so a sibling that is behind resolves with its old constraints. Never edit a sibling from a Showroom job: stop and ask.
- The worktree's `composer.local.lock` is fresh (`ringside worktrees --setup`).
- Every command names the worktree with an absolute `--working-dir`, because the shell's directory does not carry over between commands. It must never reach the main checkout.

## Checking the result

- `composer show <package>` reports the target version; `composer validate --strict --no-check-lock` passes.
- dev-main siblings moving to their checked-out commits in the local lock is expected and is not a deviation; any other package that moved is.

```bash
composer --working-dir=/Users/jay/Projects/packages/Showroom--<slug> \
    run-script local:update -- pestphp/pest brianium/paratest --with-dependencies
composer --working-dir=/Users/jay/Projects/packages/Showroom--<slug> show brianium/paratest
```
