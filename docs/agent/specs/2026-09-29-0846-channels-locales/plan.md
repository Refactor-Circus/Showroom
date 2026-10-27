# Channels & Locales — Plan

1. Save spec documentation (`shape.md`, this plan).
2. Migration `2026_01_01_000007_create_showroom_channel_tables.php`: locales, channels (currencies, category tree), channel–locale pivot.
3. `Locale` and `Channel` models, factories, model events.
4. Actions (10) with events; `WritesChannels` concern; `PurgeValueSlots` job on locale/channel delete; category delete guarded by channel trees.
5. `ValueValidator` enforces existing locales and channels, a channel's own locales for values that are both, and its currencies for scoped prices.
6. `ValueFilter` + `presentedValues()`: `scope` / `locales` on product and model show, and product search.
7. HTTP, MCP (10 tools + `scope` / `locales` params), policies, config.
8. Atrium: channels page (locales, channels), channel page; locale/channel picker on product and model value forms (`EditingSlot`), every slot editable.
9. Tests (fixture creates locales and channels), docs (`09-channels.md` and cross-references), README, CHANGELOG, Boost skill.

Verification: `composer test`.
