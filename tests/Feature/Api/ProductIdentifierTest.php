<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(fn () => Catalog::apparel());

/**
 * Whether the create rules refuse the identifier, under the current config.
 */
function identifierRefused(string $identifier): bool
{
    return validator(['identifier' => $identifier], CreateProductData::rules())->errors()->has('identifier');
}

it('creates products whose identifiers are real SKUs with spaces and slashes', function (string $identifier): void {
    $this->postJson(route('showroom.products.store'), ['identifier' => $identifier])
        ->assertCreated()
        ->assertJsonPath('data.identifier', $identifier);

    expect(ProductModel::query()->where('identifier', $identifier)->count())->toBe(1);
})->with([
    'spaces' => 'ML20906 HPSK 630 SEC M91 M92 LC LH',
    'a slash' => '83AL-IC/40 4 RED ALUM',
]);

it('keeps identifiers with spaces and slashes unique', function (): void {
    $this->postJson(route('showroom.products.store'), ['identifier' => '83AL-IC/40 4 RED ALUM'])->assertCreated();

    $this->postJson(route('showroom.products.store'), ['identifier' => '83AL-IC/40 4 RED ALUM'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('identifier');

    expect(ProductModel::query()->count())->toBe(1);
});

it('accepts identifiers the default pattern allows', function (string $identifier): void {
    expect(identifierRefused($identifier))->toBeFalse();
})->with([
    'one character' => 'A',
    'an interior double space' => 'A  B',
    '191 characters' => str_repeat('A', 191),
]);

it('refuses identifiers the default pattern rejects', function (string $identifier): void {
    expect(identifierRefused($identifier))->toBeTrue();
})->with([
    'empty' => '',
    'leading whitespace' => ' A',
    'trailing whitespace' => 'A ',
    'a tab' => "A\tB",
    'a trailing newline' => "A\n",
    'a control character' => "A\x07B",
    '192 characters' => str_repeat('A', 192),
]);

it('falls back to the default pattern when a published config has no pattern', function (): void {
    config()->set('showroom.products', []);

    expect(identifierRefused('83AL-IC/40 4 RED ALUM'))->toBeFalse()
        ->and(identifierRefused("A\tB"))->toBeTrue()
        ->and(identifierRefused(' A'))->toBeTrue();
});

it('applies a stricter pattern a host configures', function (): void {
    config()->set('showroom.products.identifier_pattern', '/^[A-Z0-9-]+$/');

    $this->postJson(route('showroom.products.store'), ['identifier' => '83AL-IC/40 4 RED ALUM'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('identifier');

    $this->postJson(route('showroom.products.store'), ['identifier' => 'ML20906'])->assertCreated();
});

it('takes a pattern holding a pipe', function (): void {
    config()->set('showroom.products.identifier_pattern', '/^(?:ABC|XYZ)-\d+$/');

    expect(identifierRefused('XYZ-1'))->toBeFalse()
        ->and(identifierRefused('ABC-2'))->toBeFalse()
        ->and(identifierRefused('QQQ-1'))->toBeTrue();
});

it('fails loud on a pattern that does not compile', function (): void {
    config()->set('showroom.products.identifier_pattern', '/[unclosed/');

    expect(fn (): array => CreateProductData::rules())
        ->toThrow(InvalidArgumentException::class, 'showroom.products.identifier_pattern');
});
