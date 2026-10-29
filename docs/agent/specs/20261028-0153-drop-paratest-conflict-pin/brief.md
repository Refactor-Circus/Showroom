Risk: low

## What changed
Only `composer.json` changed (2 lines of substance). The `pestphp/pest` constraint went from `^5.3.1` to `^5.3.4`, and the `conflict` block that pinned `brianium/paratest` below 7.26.1 was removed, including the now-empty `conflict` key.

## Why
The conflict pin was a temporary workaround for a paratest crash. Pest 5.3.4 requires the fixed paratest `^7.27.4`, so the pin is no longer needed and maintainers get parallel runs without it (ticket #5, following Keystone's PR #8). Decisions: D-001 and D-002 approved the ticket and spec as drafted. D-003 fast-forwarded the sibling Impex checkout, which still declared the old pin, so the local update could resolve and keep its symlinks.

## Look at
- `composer.json:32`: the new `pestphp/pest` `^5.3.4` constraint. It is the only committed guarantee of paratest >=7.27.4, because `composer.lock` is untracked.
- `composer.json:174`: the end of `repositories`, where the `conflict` key is gone and the JSON must stay valid.

## Tested
- AC1, AC2, AC7: the diff shows only `composer.json`, with no `conflict` key and `pest ^5.3.4`.
- AC3: the gate report shows paratest v7.27.4 installed.
- AC4: `composer validate --strict` passes.
- AC5: `composer test` passes (phpstan 0 errors, pint, pest 544/544, 5406 assertions).
- AC6: `composer test:unit` runs `pest --parallel` with no failures or crashes.

## Risks
- `composer.lock` and `vendor/` are untracked. Anyone with an old local lock or a cached `vendor/` must run `composer update`, and CI caches should be keyed on `composer.json`.
- Pest 5.3.4 is now the minimum, so older Pest 5.3.x installs no longer satisfy the constraint.
- Nothing is left open (nits: 0). The gate verdict is PASS.
