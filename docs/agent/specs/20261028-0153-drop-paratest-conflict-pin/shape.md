# Drop the paratest conflict pin — Shaping Notes

## Scope

Ticket: [#5](https://github.com/Refactor-Circus/Showroom/issues/5), part of campaign #1.

- Remove `"conflict": {"brianium/paratest": ">=7.26.1"}` from `composer.json` (`composer.json:176-177`); drop the `conflict` key too if it ends up empty.
- Raise `pestphp/pest` from `^5.3.1` to `^5.3.4` (`composer.json:32`), whose paratest requirement is `^7.27.4`.
- Run `composer update pestphp/pest brianium/paratest` (with their dependencies) so the installed paratest resolves to `^7.27.4`.
- `composer test` and a `--parallel` Pest run pass.

Out of scope: any change other than the composer constraints and the resulting dependency update.

## Decisions

- D-001: ticket created as drafted (answered in RingMaster).
- No design questions: the work is a dependency constraint change with no behaviour change.

## Context

- Keystone did the same in its PR #8; follow it.
- No product docs involved, no UI surface, no visuals.
- Check that the other pest plugins (`pest-plugin-laravel ^5.0.1`, `pest-plugin-type-coverage ^5.0.3`) still resolve with Pest 5.3.4.

## Standards

- `architecture/dependencies`: the change touches Composer constraints; keep optional integrations out of `require`.
