VERDICT: CHANGES

# Standards review, round 1

Arch, Styles, ScreenExtensions and Docs tests pass. The diff follows the listed standards except for one unrecorded deviation (minor) and three nits.

## Findings

- minor: The bulk path (BulkProductWriter::store) inserts products, category rows, external ids, flag assignments, unique values and versions with raw table inserts, so it fires no Eloquent model events and no CreateProductAction event pair, and it writes Workflow, Attribute, Flag and ExternalId tables directly. The spec requires this (AC 29, 38, D-008), but it breaks actions-events, dependencies and lifecycle-events, and docs/agent/standards-feedback.md has a row only for the lookup deviation. Fix: add a row for it, citing the spec and D-008.
- nit: The request rules() for show, update and delete add RecordLookup::rules() (`by`) beside the Data rules (request-persist says rules() delegates to the Data rules). `by` is not action input, so this is defensible.
- nit: The ExternalId domain has no service provider. Its morph alias and the scoped ExternalIds binding sit in ShowroomServiceProvider, unlike Flag and the other domains.
- nit: AGENTS.md still says "its keys do not change" about config/showroom.php, but this spec adds products, lookup and three tables keys and policies entries.

No decisions raised. Recorded deviations (D-001, D-010 vs stored-identifiers, routes, resources) have their standards-feedback row.
