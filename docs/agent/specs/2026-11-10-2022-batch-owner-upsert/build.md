# Build

## Group 1

OwnerWriter extraction (tasks 1.1-1.3; criteria 4, 6, 7, 11).

### Files

- `src/Domains/Owner/Services/OwnerWriter.php` (new, `@internal`): `create(CreateOwnerData, OwnerTypeModel $type, ?OwnerModel $parent): OwnerModel` and `update(OwnerModel, UpdateOwnerData, ?OwnerModel $parent = null, ?OwnerTypeModel $type = null): bool`. They hold the placement rules (through `PlacesOwners`), the slug claim, and the external-id and flags writes with the `assign` check. Neither opens a transaction, fires an event or reloads the returned owner's relations. `update()` returns whether the products beneath need a reindex (a move or a flag change) and leaves the queueing to its caller, so the batch can queue one sync. It reads the parent or type itself only when it isn't given one, which keeps the single update action's query and model-event order unchanged. A given parent or type that isn't the one the data names, or the one the owner has, throws `InvalidArgumentException`.
- `src/Domains/Owner/Actions/CreateOwnerAction.php`: resolves the type and parent in its transaction as before, writes through `OwnerWriter`, then loads `type`, `parent`, `externalIds` and flags. It is still final, and its constructor signature, events and return value are unchanged.
- `src/Domains/Owner/Actions/UpdateOwnerAction.php`: writes through `OwnerWriter` in its transaction and calls `ProductIndex::queueWithin(SyncScope::Owner, id)` when the writer reports a move or a flag change. It then loads the same return graph as before. It is still final, and its constructor signature, events and return value are unchanged.
- `tests/Feature/OwnerWriterTest.php` (new).

### Tests

- `OwnerWriterTest` (21 tests) covers create and update with a preloaded type and parent:
  - `can_be_root` and `parent_types`;
  - a move under the owner itself, a child or a grandchild;
  - the path and depth of a moved owner and its descendants, and `parent: null` to the root;
  - slugs claimed as `CreateOwnerAction` claims them;
  - external ids and flags written;
  - `assign` refused when flags are set on create or changed on update, before anything is written;
  - no `owner_types` or parent re-read when they are given;
  - mismatched type or parent guards.
- Run narrowly: `OwnerWriterTest`, `SlugsTest`, `FlagAssignmentTest`, `ExternalIdsTest`, `RecordLookupTest`, `Ui/OwnerPagesTest`, `EventsTest` and `ArchTest` all pass (392 tests). The gate runs the full suite.
- Pint passes. PHPStan passes on the changed `src` files.

### Deviations

- `CreateOwnerAction` and `UpdateOwnerAction` build their `OwnerWriter` in the constructor from the services they are given, instead of having it injected, so their constructor signatures stay the same. `UpdateOwnerAction` takes no `Slugs`, so it resolves one with `app(Slugs::class)` for the writer, which uses it only on create.
- `Concerns/PlacesOwners` is now used only by `OwnerWriter`. It is unchanged.
