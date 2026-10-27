# Channels & Locales — Shaping Notes

## Scope

Roadmap Milestone 4: locales and channels as catalog records, with localizable and scopable values enforced against them, per-channel/per-locale reads, and dashboard editing of every slot. Same surfaces: Actions → HTTP, MCP, Atrium, events, tests.

## Decisions

- **Locales**: `code` (e.g. `en_US`, `fr`; immutable), `labels`. A locale must exist before values use it.
- **Channels** (Akeneo "scopes"): `code` (immutable), `labels`, `locales` (at least one — the languages the channel publishes in), `currencies` (ISO 4217 list), optional `category_tree` (a root category: the catalog branch the channel sells).
- **Enforcement** in the value validator:
  - a localizable value's `locale` must be an existing locale;
  - a scopable value's `scope` must be an existing channel;
  - a value both localizable and scopable must use one of that channel's locales;
  - a price in a scopable attribute uses only the channel's currencies.
  Label maps stay free-form (any locale-shaped key), so translations can be prepared before a locale is activated.
- **Reading by channel**: product, product model and product search endpoints accept `scope` and `locales` to return only the matching slots (plus the channel- and locale-independent ones) — what an export to one storefront needs.
- **Deleting**: a locale used by a channel is refused (409). Deleting a locale or a channel purges the value slots stored under it, in a queued job. A root category used as a channel's tree cannot be deleted.
- **Dashboard**: value forms get locale and channel pickers, so every slot is editable (scopable values were API-only until now).

## Context

- **Visuals:** None.
- **References:** value validator and storage shape (products slice); purge job pattern.
- **Product alignment:** roadmap — "Localizable and channel-scoped attribute values; translations of labels and values".

## Standards Applied

- None indexed yet; see the foundation spec.
