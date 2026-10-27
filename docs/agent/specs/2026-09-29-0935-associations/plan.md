# Associations, Bundles & Kits — Plan

1. Save spec documentation (`shape.md`, this plan).
2. Migration `2026_01_01_000008_create_showroom_association_tables.php`: association types, polymorphic associations.
3. `AssociationType` and `Association` models, model events; `HasAssociations` on products and product models.
4. `Associations\Associations`: rules, per-type replacement, two-way mirroring, self/target checks, inherited presentation, cleanup on delete.
5. Association type Actions (5) with events; product and product model Actions take `associations` / `quantified_associations`.
6. HTTP, MCP (5 tools + association params on product/model tools), policy, config; `associations` / `quantified_associations` on product and model resources.
7. Atrium: association types page; associations card with one-at-a-time add/remove on product and model pages.
8. Tests, docs (`10-associations.md` and cross-references), README, CHANGELOG, Boost skill.

Verification: `composer test`.
