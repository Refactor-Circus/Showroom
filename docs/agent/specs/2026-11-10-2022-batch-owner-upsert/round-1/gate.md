VERDICT: PASS

Gate:
- composer test: passed (phpstan 0 errors, pint passed, pest 1370 passed, arch/coverage 6 passed)
- composer lint:check: passed
- composer exec ringside check: passed

Criteria map (tests/Feature/...):
1. UpsertOwnersActionTest "modes and matching"
2. "validation", "refuses to change the code or type of a matched owner"
2a. "duplicates (D-003)"
3. "parents within the batch"
4. "placement"
5. "unchanged records", "fields left out (D-002)"
6. "slugs"
7. "the assign ability"; UpsertOwnersParityTest "refuses the same flag changes"
8. "events"
9. "the index"
10. "database errors"
11. UpsertOwnersParityTest "leaves the same rows and fires the same owner events as the single actions"; OwnerWriterTest
12. UpsertOwnersPerformanceTest (unchanged, created, updated)
12a. "path safety"
12b. "size"
13. Impex/UpsertOwnersFlowTest
14. arch rule, passed in the gate
