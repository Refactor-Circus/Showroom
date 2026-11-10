# Factory decision

- Status: escalated
- Stage: build
- Run: wf_072d54ac-f04 (2026-11-10)

## Reasons

- A build agent failed. Group 7 ("Flag assignment, effective flags and search") and its salvage pass both ran out of agent turns (max_turns 80) without returning a result.

## State

- Groups 1–6 are built and committed (last commit 4eeb624). The wave 1 and wave 2 merges passed `composer test:unit`.
- Group 7 left uncommitted work in the job worktree:
  - `Flag/Services/Flags.php` and `Flag/Concerns`
  - changes to the product and owner actions, Data, models and resources
  - changes to `DatabaseEngine`, `ScoutEngine` and `ProductDocument`
  - group 7 tests not written yet
- Known: group 4's switch to lookup by `id` breaks about 19 existing test files that still use identifier or code URLs. They get `404` and will turn the gate red until they're updated.

## Nits

None; review never ran.

## Run wf_8276bd48-4cb: needs-decision (build, group 9)

Groups 13, 14 and 9 were built. Group 9 asks:

- Q-011: how to turn capture off on the CLI, given Impex's bool flag handling
- Q-012: Transfer domain exceptions to the AC 32 arch rule
