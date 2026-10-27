# Showroom Foundation + Attributes — Plan

## Context

Showroom (`refactor-circus/showroom`) is a Laravel-native PIM. Product docs (`agent-os/product/`) define the mission and roadmap, but the package is still a skeleton: placeholder config, route, migration, view, command and example tests. This spec starts roadmap Phase 1 / Milestone 1 (Catalog core) with:

1. **Foundation** — replace placeholders with the architecture mirrored from `../impex` (standards template): Actions shared by HTTP + MCP, Authorizer/policies, MCP server behind ToolSearch, optional Cortex integration, Atrium plugin, action/model events, config, test harness with parity arch test.
2. **First vertical slice: attributes** — attribute groups, typed attributes, attribute options, each shipped through Actions + HTTP API + MCP tools + Atrium pages + tests.

Families, products/variants, ownership come in later specs on top of this foundation.

Shaping decisions: scope = foundation + attributes; no visuals; reference = `../impex`; aligned with product docs as-is; core attribute type set; standards index empty → discover standards afterwards.

---

## Task 1: Save spec documentation

Create `agent-os/specs/2026-09-29-0453-foundation-and-attributes/`:
- **plan.md** — this plan
- **shape.md** — scope, decisions (below), context, product alignment
- **standards.md** — note: no standards indexed yet; conventions come from `../impex` + CLAUDE.md; follow-up task to discover
- **references.md** — `../impex` pattern map (provider, Actions, Http, Mcp, Atrium, Cortex, models, config, tests, docs) with paths
- no `visuals/`

## Task 2: Remove skeleton placeholders

Delete: `config` `placeholder` key, `routes/showroom.php` placeholder comment, `database/migrations/2026_01_01_000000_create_showroom_placeholder_table.php`, `resources/views/placeholder.blade.php`, `src/Console/Commands/ShowroomCommand.php` (+ provider registration), `tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php`. Keep `Showroom` class + Facade (entry point, can stay thin).

## Task 3: Foundation — config + service provider

`config/showroom.php` with Impex boxed-header sections:
- `authorization` (true), `policies` (model ⇒ policy map)
- `routes` `{enabled: true, prefix: 'showroom', middleware: ['api']}`
- `mcp` `{web: {enabled: false, route: 'mcp/showroom', middleware: []}, local: {enabled: false, handle: 'showroom'}}`
- `cortex` `{enabled: true, server: 'showroom', tools: null}`
- `ui` `{enabled: true}`
- `pagination` `{per_page: 25, max_per_page: 100}`

`src/ShowroomServiceProvider.php` boot order mirrors `../impex/src/ImpexServiceProvider.php`: Cortex register → policies → routes (config-gated) → Atrium plugin (`ui.enabled`) → MCP server (`Mcp::web`/`Mcp::local`, config-gated) → views/lang → console-only publishes (keep `showroom`, `showroom-*` tags). Add `extra.atrium.plugins` to `composer.json`.

## Task 4: Foundation — shared plumbing

Mirror impex classes, `RefactorCircus\Showroom` namespace, `declare(strict_types=1)`, `final` where impex is final:
- `src/Access/Authorizer.php` — `enabled/authenticated/actor/can` (Gate-backed, open when authorization off)
- `src/Policies/Policy.php` — abstract base
- `src/Contracts/{ActionStartingEvent,ActionFinishedEvent(ShouldDispatchAfterCommit),ModelLifecycleEvent}.php`
- `src/Models/Concerns/DispatchesModelEvents.php`
- `src/Exceptions/ShowroomException.php` (base, messages surfaced by MCP/HTTP)
- `src/Http/Request.php` — abstract FormRequest with `persist()`, `actor()`, `allows()`
- `src/Mcp/{ShowroomServer,Tool,Request}.php` — server `#[Name('Showroom')]`, `TOOLS` const catalog behind `ToolSearch`; Tool description override via Cortex; Request `persist()` with error mapping (Unauthorized / Not found / ShowroomException / validation)
- `src/Cortex/CortexIntegration.php` — `active()` guard, lazy registry registration, instructions/description overrides
- `src/Atrium/ShowroomPlugin.php` — key/label, nav group "Catalog", routes under `atrium.showroom.*`, settings panel, search source
- `lang/en/showroom.php` — all UI strings

## Task 5: Test harness

- `tests/TestCase.php` — providers `[McpServiceProvider, AtriumServiceProvider, ShowroomServiceProvider]`, `showroom.authorization=false`, sqlite w/ FKs, load package migrations
- `tests/CortexTestCase.php` + `Cortex` suite in `phpunit.xml.dist`
- `tests/Pest.php` — `mcpTool()` helper (FakeTransporter → `execute_tools`), `MCP_EXCEPTIONS`, `parityGaps()`
- `tests/ArchTest.php` — add: models final, every Action reachable from MCP (`parityGaps()` empty)
- `testbench.yaml` + workbench provider: Atrium provider, `viewAtrium` gate open

## Task 6: Attribute domain — schema + models + enums

Migration `database/migrations/2026_01_01_000001_create_showroom_attribute_tables.php` (anonymous class, ULIDs):
- `showroom_attribute_groups`: id, `code` unique, `labels` json (locale ⇒ label), `sort_order`, timestamps
- `showroom_attributes`: id, `code` unique, `type` string(32), `attribute_group_id` nullable FK (restrict), `labels` json (required-ness lives on families, not here), `is_unique` bool, `is_localizable` bool, `is_scopable` bool (stored now, used in milestone 4), `settings` json (type-specific validation), `sort_order`, timestamps
- `showroom_attribute_options`: id, `attribute_id` FK cascade, `code` (unique per attribute), `labels` json, `sort_order`, timestamps

`src/Enums/AttributeType.php` — `Text, Textarea, Number, Decimal, Boolean, Date, Select, Multiselect, Price, Metric`; helpers `hasOptions()`, `settingsRules()` (e.g. text `max_length`/`regex`; number/decimal `min`/`max`/`decimals`; price `currencies`; metric `metric_family`/`default_unit`).

Models `AttributeGroup`, `Attribute`, `AttributeOption` — final, `HasUlids`, `HasFactory`, `DispatchesModelEvents`, explicit `$fillable`, `casts()`, typed relations, hardcoded `showroom_*` tables. Factories in `database/factories/`.

Domain rules:
- `code`: `^[a-z][a-z0-9_]*$`, max 100, immutable after create
- `type`: immutable after create
- options only for `Select`/`Multiselect` (else `ShowroomException`)
- deleting a group with attributes → `ShowroomException` (PIM convention: reassign first)

## Task 7: Attribute Actions + events

`src/Actions/` (final, static `rules()`, `execute()` dispatches start/finish events):
- Groups: `List/Show/Create/Update/DeleteAttributeGroupAction`
- Attributes: `List/Show/Create/Update/DeleteAttributeAction` (list filters: `type`, `group`, `search`, cursor pagination)
- Options: `List/Create/Update/DeleteAttributeOptionAction`

Event pairs in `src/Events/Action/` (`AttributeCreatingActionEvent` / `AttributeCreatedActionEvent`, …). Model lifecycle events in `src/Events/Model/`. Policies `AttributeGroupPolicy`, `AttributePolicy`, `AttributeOptionPolicy` (non-final) registered via config.

## Task 8: HTTP API

- `routes/showroom.php` — prefix/middleware from config, names `showroom.attribute-groups.*`, `showroom.attributes.*`, `showroom.attributes.options.*`; ULID implicit binding
- `src/Http/Requests/*Request.php` — `rules()` delegates to Action, `persist()` calls Action
- `src/Http/Controllers/{AttributeGroup,Attribute,AttributeOption}Controller.php` — one-liners
- `src/Http/Resources/{AttributeGroup,Attribute,AttributeOption}Resource.php` — enums `->value`, ISO dates, `whenLoaded`

## Task 9: MCP tools

`src/Mcp/Requests/*McpRequest.php` (Action rules + id params) and `src/Mcp/Tools/*Tool.php` (hand-written schema, `#[Description]`), one per Action, added to `ShowroomServer::TOOLS`. Resources reused via `->resolve()`; list uses `structuredCollection` + `next_cursor`.

## Task 10: Atrium pages

`src/Http/Ui/*UiController.php` calling same Actions; views `resources/views/ui/{attribute-groups,attributes}/{index,show,form}.blade.php` with `x-atrium::*` components; options managed on attribute show page. Nav items + search source (attributes by code/label).

## Task 11: Tests

- `tests/Feature/Api/{AttributeGroup,Attribute,AttributeOption}ApiTest.php` — CRUD, validation, immutability, group-delete guard, options-on-non-select guard
- `tests/Feature/McpTest.php` — tool per action, same-rules validation, parity behaviour
- `tests/Feature/EventsTest.php` — every Action dispatches one start + one finish event; action count asserted
- `tests/Feature/PolicyTest.php` — authorization on, swapped policy via config
- `tests/Feature/Ui/{ShowroomPluginTest,AttributePagesTest}.php`
- `tests/Cortex/CortexTest.php` — server/tool registration, config filtering, overrides

## Task 12: Docs + Boost skill

- `README.md` — pitch, status, install (`showroom-migrations`, `showroom-config`), HTTP API, MCP, Cortex, Dashboard, Events, Roadmap
- `docs/` numbered guides: `01-installation`, `02-attributes`, `09-api`, `10-mcp`, `11-dashboard`, `13-configuration`
- `CHANGELOG.md` Unreleased entry
- Update `resources/boost/skills/showroom-development/SKILL.md` via `package-generate-skill`

## Task 13: Follow-up

Run `/agent-os:discover-standards` to capture the conventions established here into `agent-os/standards/`.

---

## Verification

- `composer test` — phpstan, pint `--test`, type coverage 100%, Pest parallel (incl. arch parity test)
- `composer build && composer serve` → visit `/atrium/showroom/attributes`, create group/attribute/options through UI
- Hit `GET /showroom/attributes` via HTTP; call `create-attribute-tool` through MCP `execute_tools` in tests and confirm same validation errors as HTTP
