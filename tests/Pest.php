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
    // The flag-definitions screen ships with the Atrium screens (spec group 10).
    'ListFlagsAction' => ['ui'],
    'ShowFlagAction' => ['ui'],
    'CreateFlagAction' => ['ui'],
    'UpdateFlagAction' => ['ui'],
    'DeleteFlagAction' => ['ui'],
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
