<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use Workbench\App\Models\User;

it('answers the settings summary', function (): void {
    config()->set('showroom.pagination.per_page', '40');

    $this->getJson(route('showroom.settings-summary.show'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'authorization' => false,
            'routes' => ['enabled' => true, 'prefix' => 'showroom', 'middleware' => ['api']],
            'mcp' => [
                'web' => ['enabled' => false, 'route' => 'mcp/showroom', 'middleware' => []],
                'local' => ['enabled' => false, 'handle' => 'showroom'],
            ],
            'pagination' => ['per_page' => 40, 'max_per_page' => 100],
        ]]);
});

it('refuses a guest, and a user who may not list products, while authorization is on', function (): void {
    config()->set('showroom.authorization', true);
    $ann = User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']);

    $this->getJson(route('showroom.settings-summary.show'))->assertUnauthorized();

    $this->actingAs($ann)->getJson(route('showroom.settings-summary.show'))
        ->assertOk()
        ->assertJsonPath('data.authorization', true);

    Gate::before(fn (mixed $user, string $ability, array $arguments): ?bool => $ability === 'viewAny' && ($arguments[0] ?? null) === ProductModel::class ? false : null);

    $this->actingAs($ann)->getJson(route('showroom.settings-summary.show'))->assertForbidden();
});

it('answers anyone while authorization is off', function (): void {
    config()->set('showroom.authorization', false);

    Gate::before(fn (mixed $user, string $ability): ?bool => $ability === 'viewAny' ? false : null);

    $this->getJson(route('showroom.settings-summary.show'))
        ->assertOk()
        ->assertJsonPath('data.authorization', false);
});
