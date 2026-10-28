# Drop the paratest conflict pin

## Problem

`composer.json` carries a temporary `"conflict": {"brianium/paratest": ">=7.26.1"}` entry that keeps Composer away from a broken paratest release. Pest 5.3.4 requires paratest ^7.27.4, which fixes it, so the pin is no longer needed.

## Desired outcome

- The `conflict` entry is removed from `composer.json` (the `conflict` key too, if it is then empty).
- `pestphp/pest` is required at `^5.3.4`.
- `composer update` for pest and paratest has run, and the lock/installed versions resolve paratest ^7.27.4.
- The full test suite passes, including `--parallel`.

Part of campaign #1, "Drop the paratest conflict pin". Keystone did the same in its PR #8.

## Acceptance hints

- `composer.json` has no `brianium/paratest` conflict entry.
- `composer test` and a `--parallel` pest run both pass.

## Out of scope

Any change other than the composer.json constraints and the resulting dependency update.
