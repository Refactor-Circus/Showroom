# Families

A family — an attribute set — is a kind of product: the attributes it has and which of them are required. "Shoes" has size and color; "Laptops" has memory and screen size. Products (coming next) each belong to one family.

## Membership

`attributes` is the family's whole attribute list:

```json
{
  "code": "shoes",
  "labels": {"en": "Shoes"},
  "attributes": [
    {"attribute": "name", "is_required": true},
    {"attribute": "size", "is_required": true},
    {"attribute": "color"}
  ],
  "label_attribute": "name"
}
```

- On update, `attributes` **replaces** the whole list. Leave it out to keep the list as it is.
- Entries are shown in the order given, unless an entry sets its own `sort_order`.
- An attribute may appear in any number of families, and at most once in each.
- `is_required` makes an attribute required on every channel; add `required_channels` to narrow it to some. Requirements drive [completeness](11-workflow.md).

## Label attribute

`label_attribute` names the text attribute whose value is each product's display label. It must be a `text` attribute in the family. Removing it from the family's attributes without also changing `label_attribute` is a validation error.

## Deleting

- Deleting a family leaves its attributes in place.
- Deleting an attribute removes it from every family — unless it is some family's label attribute, which is refused with `409` until another label attribute is chosen.

## Actions

`ListFamiliesAction` (filters: `search`, `attribute`), `ShowFamilyAction`, `CreateFamilyAction`, `UpdateFamilyAction`, `DeleteFamilyAction`. `CreateFamilyData::from([...])` / `UpdateFamilyData::from([...])` take `attributes` as above and hold each entry as a `FamilyAttributeData` (`attribute`, `isRequired`, `sortOrder`, `requiredChannels`); a family variant's `levels` are `FamilyLevelData` (`axes`, `attributes`).
