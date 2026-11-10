# Ownership

Products belong to owners, and owners form chains of any shape and depth:

```
vendor → series → product
vendor → product
vendor → vendor → series → product
manufacturer → vendor → product
```

## Owner types

Owner types are defined at runtime, each with the rules for where its owners may sit:

```json
{"code": "series", "parent_types": ["vendor"], "can_be_root": false, "owns_products": true}
```

| Field | Meaning |
|---|---|
| `parent_types` | Owner types allowed as parent. `null` allows any type; `[]` allows none. |
| `can_be_root` | Whether an owner of this type may have no parent. Default `true`. |
| `owns_products` | Whether products and product models may be assigned to it. Default `true`. |

Rules are checked when owners are created or moved; changing them later does not rewrite owners already placed. An owner type with owners cannot be deleted.

An owner type may name itself as a parent, for chains such as `vendor → vendor → series`: create `vendor` with `parent_types: ["vendor"]`, then place a vendor under another vendor. An unknown parent type is still refused.

## Owners

```json
{"code": "classic", "type": "series", "parent": "acme", "labels": {"en": "Classic"}, "external_ids": {"d365": "V-0042"}, "flags": ["pricing_only"]}
```

- `code` is unique across all owners and never changes; neither does `type`.
- Sending `parent` on update **moves** the owner with everything beneath it. Moving an owner under itself or its own descendant is refused.
- `GET /showroom/owners/{owner}` returns its `chain` of codes, root first: `["globex", "acme", "classic"]`.
- List by `type`, direct children of a `parent`, everything `under` an owner at any depth, or `roots`. Filter by `external[{source}]={id}` and by `flags[]=` (effective flags, inherited ones included).
- An owner with child owners, products or product models cannot be deleted. Deleting an owner deletes its external ids and flag assignments.

Each owner stores a materialized path, so "everything beneath Acme" is a single prefix query however deep the chain.

### Slugs

Every owner gets a `slug` when it's created, from the first of these that slugifies to something: its default-locale label, its `code`, or the word `owner`. `Classic` gives `classic`. Slugs are unique across all owners, take the first free `-2`, `-3`, … suffix on a collision, and are suffixed when they would read as a route segment, as product slugs are ([Slugs](04-products.md#slugs)). A slug never changes, even when the owner's labels, parent or code do. `slug` is read-only, so sending it on create or update is refused.

### Looking up an owner

API paths, Atrium links, MCP `owner` arguments and the show, update and delete actions find an owner by its ULID `id`. Set `showroom.lookup.owners` to `slug` to use slugs instead, or pick the key for one call with `?by=id` or `?by=slug` over HTTP and a `by` argument on the MCP tools. An owner's `code` is never a lookup key. It stays the reference for `parent`, a product's `owner`, and the `owner`, `parent` and `under` filters. Every owner resource returns `id`, `slug` and `code`.

### External ids and flags

Owners take `external_ids` (a `{source: id}` map) as products do, and return `external_ids_synced_at` beside it; see [External ids](04-products.md#external-ids). An id is unique per source among owners, an owner holds one id per source, and an owner may share an id with a product.

Owners take `flags` too, and pass them down. An owner's flags apply to every owner and product beneath it, and nothing beneath can drop them. Changing an owner's flags needs the `assign` ability and re-indexes every product beneath it. See [Flags](14-flags.md).

## Assigning products

Set `owner` on a simple product or a root product model, naming the deepest owner in the chain — the series, not the vendor. Sub-models and variant products take their root model's owner and cannot set their own; the API returns it as their `owner`.

## Searching by owner

`GET /showroom/products?owner=acme` finds every product owned by Acme **or anything beneath it**, on every search engine. The indexed document carries `owner`, `owners` (the whole chain of codes) and `owner_slugs` (the same chain by slug), and moving an owner, or changing its flags, re-indexes the products beneath it.

## Actions

`ListOwnerTypesAction`, `ShowOwnerTypeAction`, `CreateOwnerTypeAction`, `UpdateOwnerTypeAction`, `DeleteOwnerTypeAction`, `ListOwnersAction`, `ShowOwnerAction`, `CreateOwnerAction`, `UpdateOwnerAction`, `DeleteOwnerAction`.
