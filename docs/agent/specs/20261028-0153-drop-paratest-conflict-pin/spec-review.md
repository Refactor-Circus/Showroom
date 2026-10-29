# Spec review

## Round 1

Changes to `spec.md` (open questions untouched; there were none):

- AC3: replaced the `composer.lock` / `installed.json` check with `composer show brianium/paratest` reporting `>=7.27.4 <8`, marked as a local check. Why: `.gitignore` lists `composer.lock`, so neither lock nor vendor is in the diff. One finding said the lock exists in the repo; it may exist on disk, but it is ignored and untracked, so the spec treats it as untracked.
- AC4: now `composer validate --strict`.
- AC5: states that `composer test` runs analyse, lint:check, test:types and test:unit.
- AC6: requires no paratest crash, zero failures/errors/risky, and the same test count as a serial run. `composer test:unit` already runs `pest --parallel`.
- AC7: now `git diff --name-only` equals exactly `composer.json`, ignoring the untracked specs folder. The "if tracked" hedge is gone.
- Design: the update uses `--with-dependencies` (widen to `--with-all-dependencies` only on resolution failure), runs with `COMPOSER` unset against `composer.json`, records `composer show -l` before and after, confirms no path-repo symlinks before AC5 and AC6, and reruns `composer local:install` afterwards if symlinks were in use. Added a note that the lock is untracked, so the pest constraint is the only committed guarantee, and CI caches should be keyed on `composer.json`.
- Risks: local-path bullet now points to the `COMPOSER`-unset procedure.

Not changed: Goal, AC1, AC2 and Out of scope.
