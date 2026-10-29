# Drop the paratest conflict pin

Ticket: [#5](https://github.com/Refactor-Circus/Showroom/issues/5)

## Goal

Remove the temporary `brianium/paratest` conflict pin from `composer.json` and require Pest `^5.3.4`, which itself needs the fixed paratest `^7.27.4`. Maintainers get parallel test runs without a manual pin.

## Acceptance criteria

1. `composer.json` has no `brianium/paratest` entry under `conflict`, and no empty `conflict` key remains.
2. `composer.json` requires `pestphp/pest` at `^5.3.4`.
3. After the update, `composer show brianium/paratest` reports a version `>=7.27.4 <8`. This is a local check: `composer.lock` and `vendor/` are untracked (`.gitignore`), so the lock is not part of the diff, and `vendor/composer/installed.json` is not a criterion.
4. `composer validate --strict` passes.
5. `composer test` passes. It runs `composer analyse`, `composer lint:check`, `composer test:types` and `composer test:unit`, so lint and static analysis are covered.
6. `vendor/bin/pest --parallel` (what `composer test:unit` runs) starts and finishes without a paratest crash, reports zero failures, errors or risky tests, and the same test count as a serial `vendor/bin/pest` run.
7. `git diff --name-only` lists exactly `composer.json` (ignoring the untracked `docs/agent/specs/` folder). `composer.lock` is untracked and ignored, so it never appears.

## Design

- Edit the `pestphp/pest` constraint (`composer.json:32`) and delete the `conflict` entry (`composer.json:176-177`).
- Run `composer update pestphp/pest brianium/paratest --with-dependencies` with `COMPOSER` unset, so it uses `composer.json` and `composer.lock`, not `composer.local.json`. Widen to `--with-all-dependencies` only if resolution fails. Record `composer show -l` before and after, and confirm only Pest-related packages changed (not the dev-main siblings, `laravel/framework` or testbench). Keep `pestphp/pest-plugin-laravel` and `pestphp/pest-plugin-type-coverage` at their constraints unless they fail to resolve.
- Before AC5 and AC6, confirm `vendor/composer/installed.json` has no path-repository symlinks for the sibling packages. If the workspace was using symlinks, rerun `composer local:install` afterwards to restore them. This touches no tracked file.
- `composer.lock` is untracked, so the `pest ^5.3.4` constraint is the only committed guarantee of paratest `>=7.27.4`. Anyone with an old local lock or a cached `vendor/` must run `composer update`; CI caches should be keyed on `composer.json` so they do not keep the old paratest.
- Follows Keystone's PR #8. Departs from no standard (`architecture/dependencies`: no new `require` entries).

## Out of scope

Any change other than the composer constraints and the resulting dependency update.

## Risks and open questions

- Pest plugins may need a constraint bump if they don't resolve with Pest 5.3.4; bump only what resolution forces.
- Local path repositories (`composer local:install` uses `composer.local.json` and `composer.local.lock`) may affect the update; run it against `composer.json` with `COMPOSER` unset, as described in Design.
