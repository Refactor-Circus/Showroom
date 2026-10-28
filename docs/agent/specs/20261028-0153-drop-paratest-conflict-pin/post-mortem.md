# Post-mortem: Drop the paratest conflict pin

## Summary

Spec 20261028-0153 removed the `brianium/paratest` conflict pin from `composer.json` and raised `pestphp/pest` to `^5.3.4` (ticket #5, PR #6, merged). The code change was two lines and was committed in 60e2702 during the first run. Four factory runs followed, and three of them were spent on environment problems, not on the change. The first stopped with `needs-decision`, the next two `escalated`, and the fourth was approved after 1 review round with a green gate and no findings. The cause was a stale sibling checkout (`../Impex`, two commits behind `origin/main`, still declaring `conflict brianium/paratest >=7.26.1`) plus stale `composer.local.lock` files in the worktrees.

## Metrics

- Runs: 4 (`needs-decision`, `escalated`, `escalated`, `approved`).
- Review rounds: 1. Red gate rounds: 0. Rework: 2.
- Decisions: 3. D-001 (ticket), D-002 (spec approval) and D-003 (fast-forward the Impex sibling).
- Escalations: 2 (`wf_11c1acb9-85e`, `wf_dcfe019b-9e3`), plus one `needs-decision` stop.
- Findings: none, and no nits.
- Deviations: 3, all small.
  - A baseline count from `--list-tests` instead of a serial run.
  - The dev-main siblings moved in the local lock.
  - `laravel/framework` went from 13.38.0 to 13.39.0 in the local lock.
- Build.md has 6 "Group" sections for a one-task change (groups 1, 1, 1, 2, 3, 3 and a repeated 2), because each retry appended a section.
- Gate: `composer test` passed (phpstan 0 errors, pint, pest 544/544, 5406 assertions). `lint:check`, `ringside check` and `composer validate --strict` passed. Paratest was v7.27.4.

## What went well

- The spec review (spec-review.md round 1) corrected AC3 (the lock and `installed.json` are untracked), AC7 (the diff is exactly `composer.json`) and the update procedure (`COMPOSER` unset, record `composer show -l`). The final build followed it.
- The builder correctly blocked on the Impex conflict instead of editing a sibling or forcing the resolution. It diagnosed the cause precisely (build.md, first Group 1 section).
- The builder added no test files, which kept AC7 true.
- The gate (round-1/gate.md) checked all 7 criteria, including the serial/parallel test counts via the 544/544 result.
- The change stayed confined to `composer.json`.

## What cost rounds

- Run 1 (`needs-decision`): tasks 1.3 to 1.6 were blocked. A plain `composer update` fails with no `composer.lock`. Local mode failed with `refactor-circus/impex dev-main conflicts with brianium/paratest v7.27.4`, because `../Impex` was behind `origin/main`.
- Runs 2 and 3 (`escalated`): after D-003 and the Impex fast-forward, the worktree locks were still stale. The Group 2 and Group 3 retries in build.md show lock refreshes and re-checks. The final run succeeded after Ringside #9's `worktrees --setup` refreshed the local lock and a note said the siblings were current.
- Several build retries only re-verified work that was already done ("Nothing to modify in lock file").
- One slip: a `local:update` ran in the main checkout because the shell `cd` did not persist (Group 2). It failed in resolution and changed no tracked file.

## Root causes

- **spec_gap**: The spec assumed `composer update ... --with-dependencies` would work with `COMPOSER` unset against `composer.json` and `composer.lock`. Its own AC3 notes the lock is untracked, so no lock exists in a fresh worktree and the command cannot run. The spec also did not say that path-repository siblings must be current, or that Impex's own pin could block resolution. Evidence: spec.md Design and Risks; build.md first Group 1, "Dependency update (1.3): blocked".
- **tooling**: Worktree setup did not guarantee fresh sibling checkouts and a fresh `composer.local.lock`. Ringside #9 (`worktrees --setup`) later fixed the lock refresh, and the sibling checkouts were only made current after a human acted (D-003). Evidence: build.md Group 1 (second), Group 2, Group 3.
- **tooling**: Build retries started in a way that could repeat the same checks without a pre-flight to detect "already done", and the shell cwd did not persist between commands. Evidence: build.md Group 2 deviations.
- **standards_gap**: The cross-package pin pattern (Keystone PR #8, Impex) has no documented sequencing rule. A dev-main sibling carrying the same conflict pin blocks every dependent package until it is merged and checked out. Evidence: build.md, the "Impex dev-main conflicts" message.
- **implementer_error**: None substantive. The shell-cwd slip was harmless.
- **reviewer_noise**: None. There were no findings.

## What human review caught

Nothing. No human PR review comments were recorded for PR #6. The only human input was the three decisions: D-001 and D-002 were approvals, and D-003 unblocked the build by fast-forwarding the Impex sibling. That last one was an environment fix the factory could not do itself.

## Proposed follow-ups

- Make worktree setup (`ringside worktrees --setup`) fast-forward or warn about sibling checkouts behind `origin/main`, and refresh `composer.local.lock`, before the build starts.
- Add a spec-writing check for dependency-only jobs. If `composer.lock` is untracked, the update procedure must use local mode, and the spec must list the siblings that need to be current.
- Add a standards note on rolling out a dependency pin or unpin across packages: merge the sibling packages first, then the dependents.
- Have the build stage detect "already done" on retry (the diff matches the spec and the lock is at target) and return immediately, and make it avoid appending a new Group section for pure re-checks.
- Make the build tooling use absolute `--working-dir` paths so commands cannot hit the main checkout.

## Pending proposals

These are awaiting the user's accept, edit or reject. The post-mortem ran unattended, so no one was there to answer.

- `architecture/dependencies` (global, amend): add a "Constraints shared across packages" rollout-order rule. Text: `proposals/global-architecture-dependencies.md`.
- `showroom/dependency-updates` (project, add, tooling): do dependency updates in local mode from the worktree, with siblings current and an absolute `--working-dir`. Text: `proposals/project-showroom-dependency-updates.md`.
