# References

## Roster: organization sync and links

- **Location:** `refactor-circus/roster`
  - `src/Domains/Organization/Actions/SyncOrganizationAction.php`: `execute()`, `preview()`, `links()`
  - `src/Domains/Organization/Models/OrganizationLinkModel.php`
- **Consumer usage:** the `secret` app, `app/Domains/Organization/Actions/SyncCustomersAction.php`.
- **Relevance:** this is the established Refactor Circus pattern for syncing an external system into a package's records, at scale, re-runnably.
- **Patterns to borrow:**
  - `links(array $records)` loads every record's link for a whole page in one query, keyed by `source + "\0" + external_id`.
  - `preview($data, $link)` compares a record with what the link already holds, in memory, and returns `unchanged`, `updated` or `created`. Unchanged records skip the transaction, the audit entries and the events.
  - Unchanged links are stamped in one bulk `update(['synced_at' => now()])` per page.
  - Per-record failures are collected; the page fails after the rest are synced, so Impex retries it and counts it.
- **Differences here:**
  - Roster holds one link per organization; Showroom holds many external ids per subject (one per system).
  - The batch action itself should return per-record outcomes rather than throw for the whole page, and leave the retry policy to the flow.

## Showroom: current Impex import path

- **Location:**
  - `src/Impex/Actions/ImportProductRow.php`: per-row upsert through `CreateProductAction` / `UpdateProductAction`, validating with each action's Data `rules()`
  - `src/Impex/Flows/UpsertProductsFlow.php`: `showroom:upsert-products`, `batch(InlineProductSource)->using(ImportProductRow)`
  - `src/Impex/Sources/InlineProductSource.php`
  - `src/Impex/Flows/ImportProductsFlow.php`
  - `src/Impex/Sources/FileProductSource.php`
  - `src/Impex/ProductRows.php`: CSV/JSONL row mapping
- **Relevance:** this is the path the batch action replaces or backs. It already defines the record shape (the JSONL/API product shape), the modes (`upsert`, `create`, `update`) and the failure reporting.
- **Patterns to borrow:** keep the record shape and the modes, and validate with the same Data `rules()` so behaviour matches the single-record actions.
- **What changes:** one batch action call per chunk instead of one call per row. Lookups, versions, unique-value syncs and index queueing are batched.

## Showroom: write side effects the batch path must keep

- `src/Domains/Product/Actions/CreateProductAction.php` / `UpdateProductAction.php`: transaction, values and associations, `ProductUniqueValues::sync`, `Versions::record`, `ProductIndex::queue`, and Approved → Draft on change.
- `src/Domains/Workflow/Services/Versions.php`: `snapshot()`, `hasChanges()`, `record()`. A write that changes nothing already records nothing.
- `src/Impex/Webhooks/CaptureProductChanges.php`: listens to product writes and records identifiers for the webhook comparison. This is the target of the opt-out flag.
- `src/Domains/Owner/Data/CreateOwnerTypeData.php`: `parent_types.*` uses `Rule::exists`, which refuses the type's own code (scope item 5).
- `src/Domains/Owner/Models/OwnerTypeModel.php`: `allowsParent()` already accepts a type as its own parent once the type is stored.
