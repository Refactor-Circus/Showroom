# Flags

Flags are commercial markers, such as `restricted` or `pricing_only`, that you define at runtime and assign to products and owners. An owner's flags carry down its whole tree: a flag on a vendor applies to every series beneath it and to every product they own.

## Defining flags

```json
{"code": "pricing_only", "labels": {"en": "Pricing only"}, "applies_to": ["product", "owner"], "sort_order": 10}
```

| Field | Meaning |
|---|---|
| `code` | Unique, lowercase letters, digits and underscores, starting with a letter (at most 100 characters). Never changes; sending it on update is refused. |
| `labels` | Display labels keyed by locale. |
| `applies_to` | The kinds of record the flag may be assigned to: a non-empty list of `product`, `owner` or both. |
| `sort_order` | Flags are listed by `sort_order`, then `code`. Default `0`. |

Two guards keep assigned flags in place:

- Deleting a flag that any product or owner carries fails with a validation error at `flag`. Remove it from those records first. Assignments left behind by a record that no longer exists don't block the delete.
- Taking `product` or `owner` out of `applies_to` fails at `applies_to` while the flag is assigned to a record of that kind.

## Assigning flags

Products and owners take `flags`, a list of flag codes, on create and update. The list replaces the record's own set. Send `[]` to clear it, or leave `flags` out to keep it.

```http
PATCH /showroom/owners/01JC...
{"flags": ["pricing_only"]}
```

An unknown code, or a flag whose `applies_to` doesn't include the record's kind, fails validation at `flags.{n}`.

### The `assign` ability

Changing a record's flags needs the `assign` ability of `FlagPolicy` (`showroom.policies`), on top of `update` on the record. It is asked only when the set changes: a request that sends the flags the record already has needs only `update`. Without `assign`, a flag change answers `403` over HTTP and an error over MCP, and nothing is written. In a batch upsert, the record fails at `flags` and the others are still written. The Atrium product and owner screens hide the own-flags field from users without `assign`.

The bundled policy allows any authenticated user. Flags such as `restricted` carry down whole owner trees, so point `FlagModel` at a policy of your own that limits `assign` to the people who set commercial terms:

```php
'policies' => [
    \RefactorCircus\Showroom\Domains\Flag\Models\FlagModel::class => \App\Policies\CatalogFlagPolicy::class,
],
```

### Trusted system syncs

A queued import or an ERP sync runs with no signed-in user, so with `showroom.authorization` on, `assign` refuses every flag it sends. Wrap such a sync in `Flags::trusted()`, and every flag assignment inside it is allowed:

```php
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;

$result = Flags::trusted(fn () => app(UpsertOwnersAction::class)->execute($data));
```

It returns what the callback returns. The mode ends when the callback returns or throws, so nothing carries over to the next job or Octane request, and calls nest. Only `assign` is skipped: the sync's caller still decides whether it may create and update records.

Use it only for code that vouches for the flags it writes, such as imports and ERP syncs. Never call it while handling a request, since anyone reaching that code could set any record's flags.

## Inheritance

A record has its own flags (`flags`) and its effective flags (`effective_flags`):

- An owner's effective flags are its own plus the flags of every owner above it.
- A product's effective flags are its own plus the effective flags of its owner. A variant product follows its root product model's owner.
- Nothing beneath can drop an inherited flag. There is no negation.

If a vendor carries `pricing_only` and its series and product carry none, the product's `effective_flags` include `pricing_only`.

Product and owner resources, and their MCP tools, return both lists as flag codes in flag order. `effective_flags` is read-only: sending it on create or update is refused, so strip it from a resource you send back.

## Searching by flag

The `flags[]=` filter keeps products whose effective flags include every flag named, such as `GET /showroom/products?flags[]=restricted&flags[]=pricing_only`, on the database and Scout engines. `GET /showroom/owners?flags[]=restricted` does the same for owners. See [Search](05-search.md).

Changing an owner's flags, or moving an owner, queues one `QueueProductSyncs` job that re-indexes every product beneath it. For an owner near the top of a large tree, that means many products. Sending the same flags again queues nothing.

## Endpoints and tools

| Method | Path | Action | MCP tool |
|---|---|---|---|
| `GET` | `/showroom/flags` | `ListFlagsAction` — `search`, `cursor`, `per_page` | `list-flags-tool` |
| `POST` | `/showroom/flags` | `CreateFlagAction` → `201` | `create-flag-tool` |
| `GET` | `/showroom/flags/{flag}` | `ShowFlagAction` | `show-flag-tool` |
| `PATCH` | `/showroom/flags/{flag}` | `UpdateFlagAction` — labels, `applies_to`, sort order | `update-flag-tool` |
| `DELETE` | `/showroom/flags/{flag}` | `DeleteFlagAction` → `204` | `delete-flag-tool` |

Flags are addressed by `code` in paths and in the tools' `flag` argument. The `showroom.lookup` keys apply to products and owners only.

In Atrium, the flag-definitions screen (`showroom.flags`, linked from the product and owner lists) lists, creates, edits and deletes flags. `code` is read-only on edit, and a refused delete or `applies_to` change shows the refusal message.

Assignments are stored in `showroom_flag_assignments` (`showroom.tables.flag_assignments`) and the definitions in `showroom_flags` (`showroom.tables.flags`).
