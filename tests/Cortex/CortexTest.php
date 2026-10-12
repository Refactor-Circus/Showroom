<?php

declare(strict_types=1);

use Laravel\Ai\Contracts\Tool as AgentTool;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Cortex\Domains\McpServer\Actions\CreateMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\McpServer\Data\CreateMcpInstructionVersionData;
use RefactorCircus\Cortex\Domains\McpServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Actions\CreateToolDescriptionVersionAction;
use RefactorCircus\Cortex\Domains\Tool\Data\CreateToolDescriptionVersionData;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Keystone\Cortex\CortexIntegration;
use RefactorCircus\Keystone\Mcp\Server;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ListAttributesTool;
use RefactorCircus\Showroom\Mcp\ShowroomServer;

it('registers the MCP server with Cortex', function (): void {
    $servers = app(McpServerRegistry::class);

    expect($servers->has('showroom'))->toBeTrue()
        ->and($servers->get('showroom'))->toBe(ShowroomServer::class)
        ->and($servers->defaultInstructions('showroom'))->toStartWith('Manage the Showroom product catalog');
});

it('offers every Showroom tool to Cortex agents under its own name, the history tool only while Showroom audits', function (): void {
    $tools = app(ToolRegistry::class);

    $names = array_map(fn (string $class): string => app($class)->name(), Server::withoutHistory(ShowroomServer::TOOLS));

    expect(ShowroomServer::TOOLS)->toHaveCount(78)
        ->and(array_diff($names, $tools->names()))->toBe([])
        ->and($tools->names())->not->toContain('list-showroom-history-tool')
        ->and($tools->get('list-attributes-tool'))->toBeInstanceOf(AgentTool::class)
        ->and($tools->tagsFor('list-attributes-tool'))->toContain('showroom');
});

it('offers only the tools listed in config', function (): void {
    config()->set('showroom.cortex.tools', ['list-attributes-tool', 'show-attribute-tool']);
    app()->forgetInstance(ToolRegistry::class);

    $tools = app(ToolRegistry::class);

    expect($tools->has('list-attributes-tool'))->toBeTrue()
        ->and($tools->has('show-attribute-tool'))->toBeTrue()
        ->and($tools->has('create-attribute-tool'))->toBeFalse();
});

it('serves the instructions published in Cortex', function (): void {
    app(CreateMcpInstructionVersionAction::class)->execute('showroom', new CreateMcpInstructionVersionData(content: 'Never delete attributes.', publish: true));

    expect((new ShowroomServer(new FakeTransporter))->createContext()->instructions)->toBe('Never delete attributes.');
});

it('serves tool descriptions published in Cortex, to MCP clients and agents alike', function (): void {
    app(CreateToolDescriptionVersionAction::class)->execute('list-attributes-tool', new CreateToolDescriptionVersionData(content: 'List the attributes of our storefront catalog.', publish: true));

    expect(app(ListAttributesTool::class)->description())->toBe('List the attributes of our storefront catalog.')
        ->and(app(ToolRegistry::class)->get('list-attributes-tool')->description())->toBe('List the attributes of our storefront catalog.');
});

it('lets an agent call an Showroom tool with its arguments', function (): void {
    // The tool takes its own request class; without the arguments it could
    // not look the attribute up at all.
    $result = (string) app(ToolRegistry::class)->get('show-attribute-tool')->handle(new Request(['attribute' => 'missing']));

    expect($result)->toContain('Not found.');
});

it('stays out of Cortex when turned off', function (): void {
    $integration = CortexIntegration::for(app(PackageRegistry::class)->get('showroom'));

    expect($integration->active())->toBeTrue();

    config()->set('showroom.cortex.enabled', false);

    expect($integration->active())->toBeFalse()
        ->and($integration->instructions())->toBeNull()
        ->and($integration->description('list-attributes-tool'))->toBeNull();
});
