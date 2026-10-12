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

## Actions

| Action | |
|---|---|
| `ListAttributeGroupsAction`, `ShowAttributeGroupAction`, `CreateAttributeGroupAction`, `UpdateAttributeGroupAction`, `DeleteAttributeGroupAction` | Groups |
| `ListAttributesAction`, `ShowAttributeAction`, `CreateAttributeAction`, `UpdateAttributeAction`, `DeleteAttributeAction` | Attributes |
| `ListAttributeOptionsAction`, `CreateAttributeOptionAction`, `UpdateAttributeOptionAction`, `DeleteAttributeOptionAction` | Options |

Each input-taking Action takes a Data object from `RefactorCircus\Showroom\Domains\Attribute\Data` (`CreateAttributeData`, `UpdateAttributeData`, ...), whose static `rules()` is the input contract the HTTP API, MCP tools and dashboard all validate against. Build one with `XData::from([...])`, which validates, or `new XData(...)`.
