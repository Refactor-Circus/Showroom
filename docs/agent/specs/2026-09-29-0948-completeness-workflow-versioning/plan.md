# Completeness, Workflow & Versioning — Plan

1. Save spec documentation (`shape.md`, this plan).
2. Migration `2026_01_01_000009_create_showroom_workflow_tables.php`: `required_channels` on family attributes, `status` / `published_version` / `published_at` on products, completeness and versions tables.
3. `ProductStatus` and `Transition` enums; `Version` and `Completeness` models with events; product relations and defaults.
4. `Workflow\CompletenessCalculator`, refreshed by the index sync job for every engine; requeues on family and channel changes; `showroom:search:reindex` recomputes.
5. `Workflow\Versions` (snapshot, diff, author, action context, cleanup); product create/update record versions; approved products reopen to draft on change.
6. Actions: `TransitionProductAction`, `ListProductVersionsAction`, `ShowProductVersionAction`, `RevertProductAction` with events; `workflow` config.
7. Search: `status`, `published`, `complete` on all engines; document fields.
8. HTTP, MCP (4 tools + search/family params); product resource status, live version, completeness.
9. Atrium: workflow, completeness and history cards with transitions and revert; status on the product list; per-channel requirements on families.
10. Tests, docs (`11-workflow.md` and cross-references), README, CHANGELOG, Boost skill.

Verification: `composer test`.
