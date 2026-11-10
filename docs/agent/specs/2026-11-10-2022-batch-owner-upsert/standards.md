# Standards for Batch owner upsert

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| actions/actions-events | vendor/refactor-circus/standards/standards/actions/actions-events.md | refactor-circus/standards@dev-main | New `UpsertOwnersAction` with its starting/finished events; per-record owner events kept |
| data/data-objects | vendor/refactor-circus/standards/standards/data/data-objects.md | refactor-circus/standards@dev-main | New `UpsertOwnersData` and `UpsertOwnersResult`; records validated with `CreateOwnerData`/`UpdateOwnerData` rules |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | Flags `assign` checked per record when flags change, as the single actions do |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | Action in core; `showroom:upsert-owners` flow in the optional Impex integration |
| models/models | vendor/refactor-circus/standards/standards/models/models.md | refactor-circus/standards@dev-main | Owners, owner types, external ids and flags loaded in batch |
| models/stored-identifiers | vendor/refactor-circus/standards/standards/models/stored-identifiers.md | refactor-circus/standards@dev-main | Records matched by owner code or by an external-id source; parents referenced by code |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Behaviour and query bounds tested at the action; the flow tests its wiring |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | New classes pass the arch preset; core does not use Impex |
