<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Support\KeyNormalizer;

it('folds case and strips accents, as the default _ai_ci collation compares', function (string $value, string $key): void {
    expect(KeyNormalizer::normalize($value))->toBe($key);
})->with([
    'upper case' => ['ABC-123', 'abc-123'],
    'mixed case' => ['ErP-Id', 'erp-id'],
    'an acute accent' => ['Café', 'cafe'],
    'an accented capital' => ['ÉCOLE', 'ecole'],
    'a tilde and an acute' => ['Ñandú', 'nandu'],
    'a sharp s' => ['Straße', 'strasse'],
    'full-width letters and digits' => ['ＡＢ１', 'ab1'],
]);

it('keeps everything that is not a letter case or an accent', function (string $value): void {
    expect(KeyNormalizer::normalize($value))->toBe(strtolower($value));
})->with([
    'a quote' => ['a"b'],
    'a backslash' => ['c\\d'],
    'an OR between spaces' => ['X OR Y'],
    'a SKU with a slash' => ['83AL-IC/40 4 RED ALUM'],
    'interior double spaces' => ['A  B'],
]);

it('gives keys that differ only by case or accent the same normalization', function (string $one, string $other): void {
    expect(KeyNormalizer::normalize($one))->toBe(KeyNormalizer::normalize($other));
})->with([
    'case' => ['a', 'A'],
    'an accent' => ['é', 'E'],
    'case and accents together' => ['Crème Brûlée', 'CREME BRULEE'],
]);

it('keeps keys apart that differ in anything else', function (string $one, string $other): void {
    expect(KeyNormalizer::normalize($one))->not->toBe(KeyNormalizer::normalize($other));
})->with([
    'a dash and an underscore' => ['a-b', 'a_b'],
    'a space and none' => ['a b', 'ab'],
    'a trailing digit' => ['E-1', 'E-10'],
]);
