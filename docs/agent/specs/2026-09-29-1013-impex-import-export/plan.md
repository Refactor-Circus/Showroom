# Import / Export via Impex — Plan

Roadmap Milestone 7. Decisions in `shape.md`.

## Task 1: Save spec documentation

`shape.md` and this plan.

## Task 2: Integration

- `src/Impex/ImpexIntegration.php` — `active()` (config `showroom.impex.enabled` + Impex loaded), `register()` adds `flows()` to Impex's `FlowRegistry` once resolved, `start()` runs a flow tagged `showroom`.
- `src/Exceptions/ImpexMissingException.php` — 501 when Impex is absent or off.
- Config `showroom.impex`: `enabled`, `chunk`, `allow_failures` (default 1.0), `tries`, `export_page_size`, `export_path`, `feeds`.

## Task 3: Row formats

- `src/Impex/ProductRows.php` — CSV row ⇄ API record (Akeneo columns).
- `src/Impex/ExportRecord.php` — working copy or published snapshot → record, filtered by `ValueFilter`.

## Task 4: Import flows

- `Sources/FileProductSource` (streamed CSV/JSONL, byte-offset cursor and keys), `Sources/InlineProductSource`.
- `Actions/ImportProductRow` — create/update/upsert through the product Actions.
- `Flows/ImportProductsFlow`, `Flows/UpsertProductsFlow` — `batch()` with chunk, failure tolerance and tries from config.

## Task 5: Export and feed flows

- `Actions/ExportProductPages` (`ResumableAction`, JSONL part files), `Actions/JoinProductExport` (join into one JSONL/CSV asset, idempotent), `Actions/DeliverFeed` (`Impex::http()` POST).
- `Flows/ExportProductsFlow`, `Flows/FeedFlow` (`showroom:feed:{name}`; feed config and query captured in a side effect).

## Task 6: Surfaces

- `StartImportAction` (`file` | `url` | `asset`, `format`, `mode`) and `StartExportAction` (search filters, `format`, `code`, `published`) with action events.
- HTTP `POST /showroom/imports`, `POST /showroom/exports` → Impex `RunResource`, 202.
- MCP `start-import-tool`, `start-export-tool`.
- Atrium **Import & export** page: upload/import, export form, recent `showroom:*` runs with results.

## Task 7: Tests

`tests/Feature/Impex/ImportExportTest.php` (flows, CSV/JSONL, modes, URL import, upsert connector, exports and round trip, published export, feed + ledger, MCP, Impex off, failure tolerance), `tests/Feature/Ui/TransferPagesTest.php`, event/MCP/Cortex counts.

## Task 8: Docs

`docs/12-impex.md`, cross-references in API, MCP, configuration, installation and index docs; README section and roadmap; CHANGELOG; Boost skill.

## Verification

- `composer test`
- Workbench: **Catalog → Import & export**, import a JSONL file, start an export, see both runs.
