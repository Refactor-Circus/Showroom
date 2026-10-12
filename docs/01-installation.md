# Installation

```bash
composer require refactor-circus/showroom

php artisan vendor:publish --tag="showroom-migrations"
php artisan migrate
```

Showroom requires PHP 8.5 and Laravel 13. It depends on `refactor-circus/atrium` for the dashboard and `laravel/mcp` for the MCP server. For product search beyond the database, add `laravel/scout` with a Scout engine (picked automatically once installed) — see [Search](05-search.md). For bulk imports, exports and feeds, add `refactor-circus/impex` — see [Import, export and feeds](12-impex.md).

Asset files go to `showroom.media.disk` — use S3 or another object store on Vapor and Lambda. See [Media](08-media.md).

Run a queue worker: completeness scores and the search index are refreshed by queued jobs after each write, as are value purges after an attribute, option, locale or channel is deleted.

## Publishing

| Tag | Publishes |
|---|---|
| `showroom` | Everything below |
| `showroom-config` | `config/showroom.php` |
| `showroom-migrations` | The catalog migrations |
| `showroom-views` | Dashboard views, to `resources/views/vendor/showroom` |
| `showroom-lang` | Translations, to `lang/vendor/showroom` |

## Securing the surfaces

- **HTTP API:** add authentication middleware to `showroom.routes.middleware` (for example `['api', 'auth:sanctum']`). With `showroom.authorization` on — the default — every call also needs an authenticated user and passes the policies in `showroom.policies`.
- **MCP:** both transports ship disabled. Add auth middleware to `showroom.mcp.web.middleware` before enabling the web transport.
- **Dashboard:** Atrium owns the path, middleware and `viewAtrium` gate.

## Tables

All tables are prefixed `showroom_`: `showroom_attribute_groups`, `showroom_attributes`, `showroom_attribute_options`, `showroom_families`, `showroom_family_attributes`, `showroom_family_variants`, `showroom_family_variant_attributes`, `showroom_product_models`, `showroom_products`, `showroom_product_unique_values`, `showroom_owner_types`, `showroom_owner_type_parents`, `showroom_owners`, `showroom_categories`, `showroom_category_product`, `showroom_category_product_model`, `showroom_assets`, `showroom_asset_links`, `showroom_locales`, `showroom_channels`, `showroom_channel_locale`, `showroom_association_types`, `showroom_associations`, `showroom_product_completeness`, `showroom_versions`. Keys are ULIDs. Rename any of them before migrating with `showroom.tables` (keyed by the name without `showroom_`).

Owners and categories are trees stored as materialized paths (`/root-id/…/own-id/`, 27 characters a level), and a subtree is one prefix match on `path`. The column stays `text`; `2026_10_12_000001_add_showroom_performance_indexes` gives each driver the index its prefix `LIKE` can use: a 255-character prefix index on MySQL/MariaDB (the first nine levels; deeper trees still narrow by them), `text_pattern_ops` on PostgreSQL (a plain index serves prefix `LIKE` only under the `C` collation), and `COLLATE NOCASE` on SQLite. SQL Server cannot index `nvarchar(max)`, so it has none. The same migration indexes the foreign keys PostgreSQL and SQLite leave unindexed, and products' `created_at`, `updated_at` and `published_version`. Publish the migrations again (`--tag="showroom-migrations"`) to pick it up.
