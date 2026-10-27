# Tech Stack

Mirrors the `refactor-circus/impex` package (`../impex`), which serves as the standards template.

## Frontend

- `refactor-circus/atrium` — dashboard shell and component library; Showroom registers as an Atrium plugin (navigation, pages, settings, widgets, search).

## Backend

- PHP `^8.5`
- Laravel `^13` (`laravel/framework`)
- Laravel package conventions: service provider wiring, publishable config/migrations, publish tags under `showroom-*`
- Action classes shared by HTTP API and MCP (one Action per operation)
- `laravel/mcp` — MCP server with HTTP API parity
- `refactor-circus/cortex` (suggested) — agent tool registry and versioned MCP instructions/descriptions

## Database

- Database-agnostic via Eloquent/migrations (MySQL, PostgreSQL; SQLite in tests)

## Other

- Vapor/Lambda-friendly: media on a configurable filesystem disk (S3), no local disk, cache/lock store configurable (Redis/DynamoDB)
- `refactor-circus/impex` for import/export flows
- Testing: Pest 5, `pestphp/pest-plugin-laravel`, `pestphp/pest-plugin-type-coverage`, Orchestra Testbench 11 (workbench)
- Quality: Larastan, Laravel Pint, `laravel/pao`
