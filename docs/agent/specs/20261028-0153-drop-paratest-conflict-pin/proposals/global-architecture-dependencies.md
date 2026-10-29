---
key: architecture/dependencies
target: global
kind: architectural
action: amend
status: pending
from_spec: 20261028-0153-drop-paratest-conflict-pin
evidence:
  - "build.md first Group 1, 'Dependency update (1.3): blocked': refactor-circus/impex dev-main conflicts with brianium/paratest v7.27.4"
  - "decisions.md D-003 (fast-forward the Impex sibling, whose origin/main already drops the pin)"
  - "post-mortem.md Root causes: standards_gap"
  - "vendor/refactor-circus/standards/standards/architecture/dependencies.md (no section on shared constraints or rollout order)"
---

Rationale: no rule says how a pin or unpin shared across packages is sequenced. Showroom's unpin could not resolve while the Impex sibling checkout still declared `conflict brianium/paratest >=7.26.1`, which cost a needs-decision stop and a manual fast-forward (D-003). Amends the existing dependencies standard rather than adding a near-duplicate.

Proposed new section (the rest of the standard is unchanged):

## Constraints shared across packages

- A `conflict` pin, unpin or minimum bump that several suite packages carry is rolled out in dependency order: the base package first, then each package others require, and dependents last. A dependent is changed only after every sibling it requires has merged the same change to `main`.
- A dev-main sibling resolves with its own `composer.json`, so a sibling that still has the pin blocks every package that depends on it. Don't work around it in the dependent (no `--ignore-platform-reqs`, no extra override, no edit to the sibling from the dependent's job).
- A spec for such a change lists the siblings that must already carry it and the PRs that did it, and the build checks that they do before it updates anything.
- A temporary pin carries a comment-free, single-purpose entry under `conflict` and is removed with its own ticket once upstream is fixed, leaving no empty `conflict` key.

```text
Keystone PR #8  (drop paratest pin)  -> merged
Impex           (drop paratest pin)  -> merged, sibling checkouts current
Showroom #5     (drop paratest pin)  -> now resolves
```

Reference-implementation addition: "The `brianium/paratest` pin was dropped in this order: Keystone (PR #8), Impex, then Showroom (#5)."
