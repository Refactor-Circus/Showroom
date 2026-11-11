VERDICT: PASS

Compat review: the change is additive. CreateOwnerAction and UpdateOwnerAction keep their public constructor parameters (names and types) and execute signatures; the moved-from properties were private. The PlacesOwners trait is still present. New events, data classes, the action and the showroom:upsert-owners flow add surface only; no config key, route or schema changes.
