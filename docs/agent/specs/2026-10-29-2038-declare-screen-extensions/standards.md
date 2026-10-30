# Standards for Declare screen extensions (Showroom)

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | Atrium moves to require-dev and suggest; no inline checks for Impex or Keen in views |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | Screen keys and slots become a public extension surface |
| architecture/naming | vendor/refactor-circus/standards/standards/architecture/naming.md | refactor-circus/standards@dev-main | Screen keys and the `Atrium\Support\Screens` helper |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | The access closure asks Showroom's policies; pages authorize before resolving |
| showroom/atrium-screens | docs/agent/standards/showroom/atrium-screens.md | local | Atrium components only; its "Audit trail" section is replaced by screen declarations |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | New arch rule: no Atrium class outside src/Atrium |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Per-screen feature dataset, NoAtrium suite |
