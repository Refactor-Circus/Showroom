VERDICT: PASS

Standards review of the whole diff found no clear break.

- actions-events: UpsertOwnersAction follows the sibling UpsertProductsAction (plain final class, execute(), starting and finished event pair, per-owner transactions). The existing arch rules cover it.
- data-objects: UpsertOwnersData is final readonly with rules(); records are validated through CreateOwnerData and UpdateOwnerData rules.
- authorization: only the flags assign check per record, as D-004 settles.
- dependencies: core does not reference Impex; the flow is registered in ImpexIntegration.
- models and stored-identifiers: owners and parents are referenced by code, never by id; no literal table names added.
- test layers and arch tests: behaviour is tested at the action, the flow tests its wiring.
