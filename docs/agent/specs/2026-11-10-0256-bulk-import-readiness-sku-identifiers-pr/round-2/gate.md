VERDICT: PASS

# Gate, round 2

## Commands

- `composer test`: phpstan passed (0 errors), pint passed, pest 1273/1273 passed (12597 assertions), plus 6/6 in the second suite; coverage 100.0 %.
- `composer lint:check`: pint passed.
- `composer exec ringside check`: "ringside: check passed."

## Acceptance criteria

| AC | Proof |
|----|-------|
| 1 | tests/Feature/Api/ProductIdentifierTest.php "creates products whose identifiers are real SKUs with spaces and slashes", "keeps identifiers with spaces and slashes unique"; tests/Feature/RecordLookupTest.php "fetches a product whose identifier holds a slash and spaces by its id and by its slug" |
| 2 | ProductIdentifierTest "accepts identifiers the default pattern allows", "refuses identifiers the default pattern rejects", "applies a stricter pattern a host configures", "takes a pattern holding a pipe", "fails loud on a pattern that does not compile" |
| 3 | tests/Feature/SlugsTest.php "suffixes a slug another product holds", "gives same-base sources in one call the next suffixes, with no gaps", "counts existing slugs and slugs already given in the call as taken", "cuts a collided 191-character source to fit with its suffix"; tests/Unit/SlugsTest.php |
| 4 | SlugsTest "slugs the default-locale label, then the code, then owner", "keeps slugs unique across all owners, whatever their parent" |
| 5 | SlugsTest "refuses a slug sent on product/owner create", "refuses a full product/owner resource sent back on update, naming the read-only keys", "keeps its slug when its values change", "keeps its slug when its labels or parent change" |
| 6 | tests/Feature/SlugBackfillTest.php (chunked backfill, partial re-run, empty and reserved sources, required and unique) |
| 7 | SlugsTest "falls back to product for a source that slugifies to nothing", "suffixes a reserved route segment", "reserves every literal route segment beside or after a product or owner key"; Unit/SlugsTest "cuts a collided base so the suffixed slug still fits" |
| 8 | RecordLookupTest "lists every route that takes a product or an owner", "binds the id by default; the slug and the identifier or code answer 404", "keeps flags bound by code" |
| 9 | RecordLookupTest "binds the configured key; the others answer 404", "links the dashboard by the configured key"; ProductPagesTest/OwnerPagesTest "links ... by slug when the lookup key is slug" |
| 10 | RecordLookupTest "binds the key a call asks for over HTTP", "answers 422 at by ...", "finds the record by the key a call asks for with by", "refuses a by that is not a lookup key as a validation error" |
| 11 | RecordLookupTest "throws naming the key when a configured lookup key is not id or slug" |
| 12 | SlugsTest "returns the slug in product and owner resources", "returns the slug from the MCP tools", "puts the slug and the owner chain...", "exports the slug, and reads past it on import" |
| 13 | tests/Feature/ExternalIdsTest.php "stores the map ...", "changes only the systems an update sends ...", "accepts/refuses system names and ids ..." |
| 14 | ExternalIdsTest "refuses a (system, id) another product/owner holds, and writes nothing", "keeps the first record of a batch ...", "lets one product and one owner hold the same (system, id)" |
| 15 | ExternalIdsTest "returns the map from the product and owner endpoints and MCP tools", "serialises an empty map as an object" |
| 16 | ExternalIdsTest "lists the products and owners holding an id", "matches values with quotes, backslashes and OR exactly on the database engine", "filters on the normalized value, quoted and escaped", "deletes the external ids of a deleted product or owner" |
| 17 | tests/Feature/FlagActionsTest.php (create/list/show/update/delete, policy registration); tests/Feature/Api/FlagApiTest.php |
| 18 | FlagActionsTest "refuses to delete a flag that is assigned to anything", "refuses to drop a subject type from applies_to while the flag is assigned to it" |
| 19 | tests/Feature/FlagAssignmentTest.php "stores the flags ...", "replaces the own set on update ...", "refuses an unknown flag, or one that does not apply ..., at flags.{n}" |
| 20 | FlagAssignmentTest "gives an owner its own flags plus every ancestor...", "gives a product its own flags plus its owner...", "gives a variant product the flags of its root model...", "never lets a product or owner drop a flag it inherits" |
| 21 | FlagAssignmentTest "returns own and effective flags from the endpoints", "... from the MCP tools" |
| 22 | tests/Feature/Search/FlagSearchTest.php "filters products on their effective flags, every flag asked for", "filters owners on their effective flags", "indexes effective flags normalized, and filters on the normalized code, quoted" |
| 23 | FlagAssignmentTest "queues a sync of every product beneath an owner whose flags change, or that moves", "queues nothing when the flags are sent unchanged"; FlagSearchTest "queues one owner sync on a flag change or a move ..." |
| 24 | tests/Feature/Api/OwnerTypeSelfParentTest.php |
| 25 | tests/Feature/UpsertProductsActionTest.php "creates new records and updates matched ones by identifier", "skips matched records in create mode ...", "matches by an external system..." |
| 26 | UpsertProductsActionTest "reports each failing record by index, identifier and errors, and writes the rest", "reports a record refused by a domain guard, and writes the rest" |
| 27 | UpsertProductsActionTest "writes, records, queues and announces nothing for a record equal to the stored product", "updates a record when one sent field changes"; tests/Feature/UpsertProductsPerformanceTest.php "re-upserts an unchanged batch ..." |
| 28 | UpsertProductsActionTest "records one created version, then one updated version only when something changed", "sends an approved product that changes back to draft ..." |
| 29 | UpsertProductsActionTest "queues one index sync covering every product the batch wrote"; UpsertProductsPerformanceTest "creates a batch of new simple products in a number of queries that does not grow with it" |
| 30 | UpsertProductsActionTest "writes with change capture suppressed only when asked"; tests/Feature/Impex/ProductWebhooksTest.php "records nothing while ProductChangeCapture is suppressed, and records again after" |
| 31 | tests/Feature/Impex/UpsertChunkTest.php (chunked upsert with global row index, unchanged count, `--capture-changes=0` per flow, D-011) |
| 32 | tests/ArchTest.php "code outside src/Impex uses no Impex class" (with the D-012 exceptions) |
| 33 | FlagAssignmentTest "deletes the flag assignments of a deleted product or owner", "ignores assignments whose owner no longer exists when deleting a flag" |
| 34 | FlagSearchTest "lists slug, external ids, flags and owner slugs in the Scout index settings" |
| 35 | tests/Unit/ProductChangeCaptureTest.php (set inside, restored after, restored on throw, scoped per request) |
| 36 | tests/Unit/KeyNormalizerTest.php; ExternalIdsTest "refuses an id/identifier that differs only by case ...", "matches regardless of case and accents"; UpsertProductsActionTest "matches an identifier or an external id that differs only by case", "keeps the first of two records sharing a (system, id) that differs only by case" |
| 37 | FlagActionsTest "registers FlagPolicy ... with an assign ability"; FlagAssignmentTest "answers 403 over HTTP ...", "answers an MCP error ..."; UpsertProductsActionTest "refuses a record whose flags change without assign, and writes the rest"; Product/OwnerPagesTest "hides the own-flags field without assign" |
| 38 | tests/Feature/UpsertProductsParityTest.php "creates through the bulk path exactly what CreateProductAction creates" |
| 39 | UpsertProductsParityTest "persists every good record of a mixed chunk", "falls back to per-record writes when the bulk write fails, with the same result and rows" |
| 40 | tests/Feature/Ui/FlagPagesTest.php "lists, creates, edits and deletes flag definitions", "shows the refusal message when a flag still assigned cannot be deleted" |
| 41 | tests/Feature/Ui/ProductPagesTest.php and OwnerPagesTest.php "shows the slug read-only", "edits the external ids as a map", "offers as own flags only ...", "shows the effective flags read-only, marking the inherited ones", "hides the own-flags field without assign" |
| 42-44 | tests/Feature/ExternalIdsTest.php, tests/Feature/UpsertProductsActionTest.php (source column, constraints, synced_at stamping, read-only external_ids_synced_at); mapped by file |

## Findings

None.
