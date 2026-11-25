<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use RefactorCircus\Showroom\Domains\Catalog\Actions\ShowSettingsSummaryAction;
use RefactorCircus\Showroom\Domains\Catalog\Events\SettingsSummaryShowingActionEvent;
use RefactorCircus\Showroom\Domains\Catalog\Events\SettingsSummaryShownActionEvent;
use RefactorCircus\Showroom\Domains\Catalog\Resources\SettingsSummaryResource;

it('sums up the shipped configuration', function (): void {
    config()->set('showroom.authorization', true);

    expect(app(ShowSettingsSummaryAction::class)->execute())->toBe([
        'authorization' => true,
        'routes' => ['enabled' => true, 'prefix' => 'showroom', 'middleware' => ['api']],
        'mcp' => [
            'web' => ['enabled' => false, 'route' => 'mcp/showroom', 'middleware' => []],
            'local' => ['enabled' => false, 'handle' => 'showroom'],
        ],
        'pagination' => ['per_page' => 25, 'max_per_page' => 100],
    ]);
});

it('casts every value, as the package reads it, rather than passing config through', function (): void {
    config()->set('showroom.authorization', 'yes');
    config()->set('showroom.routes', ['enabled' => 1, 'prefix' => 42, 'middleware' => 'api', 'extra' => 'dropped']);
    config()->set('showroom.mcp', ['web' => ['enabled' => true, 'route' => 'mcp/catalog', 'middleware' => ['auth:sanctum', 7]]]);
    config()->set('showroom.pagination', ['per_page' => '30', 'max_per_page' => 'many']);

    expect(app(ShowSettingsSummaryAction::class)->execute())->toBe([
        // Only `true` turns a switch on, as the package reads it.
        'authorization' => false,
        'routes' => ['enabled' => false, 'prefix' => '42', 'middleware' => ['api']],
        'mcp' => [
            'web' => ['enabled' => true, 'route' => 'mcp/catalog', 'middleware' => ['auth:sanctum']],
            'local' => ['enabled' => false, 'handle' => ''],
        ],
        // A number that is no number falls back to the default, as listings do.
        'pagination' => ['per_page' => 30, 'max_per_page' => 100],
    ]);
});

it('answers the summary through its resource unchanged', function (): void {
    $summary = app(ShowSettingsSummaryAction::class)->execute();

    expect((new SettingsSummaryResource($summary))->resolve(request()))->toBe($summary);
});

it('fires the showing and shown events around the read', function (): void {
    Event::fake([SettingsSummaryShowingActionEvent::class, SettingsSummaryShownActionEvent::class]);

    $summary = app(ShowSettingsSummaryAction::class)->execute();

    Event::assertDispatched(SettingsSummaryShowingActionEvent::class);
    Event::assertDispatched(SettingsSummaryShownActionEvent::class, fn (SettingsSummaryShownActionEvent $event): bool => $event->summary === $summary);
});
