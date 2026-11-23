<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Keystone\Testing\McpTools;
use RefactorCircus\Keystone\Testing\Parity;
use RefactorCircus\Showroom\Mcp\ShowroomServer;
use RefactorCircus\Showroom\Tests\CortexTestCase;
use RefactorCircus\Showroom\Tests\KeenTestCase;
use RefactorCircus\Showroom\Tests\OpenApiTestCase;
use RefactorCircus\Showroom\Tests\RenamedTablesTestCase;
use RefactorCircus\Showroom\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
uses(CortexTestCase::class)->in('Cortex');
uses(KeenTestCase::class)->in('Keen');
uses(RenamedTablesTestCase::class)->in('Modes/RenamedTables');
uses(OpenApiTestCase::class)->in('OpenApi');

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
 * Every use case must be reachable from the HTTP API and MCP. An Action
 * counts as reachable when its class name appears in that surface's request
 * classes.
 *
 * @var array<string, array<int, string>>
 */
const PARITY_EXCEPTIONS = [
    // Batch upserts run through the Impex import flows; their own HTTP and
    // MCP endpoints are out of scope (bulk import readiness spec).
    'UpsertProductsAction' => ['http', 'mcp'],
    // The same for owners (batch owner upsert spec).
    'UpsertOwnersAction' => ['http', 'mcp'],
    // The same for attribute options (batch attribute option upsert).
    'UpsertAttributeOptionsAction' => ['http', 'mcp'],
    // The settings summary describes host configuration for an admin panel
    // and adds nothing for an MCP client (D-006).
    'ShowSettingsSummaryAction' => ['mcp'],
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
    ], PARITY_EXCEPTIONS);
}
