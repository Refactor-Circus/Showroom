# Declare screen extensions (Showroom) — Shaping Notes

## Scope

Move Showroom's screens onto Atrium's screen extensions registry (Atrium `2026-10-29-2037-screen-extensions-registry`), and stop rendering other packages' UI inline.

- **Inline audit trails.** Remove `<x-atrium::audit-trail>` from the products index and the product, category, attribute, owner, product-model, asset, family and channel show pages. Each screen declares its key (`showroom.product`, …) and its audited subject (package `showroom`, the record, or package-wide for the index). Keen's History section then attaches by capability.
- **Run link.** Remove the inline Impex run link check from `ui/transfers/index.blade.php:62`. Declare a row slot on `showroom.transfers` so that Impex registers the link (Impex spec `register-showroom-run-link`). Without Impex, the row shows the flow name as plain text, as today.
- **Out of scope (section B):** the transfers page and nav gating on `ImpexIntegration::active()`, the Impex flows integration, and the Scout search fallback.

## Decisions

- D-001: each screen keeps its current visual placement through declared slots (e.g. `lg:col-span-3` on assets is handled by the slot wrapper).

## Context

- **Depends on:** Atrium `screen-extensions-registry`. Pairs with Keen `history-section-by-capability` and Impex `register-showroom-run-link`.
- **References:** see `references.md`.

## Standards

- architecture/dependencies: no inline checks for optional packages in views.
- atrium/components: `x-atrium::*` only, so `StylesTest` stays green.
- testing/test-layers: feature tests assert each screen declares its subject, and the existing suites stay green.

## Addendum (2026-10-29): every screen declares slots and tab bar

User decision (Atrium D-013): every Atrium screen in this package declares itself with `Screen::make("{package}.{screen}")`, renders the Atrium tab bar (only visible once at least 2 tabs are visible), and declares the standard slots: `header.actions`, `before` and `after` on all screens; `aside` and `history` (when audited) on detail screens; the `row.actions` row slot on list screens. Screens without built-in tabs get a default `overview` tab. With nothing registered, every existing feature and browser test passes unchanged. A feature test per screen registers a tab and a section in each standard slot and asserts that they render.
Showroom screens (about 20): products, product-models, channels, assets, categories, owners, owner-types, families, attributes, attribute-groups, association-types and transfers (index and show where they exist), plus create forms.
