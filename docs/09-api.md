# HTTP API

Routes are registered under `showroom.routes.prefix` (default `showroom`) with `showroom.routes.middleware` (default `['api']`). Disable them with `showroom.routes.enabled`.

## Endpoints

| Method | Path | Action |
|---|---|---|
| `GET` | `/showroom/attribute-groups` | `ListAttributeGroupsAction` — `search`, `cursor`, `per_page` |
| `POST` | `/showroom/attribute-groups` | `CreateAttributeGroupAction` → `201` |
| `GET` | `/showroom/attribute-groups/{group}` | `ShowAttributeGroupAction`, with its attributes |
| `PATCH` | `/showroom/attribute-groups/{group}` | `UpdateAttributeGroupAction` |
| `DELETE` | `/showroom/attribute-groups/{group}` | `DeleteAttributeGroupAction` → `204` |
| `GET` | `/showroom/attributes` | `ListAttributesAction` — `type`, `group`, `search`, `cursor`, `per_page` |
| `POST` | `/showroom/attributes` | `CreateAttributeAction` → `201` |
| `GET` | `/showroom/attributes/{attribute}` | `ShowAttributeAction`, with options for select types |
| `PATCH` | `/showroom/attributes/{attribute}` | `UpdateAttributeAction` |
| `DELETE` | `/showroom/attributes/{attribute}` | `DeleteAttributeAction` → `204` |
| `GET` | `/showroom/attributes/{attribute}/options` | `ListAttributeOptionsAction` — `search`, `cursor`, `per_page` |
| `POST` | `/showroom/attributes/{attribute}/options` | `CreateAttributeOptionAction` → `201` |
| `PATCH` | `/showroom/attributes/{attribute}/options/{option}` | `UpdateAttributeOptionAction` |
| `DELETE` | `/showroom/attributes/{attribute}/options/{option}` | `DeleteAttributeOptionAction` → `204` |
| `GET` | `/showroom/families` | `ListFamiliesAction` — `search`, `attribute`, `cursor`, `per_page` |
| `POST` | `/showroom/families` | `CreateFamilyAction` → `201` |
| `GET` | `/showroom/families/{family}` | `ShowFamilyAction`, with its attributes |
| `PATCH` | `/showroom/families/{family}` | `UpdateFamilyAction` |
| `DELETE` | `/showroom/families/{family}` | `DeleteFamilyAction` → `204` |
| `GET` | `/showroom/family-variants` | `ListFamilyVariantsAction` — `family`, `search`, `cursor`, `per_page` |
| `POST` | `/showroom/family-variants` | `CreateFamilyVariantAction` → `201` |
| `GET` | `/showroom/family-variants/{familyVariant}` | `ShowFamilyVariantAction`, with levels and common attributes |
| `PATCH` | `/showroom/family-variants/{familyVariant}` | `UpdateFamilyVariantAction` |
| `DELETE` | `/showroom/family-variants/{familyVariant}` | `DeleteFamilyVariantAction` → `204` |
| `GET` | `/showroom/product-models` | `ListProductModelsAction` — `family_variant`, `parent`, `roots`, `search`, `cursor`, `per_page` |
| `POST` | `/showroom/product-models` | `CreateProductModelAction` → `201` |
| `GET` | `/showroom/product-models/{productModel}` | `ShowProductModelAction`, with sub-models and products |
| `PATCH` | `/showroom/product-models/{productModel}` | `UpdateProductModelAction` |
| `DELETE` | `/showroom/product-models/{productModel}` | `DeleteProductModelAction` → `204` |
| `GET` | `/showroom/assets` | `ListAssetsAction` — `search`, `type`, `product`, `product_model`, `owner`, `role`, `cursor`, `per_page` |
| `POST` | `/showroom/assets` | `CreateAssetAction` — `file`, `path` or `url` → `201` |
| `GET` | `/showroom/assets/{asset}` | `ShowAssetAction`, with its links |
| `PATCH` | `/showroom/assets/{asset}` | `UpdateAssetAction` — labels, or replace the file (multipart: POST with `_method=PATCH`) |
| `DELETE` | `/showroom/assets/{asset}` | `DeleteAssetAction` → `204` |
| `POST` | `/showroom/assets/{asset}/links` | `AttachAssetAction` — `type`, `target`, `role`, `sort_order` |
| `DELETE` | `/showroom/assets/{asset}/links` | `DetachAssetAction` — `type`, `target`, optional `role` |
| `GET` | `/showroom/association-types` | `ListAssociationTypesAction` — `search`, `cursor`, `per_page` |
| `POST` | `/showroom/association-types` | `CreateAssociationTypeAction` → `201` |
| `GET` / `PATCH` / `DELETE` | `/showroom/association-types/{associationType}` | Show / update labels / delete |
| `GET` | `/showroom/locales` | `ListLocalesAction` — `search`, `cursor`, `per_page` |
| `POST` | `/showroom/locales` | `CreateLocaleAction` → `201` |
| `GET` / `PATCH` / `DELETE` | `/showroom/locales/{locale}` | Show / update labels / delete |
| `GET` | `/showroom/channels` | `ListChannelsAction` — `search`, `cursor`, `per_page` |
| `POST` | `/showroom/channels` | `CreateChannelAction` → `201` |
| `GET` / `PATCH` / `DELETE` | `/showroom/channels/{channel}` | Show / update / delete |
| `GET` | `/showroom/categories` | `ListCategoriesAction` — `roots`, `parent`, `under`, `search`, `cursor`, `per_page` |
| `POST` | `/showroom/categories` | `CreateCategoryAction` → `201` |
| `GET` | `/showroom/categories/{category}` | `ShowCategoryAction`, with its chain and children |
| `PATCH` | `/showroom/categories/{category}` | `UpdateCategoryAction` — labels, order, or move with `parent` |
| `DELETE` | `/showroom/categories/{category}` | `DeleteCategoryAction` → `204` |
| `GET` | `/showroom/owner-types` | `ListOwnerTypesAction` — `search`, `cursor`, `per_page` |
| `POST` | `/showroom/owner-types` | `CreateOwnerTypeAction` → `201` |
| `GET` | `/showroom/owner-types/{ownerType}` | `ShowOwnerTypeAction` |
| `PATCH` | `/showroom/owner-types/{ownerType}` | `UpdateOwnerTypeAction` |
| `DELETE` | `/showroom/owner-types/{ownerType}` | `DeleteOwnerTypeAction` → `204` |
| `GET` | `/showroom/owners` | `ListOwnersAction` — `type`, `parent`, `under`, `roots`, `search`, `cursor`, `per_page` |
| `POST` | `/showroom/owners` | `CreateOwnerAction` → `201` |
| `GET` | `/showroom/owners/{owner}` | `ShowOwnerAction`, with its chain and children |
| `PATCH` | `/showroom/owners/{owner}` | `UpdateOwnerAction` — labels, or move with `parent` |
| `DELETE` | `/showroom/owners/{owner}` | `DeleteOwnerAction` → `204` |
| `GET` | `/showroom/products` | `ListProductsAction` — search, `updated_since`; see [Search](05-search.md) |
| `POST` | `/showroom/products` | `CreateProductAction` → `201` |
| `GET` | `/showroom/products/{product}` | `ShowProductAction`, with inherited values — `scope`, `locales[]`; `ETag`, `304` on `If-None-Match` |
| `PATCH` | `/showroom/products/{product}` | `UpdateProductAction` |
| `DELETE` | `/showroom/products/{product}` | `DeleteProductAction` → `204` |
| `POST` | `/showroom/products/{product}/transitions` | `TransitionProductAction` — `transition`, `comment` |
| `GET` | `/showroom/products/{product}/versions` | `ListProductVersionsAction` — `action`, `cursor`, `per_page` |
| `GET` | `/showroom/products/{product}/versions/{version}` | `ShowProductVersionAction` — a number, `latest` or `published` |
| `POST` | `/showroom/products/{product}/revert` | `RevertProductAction` — `version`, `comment` |
| `POST` | `/showroom/imports` | `StartImportAction` → `202` with the Impex run — `file`, `url` or `asset`, `format`, `mode`; see [Import, export and feeds](12-impex.md) |
| `POST` | `/showroom/exports` | `StartExportAction` → `202` with the Impex run — search filters, `format`, `code`, `published` |

Path parameters are codes — identifiers for products. An option is looked up within its attribute.

## Example

```http
POST /showroom/attributes
Content-Type: application/json

{"code": "color", "type": "select", "group": "marketing", "labels": {"en": "Color"}}
```

```json
{
  "data": {
    "id": "01J...",
    "code": "color",
    "type": "select",
    "group": "marketing",
    "labels": {"en": "Color"},
    "is_unique": false,
    "is_localizable": false,
    "is_scopable": false,
    "settings": [],
    "sort_order": 0,
    "options": [],
    "created_at": "2026-09-29T09:00:00+00:00",
    "updated_at": "2026-09-29T09:00:00+00:00",
    "changed_at": "2026-09-29T09:00:00+00:00"
  }
}
```

## Pagination

Product search is page-numbered (`page`, `per_page`, `meta.total`). Other listings are cursor paginated in display order (`sort_order`, then `code`). Pass `meta.next_cursor` back as `cursor`. `per_page` defaults to `showroom.pagination.per_page` and is capped at `showroom.pagination.max_per_page`.

## Polling products

Clients that poll the catalog have two helpers:

- `GET /showroom/products?updated_since=2026-10-01T00:00:00Z` lists products where anything they show changed at or after that moment — inherited changes, refilings, associations and assets included. It filters on each product's `changed_at`, which the API returns; see [Search](05-search.md#changed-since).
- `GET /showroom/products/{product}` carries an `ETag` of its body. Send it back as `If-None-Match` and an unchanged product answers `304 Not Modified` with no body. The tag covers the response as asked for, so the same product read with other `scope` or `locales[]` has another tag.

Vendors who need every change, inherited ones included, subscribe to product webhooks instead of polling; see [Import, export and feeds](12-impex.md#product-webhooks).

## Errors

| Status | When |
|---|---|
| `401` | Unauthenticated, with authorization on |
| `403` | Denied by a policy, with authorization on |
| `404` | Unknown code: `{"message": "Not found."}` |
| `409` | A catalog rule: deleting something still in use (a group with attributes, a family with products, a family variant with models, an attribute that labels a family or shapes a variant), options on a type that takes none. The body is `{"message": "..."}`. |
| `422` | Validation — including changing a code, type or identifier, invalid values, taken unique values, missing or duplicate axes — and search filters the engine cannot run |

## Authorization

With `showroom.authorization` on, every request needs an authenticated user and is checked against the model's policy from `showroom.policies`: `viewAny`, `view`, `create`, `update` and `delete`. Option endpoints also check `view` or `update` on the parent attribute. The bundled policies allow any authenticated user; replace one to restrict the catalog:

```php
'policies' => [
    \RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel::class => \App\Policies\CatalogAttributePolicy::class,
    // ...
],
```
