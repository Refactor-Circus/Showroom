VERDICT: PASS

Security review, round 1. No findings at or above minor.

- Mass assignment: records only reach the writer through validated CreateOwnerData/UpdateOwnerData rules (fromValidated); no raw record keys are stored.
- Authorization: the flags assign check is kept per record in OwnerWriter, as in the single actions; D-004 covers the rest.
- Injection: all lookups use whereIn/bound queries; no raw SQL.
- Data exposure: DB errors are reported without SQL; non-unique QueryExceptions go to report() only.
- Chunk input from queued payload is re-validated through UpsertOwnersData::from.
