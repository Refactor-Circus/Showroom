<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Atrium\Testing\ResetsScreenExtensions;
use RefactorCircus\Keystone\Testing\McpTools;
use RefactorCircus\Keystone\Testing\Parity;
use RefactorCircus\Showroom\Mcp\ShowroomServer;
use RefactorCircus\Showroom\Tests\CortexTestCase;
use RefactorCircus\Showroom\Tests\KeenTestCase;
use RefactorCircus\Showroom\Tests\PennantPlusTestCase;
use RefactorCircus\Showroom\Tests\RenamedTablesTestCase;
use RefactorCircus\Showroom\Tests\TestCase;

// These test cases use Atrium. Without it (the CI without-atrium lane) only
// tests/WithoutAtrium runs, and loading them would fail on the missing classes.
if (trait_exists(ResetsScreenExtensions::class)) {
    uses(TestCase::class)->in('Feature', 'Unit');
    uses(CortexTestCase::class)->in('Cortex');
    uses(PennantPlusTestCase::class)->in('PennantPlus');
    uses(KeenTestCase::class)->in('Keen');
    uses(RenamedTablesTestCase::class)->in('Modes/RenamedTables');
}

/**
 * Call a catalog tool as an MCP client does, through tool search, with its
 * answer checked against the tool's output schema.
 *
 * @param  class-string<Tool>  $tool
 * @param  array<string, mixed>  $arguments
 */
function mcpTool(string $tool, array $arguments = []): TestResponse
{
    return McpTools::call(ShowroomServer::class, $tool, $arguments);
}

/**
 * Actions deliberately missing from a surface, and why.
 *
 * Every use case must be reachable from the HTTP API, MCP and the Atrium
 * dashboard. An Action counts as reachable when its class name appears in
 * that surface's request or controller classes.
 *
 * @var array<string, array<int, string>>
 */
const PARITY_EXCEPTIONS = [
    // Batch upserts run through the Impex import flows; their own HTTP and
    // MCP endpoints are out of scope (bulk import readiness spec).
    'UpsertProductsAction' => ['http', 'mcp', 'ui'],
    // The same for owners (batch owner upsert spec).
    'UpsertOwnersAction' => ['http', 'mcp', 'ui'],
    // The same for attribute options (batch attribute option upsert).
    'UpsertAttributeOptionsAction' => ['http', 'mcp', 'ui'],
    // Not on the dashboard yet; reachable over the API and MCP.
    'ShowAssociationTypeAction' => ['ui'],
    'UpdateAssociationTypeAction' => ['ui'],
    'ListAttributeOptionsAction' => ['ui'],
    'UpdateAttributeOptionAction' => ['ui'],
    'ShowLocaleAction' => ['ui'],
    'UpdateLocaleAction' => ['ui'],
    'ListFamilyVariantsAction' => ['ui'],
    'UpdateFamilyVariantAction' => ['ui'],
    'ShowProductVersionAction' => ['ui'],
    // The figures the dashboard widgets showed, now served to Atrium v2
    // over the API; the screens that would reach it are being removed.
    'ShowCatalogStatsAction' => ['ui'],
    // The same for the search the dashboard's command palette ran.
    'SearchCatalogAction' => ['ui'],
    // The settings summary describes host configuration for an admin panel
    // and adds nothing for an MCP client (D-006); the dashboard's settings
    // panel that showed it is being removed.
    'ShowSettingsSummaryAction' => ['mcp', 'ui'],
];

/**
 * Actions missing from a surface, keyed by surface.
 *
 * @return array<string, array<int, string>>
 */
function parityGaps(): array
{
    $root = dirname(__DIR__).'/src';

    return Parity::gaps($root.'/Domains/*/Actions/*.php', [
        'http' => $root.'/Domains/*/Http/Requests/*.php',
        'mcp' => $root.'/Domains/*/Mcp/Requests/*.php',
        'ui' => $root.'/Atrium/Http/Controllers/*.php',
    ], PARITY_EXCEPTIONS);
}
