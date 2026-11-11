# Attributes

An attribute is a typed product characteristic — color, weight, description — defined at runtime. [Families](03-families.md) say which attributes a kind of product has and which are required; attributes themselves carry only their type, labels and validation.

## Codes are permanent

Every catalog record is addressed by a **code**: lowercase letters, digits and underscores, starting with a letter (option codes may start with a digit, so sizes like `10` work). Codes are unique and never change once created, because integrations, product values and agents hold on to them. An attribute's **type** never changes either. Sending `code` or `type` to an update is a validation error.

## Types and settings

| Type | Settings | Can be unique |
|---|---|---|
| `text` | `max_length` (1–255), `regex` | yes |
| `textarea` | `max_length` (1–65535), `rich_text` | |
| `number` | `min`, `max` (integers) | yes |
| `decimal` | `min`, `max`, `decimals` (0–10) | yes |
| `boolean` | — | |
| `date` | `min`, `max` | yes |
| `select` | — | |
| `multiselect` | — | |
| `price` | `currencies` (ISO 4217, such as `USD`), `decimals` (0–4) | |
| `metric` | `metric_family`, `default_unit` — both required | |

Settings are validated against the type; keys a type does not understand are dropped. On update, `settings` replaces the whole map. Errors are reported under `settings.*`.

`AttributeType` exposes the same knowledge in code: `hasOptions()`, `canBeUnique()`, `settingsRules()` and `validateSettings()`.

## Flags

| Flag | Meaning |
|---|---|
| `is_unique` | Each product must hold a different value. Only for types that can be unique. |
| `is_localizable` | Values differ per locale; each value names an existing locale. |
| `is_scopable` | Values differ per channel; each value names an existing channel. See [Channels and locales](09-channels.md). |

## Labels

`labels` is a map of locale to label, `{"en": "Color", "fr": "Couleur"}`. Sending `labels` replaces the map. `$model->label()` returns the label in the current locale, then the fallback locale, then the code.

## Attribute groups

Groups organise attributes into sections such as `marketing` and `technical`. An attribute belongs to at most one group, set by the group's code (`"group": "technical"`, or `null` for none). A group that still holds attributes cannot be deleted — move them first.

## Options

Select and multiselect attributes take options. Their codes are unique within the attribute. Adding or listing options on any other type is refused. Deleting an attribute deletes its options and removes it from every family; an attribute that is a family's label attribute cannot be deleted.

### Batch upsert

`UpsertAttributeOptionsAction` writes a batch of option records, for catalog syncs that carry thousands of options across many attributes.

```php
use RefactorCircus\Showroom\Domains\Attribute\Actions\UpsertAttributeOptionsAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpsertAttributeOptionsData;

$result = app(UpsertAttributeOptionsAction::class)->execute(UpsertAttributeOptionsData::from([
    'records' => $records,  // [{"attribute": "color", "code": "red", "labels": {"en": "Red"}, "sort_order": 1}, ...]
    'mode' => 'upsert',     // or create, update
]));
```

- `attribute` is the code of a select or multiselect attribute, so one batch can span many attributes. A record whose attribute doesn't exist, or doesn't take options, fails at `attribute`.
- Records are matched to stored options by `attribute` and `code`. In `create` mode a matched record is skipped; in `update` mode an unmatched one is.
- Each record is validated with `CreateAttributeOptionData` or `UpdateAttributeOptionData` rules. Codes follow the option code rule (lowercase letters, digits and `_`), so a code in another case fails at `code` instead of matching. A field the record leaves out keeps its stored value; `"labels": null` clears the labels.
- Two records with the same `attribute` and `code` can't both be written. The first one keeps it, whatever becomes of it, and the later one fails at `code`.
- A record whose sent fields (`labels`, `sort_order`) match the stored option is `unchanged`. Nothing is written for it, and no event fires.
- New options are inserted in bulk, up to 200 rows per statement. Each fires the events `CreateAttributeOptionAction` fires, and the batch dispatches the Eloquent `saving`, `creating`, `created` and `saved` events for it, which a bulk insert skips (they can't cancel the insert). If the bulk insert fails, the new options are inserted one by one, so only the record that broke it fails.
- Each changed option is saved in its own transaction and fires `UpdateAttributeOptionAction`'s events. A record that fails rolls back alone.
- Product values store option codes, and the search document holds no option labels, so no product is re-indexed, as with a single update.
- The batch checks no permission per record: its caller authorizes creating and updating options.
- The action expects at most `showroom.impex.chunk` records per call, and reads what it needs in lookups of that size. Send larger batches in chunks, or run them through the `showroom:upsert-attribute-options` flow ([Impex](12-impex.md)).

A batch costs a fixed two queries (its attributes and its matched options), plus one insert per 200 new options and one update per changed option. An all-unchanged batch costs the same two queries at any size.

The result, an `UpsertAttributeOptionsResult`, counts `created`, `updated`, `unchanged` and `skipped`. `failures` lists each refused record by its `index` in the batch, with its `attribute`, its `code` and its `errors` by field. The batch fires `AttributeOptionsUpsertingActionEvent` before and `AttributeOptionsUpsertedActionEvent` after, with the result.

## Actions

| Action | |
|---|---|
| `ListAttributeGroupsAction`, `ShowAttributeGroupAction`, `CreateAttributeGroupAction`, `UpdateAttributeGroupAction`, `DeleteAttributeGroupAction` | Groups |
| `ListAttributesAction`, `ShowAttributeAction`, `CreateAttributeAction`, `UpdateAttributeAction`, `DeleteAttributeAction` | Attributes |
| `ListAttributeOptionsAction`, `CreateAttributeOptionAction`, `UpdateAttributeOptionAction`, `DeleteAttributeOptionAction`, `UpsertAttributeOptionsAction` | Options |

Each input-taking Action takes a Data object from `RefactorCircus\Showroom\Domains\Attribute\Data` (`CreateAttributeData`, `UpdateAttributeData`, ...), whose static `rules()` is the input contract the HTTP API, MCP tools and dashboard all validate against. Build one with `XData::from([...])`, which validates, or `new XData(...)`.
