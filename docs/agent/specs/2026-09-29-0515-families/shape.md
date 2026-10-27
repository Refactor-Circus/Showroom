# Families — Shaping Notes

## Scope

Roadmap Milestone 1, second slice. A family (attribute set) says which attributes a kind of product has and which of them are required. Built on the foundation from `2026-09-29-0453-foundation-and-attributes`: Actions shared by HTTP, MCP and the Atrium dashboard, with events, policies, and tests.

## Decisions

- Family: `code` (immutable), `labels`, `sort_order`, optional `label_attribute` (the attribute used as a product's display label).
- Membership in `showroom_family_attributes` (family, attribute, `is_required`, `sort_order`). Composite key, no id.
- `attributes` on create/update is the whole membership list, replaced on update: `[{"attribute": "color", "is_required": true, "sort_order": 1}]`. One call gives a predictable result; no attach/detach Actions yet.
- `is_required` is global for now. It becomes per channel when channels land (milestone 4) and feeds completeness (milestone 6).
- The label attribute must be a `text` attribute in the family.
- Deleting an attribute removes it from every family, except when it is some family's label attribute: refused with a message (409).
- Deleting a family is allowed; it will be refused once products reference families.
- Addressed by code everywhere, like attributes.

## Context

- **Visuals:** None.
- **References:** attribute slice in this package (`src/Actions/*Attribute*`, `src/Http`, `src/Mcp`, `src/Http/Ui`).
- **Product alignment:** mission — "Families (attribute sets) with required/optional attributes".

## Standards Applied

- None indexed yet; see the foundation spec.
