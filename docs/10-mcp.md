# MCP

The same operations over MCP as over HTTP. Both surfaces call one Action, so they cannot drift — an arch test fails if an Action has no MCP tool.

```php
// config/showroom.php — both transports ship disabled
'mcp' => [
    'web' => [
        'enabled' => true,
        'route' => 'mcp/showroom',
        'middleware' => ['auth:api'],
    ],
    'local' => [
        'enabled' => true,
        'handle' => 'showroom',
    ],
],
```

**Add auth middleware to the web transport before enabling it.** The tools change the catalog every product depends on.

With `showroom.authorization` on, each tool acts as the authenticated user and checks the same policy ability as its HTTP endpoint; a denied call answers `Unauthorized.`.

Every tool declares an output schema. Tools answer with the same resource as the HTTP API, under `data`; deletes answer `{"deleted": true, "code": ...}`. Lists carry their page under `meta`: `meta.next_cursor` (pass it back as `cursor`), or `meta.current_page`, `meta.last_page` and `meta.total` for `list-products-tool`, which also returns `facets`. `list-showroom-history-tool` is offered only while Showroom audits (refactor-circus/keen installed and `showroom.audit.enabled` on).

## Tools

The server lists two entry points, `search_tools` and `execute_tools`, and keeps the Showroom tools behind them, so a client loads only the schemas it searches for:

```json
{ "calls": [{ "name": "list-attributes-tool", "arguments": {"type": "select"} }] }
```

| Tool | Action |
|---|---|
| `list-attribute-groups-tool` | Groups in display order. Cursor paginated. |
| `show-attribute-group-tool` | One group with its attributes |
| `create-attribute-group-tool` | Create a group |
| `update-attribute-group-tool` | Labels, sort order |
| `delete-attribute-group-tool` | Delete an empty group |
| `list-attributes-tool` | Filter by type, group, search. Cursor paginated. |
| `show-attribute-tool` | One attribute with group, settings and options |
| `create-attribute-tool` | Create an attribute |
| `update-attribute-tool` | Group, labels, flags, settings, sort order |
| `delete-attribute-tool` | Delete an attribute and its options |
| `list-attribute-options-tool` | Options of a select or multiselect attribute |
| `create-attribute-option-tool` | Add an option |
| `update-attribute-option-tool` | Labels, sort order |
| `delete-attribute-option-tool` | Delete an option |
| `list-families-tool` | Filter by search or an attribute they include. Cursor paginated. |
| `show-family-tool` | One family with its attributes and label attribute |
| `create-family-tool` | Create a family with its attributes |
| `update-family-tool` | Labels, attribute list (replaced whole), label attribute, sort order |
| `delete-family-tool` | Delete a family, keeping its attributes |
| `list-family-variants-tool` | By family. Cursor paginated. |
| `show-family-variant-tool` | Levels, axes and common attributes |
| `create-family-variant-tool` | One or two levels of axes |
| `update-family-variant-tool` | Labels; levels while unused |
| `delete-family-variant-tool` | Delete an unused family variant |
| `list-product-models-tool` | By family variant or parent. Cursor paginated. |
| `show-product-model-tool` | Values, sub-models and variant products |
| `create-product-model-tool` | Root model or sub-model |
| `update-product-model-tool` | Values; variants follow |
| `delete-product-model-tool` | With its sub-models and variants |
| `list-products-tool` | Search: text, family, filters, facets, sort, `updated_since`. Page-numbered. |
| `show-product-tool` | All values, inherited included |
| `create-product-tool` | Simple or variant product |
| `update-product-tool` | Family, enabled, values (patched) |
| `delete-product-tool` | Delete a product |
| `transition-product-tool` | Submit, approve, reject, publish, unpublish, archive, restore |
| `list-product-versions-tool` | History: actions, authors, changes |
| `show-product-version-tool` | A version, `latest` or `published`, with its snapshot |
| `revert-product-tool` | Restore an earlier version |
| `list-association-types-tool` / `show-association-type-tool` / `create-association-type-tool` / `update-association-type-tool` / `delete-association-type-tool` | Association types; associations are sent with the product and product model tools |
| `list-locales-tool` / `show-locale-tool` / `create-locale-tool` / `update-locale-tool` / `delete-locale-tool` | Locales |
| `list-channels-tool` / `show-channel-tool` / `create-channel-tool` / `update-channel-tool` / `delete-channel-tool` | Channels: locales, currencies, category tree |
| `list-assets-tool` | By type or linked record |
| `show-asset-tool` | URL and links |
| `create-asset-tool` | From a URL or a disk path |
| `update-asset-tool` | Labels, or replace the file |
| `delete-asset-tool` | Asset, links and file |
| `attach-asset-tool` | Link to a product, model or owner under a role |
| `detach-asset-tool` | Unlink |
| `list-categories-tool` | Trees, children, or a whole branch |
| `show-category-tool` | Chain, children, filed counts |
| `create-category-tool` | A category, or a new tree |
| `update-category-tool` | Labels, order, or move a branch |
| `delete-category-tool` | Delete a leaf category |
| `list-owner-types-tool` | Owner types and their chain rules |
| `show-owner-type-tool` | One owner type |
| `create-owner-type-tool` | Define a kind of owner and where it may sit |
| `update-owner-type-tool` | Labels and rules |
| `delete-owner-type-tool` | Delete an unused owner type |
| `list-owners-tool` | By type, parent, or everything under an owner |
| `show-owner-tool` | Chain, children, product counts |
| `create-owner-tool` | Create an owner under a parent |
| `update-owner-tool` | Labels, or move with everything beneath |
| `delete-owner-tool` | Delete an owner nothing depends on |
| `start-import-tool` | Import products from an asset or URL (with refactor-circus/impex) |
| `start-export-tool` | Export products matching a search to a file asset (with refactor-circus/impex) |

Records are addressed by code: `group`, `attribute`, `option`, `family`, `family_variant`, `product_model`, `owner_type`, `owner`, `category`, `asset`, `locale` and `channel` arguments take codes. `show-product-tool`, `show-product-model-tool` and `list-products-tool` take `scope` and `locales` to return one channel's values; `product` takes an identifier. Catalog rule violations come back as errors with a message an agent can act on.

The catalog is `ShowroomServer::TOOLS`.

`list-products-tool`'s `updated_since` (an ISO 8601 moment) keeps products where anything they show changed at or after it, inherited changes included; see [Search](05-search.md#changed-since).

## Vendor webhooks

Product webhooks ([Import, export and feeds](12-impex.md#product-webhooks)) have no Showroom tools: subscribers, subscriptions and deliveries belong to Impex, and are managed with the Impex MCP server's tools — `list-streams-tool` (the `showroom.products` stream and its topics), `list-subscribers-tool`, `create-subscriber-tool`, `list-subscriptions-tool`, `show-subscription-tool`, `create-subscription-tool`, `update-subscription-tool`, `delete-subscription-tool`, `update-subscription-subjects-tool`, `export-subscription-tool`, `ping-subscription-tool`, `list-subscription-events-tool` and `list-deliveries-tool` (`RefactorCircus\Impex\Mcp\ImpexServer::TOOLS`).

## Cortex

When `refactor-circus/cortex` is installed and loaded, the server is registered with Cortex under `showroom.cortex.server` and every tool joins its tool registry, tagged with the server name. Cortex can then serve published overrides of the server instructions and of each tool's description.

```php
'cortex' => [
    'enabled' => true,
    'server' => 'showroom',
    'tools' => ['list-attributes-tool', 'show-attribute-tool'], // or null for all
],
```
