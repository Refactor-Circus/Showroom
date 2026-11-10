VERDICT: PASS

Gate results (run in the job checkout):
- composer test: passed (phpstan 0 errors, pint passed, pest 1268/1268 with 12564 assertions, coverage 100%, second pest suite 6/6).
- composer lint:check: pint passed.
- composer exec ringside check: passed.

Acceptance criteria map (file-level):
- AC 1, 2: tests/Feature/Api/ProductIdentifierTest.php
- AC 3, 4, 7: tests/Unit/SlugsTest.php, tests/Feature/SlugsTest.php
- AC 5, 12: tests/Feature/Api/ProductApiTest.php, tests/Feature/Api/OwnershipApiTest.php, tests/Feature/McpTest.php
- AC 6: tests/Feature/SlugBackfillTest.php
- AC 8-11: tests/Feature/RecordLookupTest.php
- AC 13-16, 42-44: tests/Feature/ExternalIdsTest.php
- AC 17-21, 33: tests/Feature/Api/FlagApiTest.php, tests/Feature/FlagActionsTest.php, tests/Feature/FlagAssignmentTest.php
- AC 22, 34: tests/Feature/Search/FlagSearchTest.php, tests/Feature/Search/ScoutEngineTest.php
- AC 23: tests/Feature/FlagAssignmentTest.php, tests/Feature/Search/FlagSearchTest.php
- AC 24: tests/Feature/Api/OwnerTypeSelfParentTest.php
- AC 25-29, 36, 38, 39: tests/Feature/UpsertProductsActionTest.php, tests/Feature/UpsertProductsParityTest.php, tests/Feature/UpsertProductsPerformanceTest.php, tests/Unit/KeyNormalizerTest.php
- AC 30, 35: tests/Feature/Impex/ProductWebhooksTest.php, tests/Unit/ProductChangeCaptureTest.php
- AC 31: tests/Feature/Impex/UpsertChunkTest.php, tests/Feature/Impex/ImportExportTest.php
- AC 32: tests/ArchTest.php
- AC 37: tests/Feature/PolicyTest.php (with tests/Fixtures/ReadOnlyFlagPolicy.php)
- AC 40, 41: tests/Feature/Ui/FlagPagesTest.php, tests/Feature/Ui/ProductPagesTest.php, tests/Feature/Ui/OwnerPagesTest.php
