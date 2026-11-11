# References

| Reference | Location | Relevance | Pattern to borrow |
| --- | --- | --- | --- |
| UpsertProductsAction | src/Domains/Product/Actions/UpsertProductsAction.php | The batch upsert this mirrors | Match by identifier or source; load matched records together; validate with the create and update Data rules; claim keys in batch order; unchanged skip with an `ExternalIds::touch()` bulk stamp; per-record failures; one index sync per batch |
| UpsertProductsData / UpsertProductsResult / UpsertMode | src/Domains/Product/Data, src/Domains/Product/Enums | Input and result shapes | `records`, `mode`, `match`; created, updated, unchanged, skipped, failures |
| BatchPresenceVerifier | src/Support/BatchPresenceVerifier.php | Primed `exists` and `unique` validation for a batch | Prime the type, parent and code lookups once per batch instead of per record |
| CreateOwnerAction / UpdateOwnerAction | src/Domains/Owner/Actions | The per-record rules to keep | Placement (`checkPlacement`, `place`), slug claim, external ids, flags and `authorizeAssign`, reindex on a move or flag change, events |
| CreateOwnerData / UpdateOwnerData | src/Domains/Owner/Data | Validation rules | Reuse as the products batch reuses the product Data |
| ExternalIds | src/Domains/ExternalId/Services/ExternalIds.php | Batch find, write and touch | `find(subject, source, ids)`, `touch()` |
| Slugs | src/Support/Slugs.php | Slugs for many records at once | `forMany('owners', ...)`, `ownerSource()` |
| Flags service | src/Domains/Flag/Services/Flags.php | Batch load and assign checks | `load(Collection)`, `changes()`, `authorizeAssign()` |
| ProductIndex | src/Domains/Search/Services/ProductIndex.php | Reindex products under changed owners | Once per batch |
| UpsertProductsFlow / UpsertProductChunk | src/Impex/Flows/UpsertProductsFlow.php, src/Impex/Actions/UpsertProductChunk.php | The Impex flow to mirror | Inline source, a chunk per `showroom.impex.chunk`, failed rows on the batch item |
| secret core:vendors measurement | secret PR #5, docs/catalog-import.md | The cost to beat | About 14 queries per owner, 1,744 selects for 200 vendors |
