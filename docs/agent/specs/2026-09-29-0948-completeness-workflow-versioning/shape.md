# Completeness, Workflow & Versioning — Shaping Notes

## Scope

Roadmap Milestone 6: completeness scoring per channel and locale; an enrichment workflow with review, approval and publishing; versioning with an audit history and revert. Products are the subject (product models feed their variants' completeness and snapshots). Same surfaces: Actions → HTTP, MCP, Atrium, events, search, tests.

## Decisions

### Completeness
- A family attribute's requirement can be narrowed to channels: `{"attribute": "copy", "is_required": true, "required_channels": ["ecommerce"]}`; without `required_channels`, a required attribute is required on every channel.
- For each channel and each of its locales, a product scores `ratio` = filled required slots / required slots (0–100), with the `missing` attribute codes. A slot is filled when it holds a non-empty value — own or inherited from product models — in the channel (if scopable) and locale (if localizable). Products without a family have no completeness.
- Scores are stored (`showroom_product_completeness`) so search can filter on them, and refreshed by the same queued job that feeds the search index, whenever a product, its models, its family or a channel changes. The job now runs for every engine (completeness is derived data too).
- Search gains `complete`: `{"scope": "ecommerce", "locale": "en", "min": 100}` (locale optional: then every locale of the channel must reach `min`).

### Workflow
- `status`: `draft` → `in_review` → `approved`, plus `archived`. Transitions through one Action: `submit`, `approve`, `reject` (back to draft, with a comment), `publish`, `unpublish`, `archive`, `restore`.
- **Publishing snapshots**: `publish` (from `approved`) points `published_version` at the product's current version. The working copy stays editable; storefronts read `?version=published`. Editing an `approved` product sends it back to `draft` for review; the live version is untouched until the next publish.
- `showroom.workflow.require_approval` (default true) — off, `publish` works from any non-archived status. `showroom.workflow.require_complete` (default false) — on, `submit` needs 100% on every channel.

### Versioning & audit
- Every product write records a version: `version` number, `action` (`created`, `updated`, `submitted`, `approved`, `rejected`, `published`, `unpublished`, `archived`, `restored`, `reverted`), a `snapshot` (own values, inherited values, family, parent, owner, categories, associations, enabled, status), the `changes` against the previous version, an optional `comment`, the author (the authenticated user, when there is one) and the time. A write that changes nothing records nothing.
- `GET /products/{id}/versions`, `…/versions/{n}`; `GET /products/{id}?version=published|n` reads a snapshot.
- **Revert** to a version restores its own values, categories, associations, family, owner and enabled flag, recorded as a new `reverted` version.
- Versions go when the product is deleted.

## Context

- **Visuals:** None.
- **References:** product Actions, index job, value storage, family attributes.
- **Product alignment:** roadmap — "Completeness scoring per channel + locale; enrichment workflow, approval and publishing states; versioning and audit history".

## Standards Applied

- None indexed yet; see the foundation spec.
