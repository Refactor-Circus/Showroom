<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use RefactorCircus\Showroom\Atrium\FlagScreen;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

/*
 * The flag-definitions screen: list, create, edit and delete the flags that
 * products and owners carry, with the action's refusal shown when a flag
 * still assigned to something cannot be deleted.
 */

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');
});

it('lists, creates, edits and deletes flag definitions', function (): void {
    $this->get(route('atrium.showroom.flags.index'))
        ->assertOk()
        ->assertSee(__('showroom::showroom.no_flags'))
        ->assertSee('data-testid="new-flag-card"', false);

    $this->post(route('atrium.showroom.flags.store'), [
        'code' => 'restricted',
        'labels' => ['en' => 'Restricted'],
        'applies_to' => ['product', 'owner'],
        'sort_order' => '2',
    ])->assertRedirect(route('atrium.showroom.flags.show', 'restricted'));

    $this->post(route('atrium.showroom.flags.store'), ['code' => 'clearance', 'applies_to' => ['product']])->assertRedirect();

    $flag = FlagModel::query()->where('code', 'restricted')->firstOrFail();

    expect($flag->labels)->toBe(['en' => 'Restricted'])
        ->and($flag->applies_to)->toBe(['product', 'owner'])
        ->and($flag->sort_order)->toBe(2);

    // Listed in flag order, each linking to its page.
    $this->get(route('atrium.showroom.flags.index'))
        ->assertOk()
        ->assertSeeInOrder(['clearance', 'restricted'])
        ->assertSee('href="'.route('atrium.showroom.flags.show', 'restricted').'"', false);

    // The code is shown, never offered for editing.
    $this->get(route('atrium.showroom.flags.show', 'restricted'))
        ->assertOk()
        ->assertSee('data-testid="flag-details-card"', false)
        ->assertSee('data-testid="flag-code"', false)
        ->assertDontSee('name="code"', false)
        ->assertSee('id="atrium-applies_to-product"', false)
        ->assertSee('id="atrium-applies_to-owner"', false);

    $this->patch(route('atrium.showroom.flags.update', 'restricted'), [
        'labels' => ['en' => 'Restricted sale'],
        'applies_to' => ['product'],
        'sort_order' => '5',
    ])->assertRedirect(route('atrium.showroom.flags.show', 'restricted'))->assertSessionHasNoErrors();

    $flag->refresh();

    expect($flag->code)->toBe('restricted')
        ->and($flag->labels)->toBe(['en' => 'Restricted sale'])
        ->and($flag->applies_to)->toBe(['product'])
        ->and($flag->sort_order)->toBe(5);

    $this->delete(route('atrium.showroom.flags.destroy', 'restricted'))
        ->assertRedirect(route('atrium.showroom.flags.index'))
        ->assertSessionHas('status', __('showroom::showroom.flag_deleted'));

    expect(FlagModel::query()->pluck('code')->all())->toBe(['clearance']);
});

it('refuses a code sent on edit', function (): void {
    FlagModel::factory()->create(['code' => 'restricted']);

    $this->from(route('atrium.showroom.flags.show', 'restricted'))
        ->patch(route('atrium.showroom.flags.update', 'restricted'), ['code' => 'other', 'applies_to' => ['product']])
        ->assertSessionHasErrors('code');

    expect(FlagModel::query()->pluck('code')->all())->toBe(['restricted']);
});

it('shows the refusal message when a flag still assigned cannot be deleted', function (): void {
    FlagModel::factory()->create(['code' => 'restricted']);
    OwnerTypeModel::factory()->create(['code' => 'vendor']);
    app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => 'acme', 'type' => 'vendor', 'flags' => ['restricted']]));

    $message = 'Flag "restricted" is assigned to 1 record(s). Remove it from them first.';

    $this->from(route('atrium.showroom.flags.show', 'restricted'))
        ->delete(route('atrium.showroom.flags.destroy', 'restricted'))
        ->assertRedirect(route('atrium.showroom.flags.show', 'restricted'))
        ->assertSessionHasErrors(['flag' => $message]);

    $this->get(route('atrium.showroom.flags.show', 'restricted'))
        ->assertOk()
        ->assertSee('data-testid="flash-error"', false)
        ->assertSee($message);

    expect(FlagModel::query()->where('code', 'restricted')->exists())->toBeTrue();
});

it('shows the refusal message when applies_to drops a kind still carrying the flag', function (): void {
    FlagModel::factory()->create(['code' => 'restricted']);
    OwnerTypeModel::factory()->create(['code' => 'vendor']);
    app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => 'acme', 'type' => 'vendor', 'flags' => ['restricted']]));

    $this->from(route('atrium.showroom.flags.show', 'restricted'))
        ->patch(route('atrium.showroom.flags.update', 'restricted'), ['applies_to' => ['product']])
        ->assertSessionHasErrors('applies_to');

    expect(FlagModel::query()->where('code', 'restricted')->firstOrFail()->applies_to)->toBe(['product', 'owner']);
});

it('declares the flag screens on the screen extensions registry', function (): void {
    $flag = FlagModel::factory()->create(['code' => 'restricted']);

    $list = $this->get(route('atrium.showroom.flags.index'))->assertOk()->viewData('resolved');
    $detail = $this->get(route('atrium.showroom.flags.show', 'restricted'))->assertOk()->viewData('resolved');

    expect($list->screen->key)->toBe(FlagScreen::LIST)
        ->and($list->screen->key)->toBe('showroom.flags')
        ->and($detail->screen->key)->toBe('showroom.flag')
        ->and($detail->screen->record?->is($flag))->toBeTrue();
});

it('links the flag definitions from the products and owners lists', function (): void {
    $link = 'href="'.route('atrium.showroom.flags.index').'"';

    $this->get(route('atrium.showroom.products.index'))->assertOk()->assertSee($link, false);
    $this->get(route('atrium.showroom.owners.index'))->assertOk()->assertSee($link, false);
});
