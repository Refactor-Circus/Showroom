VERDICT: CHANGES

Code review, round 1. Gate passes (tests, phpstan, pint). Two minor findings in UpsertProductsAction; no blockers.

1. minor: write order across the bulk, per-record and update paths breaks the "first persists" rule for duplicate external ids (AC 14, 36). createAll() writes bulk creates first, then non-simple creates, then updateAll() runs last. If record 1 is non-simple (parent or associations) or an update, and record 3 is a simple create holding the same (source, id), record 3 persists and record 1 fails. An update that frees an id (null) for a later create in the same batch is also refused. Fix: claim external ids across the whole batch in validate(), in batch order, as $newIdentifiers does for identifiers, or write in index order.

2. minor: createOne() and updateAll() catch only ValidationException, AuthorizationException and ModelNotFoundException. A UniqueConstraintViolationException (identifier race, or Slugs::claim retry exhausted) or other QueryException on one record escapes execute() and drops the result and failures of the batch (AC 26, 39). Fix: catch the DB exception per record and report it as a failure at the record, keeping neighbours.
