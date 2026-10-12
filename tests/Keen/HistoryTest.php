<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Showroom\Mcp\Tools\ListShowroomHistoryTool;

it('offers its history while Showroom audits', function (): void {
    expect(app(PackageRegistry::class)->get('showroom')->audits())->toBeTrue();

    $this->getJson(route('showroom.history.index'))->assertOk();
    mcpTool(ListShowroomHistoryTool::class)->assertOk();
});

it('offers no history once showroom.audit.enabled is off', function (): void {
    config()->set('showroom.audit.enabled', false);

    $this->getJson(route('showroom.history.index'))->assertNotFound();
    mcpTool(ListShowroomHistoryTool::class)->assertHasErrors();
});
