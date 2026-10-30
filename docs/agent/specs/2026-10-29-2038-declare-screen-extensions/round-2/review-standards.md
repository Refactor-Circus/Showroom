VERDICT: PASS

# Standards review, round 2

The round 1 rework changed tests, Pest.php and generated files only. No standard is broken, so there are no findings at or above minor.

- arch-tests: the Atrium rule in tests/ArchTest.php now puts ->ignoring() after ->not->toUse(), which is the correct order.
- test-layers: the Impex plugin is disabled in tests/TestCase.php, and the WithoutAtrium suite tells preloaded harness classes from Showroom-loaded ones. Both are test-only changes that keep the layers.
- atrium-screens: the worktree copy of the standard no longer mentions the audit trail, and the views contain none.
- Generated files (AGENTS.md) were synced.
