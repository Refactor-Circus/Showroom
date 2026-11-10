<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Support\Slugs;

it('slugifies the source into a base', function (string $table, string $source, string $base): void {
    expect(Slugs::base($table, $source))->toBe($base);
})->with([
    'a slash' => ['products', 'A/B', 'a-b'],
    'a space' => ['products', 'A B', 'a-b'],
    'a real SKU' => ['products', '83AL-IC/40 4 RED ALUM', '83al-ic-40-4-red-alum'],
    'an owner label' => ['owners', 'Acme Corp', 'acme-corp'],
]);

it('falls back to the subject word when the source slugifies to nothing', function (string $table, string $source, string $base): void {
    expect(Slugs::base($table, $source))->toBe($base);
})->with([
    'slashes' => ['products', '///', 'product'],
    'punctuation' => ['products', '!!!', 'product'],
    'an empty owner source' => ['owners', '', 'owner'],
]);

it('cuts a long base to the column length', function (): void {
    expect(Slugs::base('products', str_repeat('a', 300)))->toBe(str_repeat('a', Slugs::LENGTH));
});

it('refuses a table it has no fallback word for', function (): void {
    Slugs::base('families', 'shirts');
})->throws(InvalidArgumentException::class, 'families');

it('keeps a free base as it is', function (): void {
    expect(Slugs::firstFree('a-b', []))->toBe('a-b');
});

it('takes the first free suffix, with no gaps', function (): void {
    expect(Slugs::firstFree('a-b', ['a-b' => true]))->toBe('a-b-2')
        ->and(Slugs::firstFree('a-b', ['a-b' => true, 'a-b-2' => true]))->toBe('a-b-3')
        ->and(Slugs::firstFree('a-b', ['a-b' => true, 'a-b-3' => true]))->toBe('a-b-2');
});

it('suffixes a reserved route segment even when it is free', function (string $reserved): void {
    expect(Slugs::firstFree($reserved, []))->toBe($reserved.'-2');
})->with(Slugs::RESERVED);

it('cuts a collided base so the suffixed slug still fits', function (): void {
    $base = str_repeat('a', Slugs::LENGTH);

    expect($second = Slugs::firstFree($base, [$base => true]))->toHaveLength(Slugs::LENGTH)->toEndWith('-2')
        ->and(Slugs::firstFree($base, array_fill_keys([$base, $second, ...array_map(
            fn (int $n): string => str_repeat('a', Slugs::LENGTH - strlen('-'.$n)).'-'.$n,
            range(3, 9),
        )], true)))->toBe(str_repeat('a', Slugs::LENGTH - 3).'-10');
});

it('never leaves a dash before the suffix when the cut lands on one', function (): void {
    $base = str_repeat('a', Slugs::LENGTH - 3).'-bb';

    expect(Slugs::firstFree($base, [$base => true]))->toBe(str_repeat('a', Slugs::LENGTH - 3).'-2');
});
