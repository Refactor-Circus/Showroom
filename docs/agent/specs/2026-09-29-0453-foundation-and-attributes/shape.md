# Foundation + Attributes — Shaping Notes

## Scope

Start roadmap Phase 1 / Milestone 1 (Catalog core). Replace the package skeleton with the architecture mirrored from `refactor-circus/impex`, then ship the first vertical slice: attribute groups, typed attributes and attribute options, each through Actions, HTTP API, MCP tools, Atrium pages and tests.

Families, products/product models/variants and dynamic ownership are out of scope and follow in later specs on this foundation.

## Decisions

- **Slice:** foundation + attributes (not full catalog core, not ownership first).
- **Template:** `../impex` patterns are followed as-is: one final Action per use case with static `rules()`; HTTP FormRequest and MCP Request both delegate to it; parity enforced by an arch test.
- **Attribute types (core set):** text, textarea, number, decimal, boolean, date, select, multiselect, price, metric. Identifier, asset, reference entity and table types come later.
- **Labels:** stored as a `labels` JSON map (locale ⇒ label) now, so milestone 4 (channels & locales) needs no schema change.
- **`is_localizable` / `is_scopable`:** stored now, enforced in milestone 4.
- **Required-ness:** lives on families (next spec), not on attributes.
- **Immutability:** `code` and `type` cannot change after creation (PIM convention; values depend on them).
- **Options:** only allowed on select/multiselect attributes.
- **Group deletion:** blocked while the group still has attributes.
- **Tables:** hardcoded `showroom_*` names, no configurable prefix (keeps `exists:` rules in static `rules()` literal, as in Impex).
- **Addressing (changed during build):** routes, MCP arguments and dashboard URLs address records by immutable `code`, not ULID — codes are what integrations and agents hold. ULIDs remain the primary keys.
- **Surfaces:** Atrium and Cortex included in this slice (product docs followed as-is).

## Context

- **Visuals:** None.
- **References:** `../impex` (full pattern map in `references.md`).
- **Product alignment:** Aligned with `agent-os/product/` as-is — Laravel-native PIM, HTTP/MCP parity via shared Actions, runtime-defined attributes, industry PIM terminology, S3/Vapor-friendly.

## Standards Applied

- None indexed yet (`agent-os/standards/index.yml` is empty). Conventions come from `../impex` and `CLAUDE.md`. Follow-up: run `/agent-os:discover-standards` after this lands.
