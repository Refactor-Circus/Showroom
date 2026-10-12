# Showroom documentation

| Guide | |
|---|---|
| [Installation](01-installation.md) | Install, publish, migrate, secure |
| [Attributes](02-attributes.md) | Attribute groups, typed attributes, options |
| [Families](03-families.md) | Attribute sets and required attributes |
| [Products and variants](04-products.md) | Values, uniqueness, family variants, product models |
| [Search](05-search.md) | Database and Scout engines, or your own |
| [Ownership](06-ownership.md) | Owner types, owner chains, assigning products |
| [Taxonomy](07-taxonomy.md) | Category trees, filing products |
| [Media](08-media.md) | Assets on any disk, linked to products, models and owners |
| [Channels and locales](09-channels.md) | Locales, channels, scoped and localized values |
| [Associations](10-associations.md) | Cross-sell, compatible parts, bundles and kits |
| [Workflow](11-workflow.md) | Completeness, review and publishing, versions and revert |
| [Import, export and feeds](12-impex.md) | Bulk imports and exports, syndication feeds, ERP connectors, product webhooks for vendors (with Impex) |
| [HTTP API](09-api.md) | Endpoints, pagination, errors |
| [MCP](10-mcp.md) | Tools for agents, Cortex |
| [Dashboard](11-dashboard.md) | The Atrium plugin |
| [Configuration](13-configuration.md) | Every config key |

## One page

```php
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeGroupData;

app(CreateAttributeGroupAction::class)->execute(CreateAttributeGroupData::from(['code' => 'technical', 'labels' => ['en' => 'Technical']]));

app(CreateAttributeAction::class)->execute(CreateAttributeData::from([
    'code' => 'weight',
    'type' => 'metric',
    'group' => 'technical',
    'settings' => ['metric_family' => 'weight', 'default_unit' => 'kilogram'],
]));
```

The same call over HTTP is `POST /showroom/attributes`, and over MCP `create-attribute-tool`.
