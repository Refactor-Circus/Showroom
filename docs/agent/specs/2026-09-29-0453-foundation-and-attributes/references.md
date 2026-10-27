# References for Foundation + Attributes

## Similar Implementations

### Impex service provider

- **Location:** `../impex/src/ImpexServiceProvider.php`
- **Relevance:** Boot order and conditional wiring Showroom mirrors.
- **Key patterns:** Cortex register → policies from config → config-gated routes → `Atrium::plugin()` when `ui.enabled` → `Mcp::web` / `Mcp::local` when enabled → views/lang → console-only publishes with umbrella + specific tags.

### Actions

- **Location:** `../impex/src/Actions/*Action.php`
- **Relevance:** Single implementation shared by HTTP, MCP and UI.
- **Key patterns:** `final`, no base class, static `rules()` as the input contract, `execute(Model..., array $data, context...)`; start/finish action events (`src/Events/Action/`, contracts `ActionStartingEvent` / `ActionFinishedEvent`); list actions return `CursorPaginator`.

### HTTP layer

- **Location:** `../impex/src/Http/{Request.php,Requests,Controllers,Resources}`, `../impex/routes/impex.php`
- **Relevance:** API surface.
- **Key patterns:** abstract `Request extends FormRequest` with `persist()`; requests delegate `rules()` to the Action; one-line controllers; final `JsonResource`s reused by MCP; route prefix/middleware from config; names `impex.*`.

### MCP layer

- **Location:** `../impex/src/Mcp/{ImpexServer.php,Tool.php,Request.php,Tools,Requests}`
- **Relevance:** MCP parity with HTTP.
- **Key patterns:** `TOOLS` const catalog behind `ToolSearch`; Tool description overridable by Cortex; `Request::persist()` maps Unauthorized / Not found / package exception; `structuredCollection()` for lists; hand-written schemas.

### Access & policies

- **Location:** `../impex/src/Access/Authorizer.php`, `../impex/src/Policies/`
- **Key patterns:** shared authorizer, open when `authorization` is false; non-final policies mapped in config.

### Atrium plugin

- **Location:** `../impex/src/Atrium/ImpexPlugin.php`, `../impex/src/Http/Ui/`, `../impex/resources/views/ui/`
- **Key patterns:** nav items, plugin routes under `atrium.impex.*`, UI controllers calling the same Actions, settings panel, search source, `x-atrium::*` components, strings in `lang/en/impex.php`.

### Cortex integration

- **Location:** `../impex/src/Cortex/CortexIntegration.php`
- **Key patterns:** `active()` guard (config + class + provider loaded); lazy registration through `afterResolving`; instruction/description overrides.

### Models & migrations

- **Location:** `../impex/src/Models/`, `../impex/database/migrations/`, `../impex/database/factories/`
- **Key patterns:** final models, `HasUlids`, `HasFactory`, `DispatchesModelEvents`, explicit `$fillable`, `casts()`, hardcoded table names, anonymous-class migrations grouped per domain.

### Tests

- **Location:** `../impex/tests/{TestCase.php,CortexTestCase.php,Pest.php,ArchTest.php,Feature}`
- **Key patterns:** `mcpTool()` helper over `execute_tools`, `parityGaps()` arch rule, events test globbing every Action, policy test with authorization on, Atrium UI tests.

### Docs

- **Location:** `../impex/README.md`, `../impex/docs/`, `../impex/resources/boost/skills/impex-development/`
- **Key patterns:** numbered guides, configuration key tables, MCP tool catalog table.
