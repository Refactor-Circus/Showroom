## D-001: Create the GitHub issue as drafted in ticket-draft.md, or redraft it?
- Choice: Create as drafted
- Rationale: none given (answered in RingMaster)
- Date: 2026-10-28
- Raised by: ticket

## D-002: Approve this spec as written so the factory can build it?
- Choice: Approve the spec
- Rationale: none given (answered in RingMaster)
- Date: 2026-10-28
- Raised by: approval

## D-003: Task 1.3 needs the sibling Impex checkout (2 commits behind origin/main, still declares conflict brianium/paratest >=7.26.1). How should the dependency update proceed?
- Choice: Fast-forward the Impex sibling (git -C /Users/jay/Projects/packages/Impex pull --ff-only, origin/main already drops the pin), then rerun `composer run-script local:update -- pestphp/pest brianium/paratest --with-dependencies` in the worktree so only Pest packages change and the symlinks stay
- Rationale: none given (answered in RingMaster)
- Date: 2026-10-28
- Raised by: build
