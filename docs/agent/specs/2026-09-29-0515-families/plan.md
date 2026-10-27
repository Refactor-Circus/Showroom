# Families — Plan

1. Save spec documentation (`shape.md`, this plan).
2. Migration `2026_01_01_000002_create_showroom_family_tables.php`: `showroom_families` (code, labels, label_attribute_id restrict, sort_order) and `showroom_family_attributes` (family, attribute, is_required, sort_order).
3. `Family` model (`familyAttributes()`, `labelAttribute()`), factory, model events.
4. Actions `List/Show/Create/Update/DeleteFamilyAction` with action events; shared `Actions\Concerns\WritesFamilyAttributes` for membership sync and label rules. `DeleteAttributeAction` refuses a family's label attribute (`AttributeLabelsFamiliesException`, 409).
5. HTTP: `FamilyResource`, `FamilyAttributeResource`, requests, `FamilyController`, `/showroom/families` routes. `FamilyPolicy` in config.
6. MCP: five tools and requests, added to `ShowroomServer::TOOLS`; instructions mention families.
7. Atrium: Families nav item, index and show pages (membership table with required, order, remove; add attribute; label attribute), search.
8. Tests: API, MCP, UI, events/tools counts, policy registration.
9. Docs (`03-families.md`, API, MCP, dashboard), README, CHANGELOG, Boost skill.

Verification: `composer test`.
