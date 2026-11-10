<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Support;

use Normalizer;

/**
 * The one key normalization identifiers and external ids compare under:
 * case folded and accents stripped, as the database's default `utf8mb4`
 * `_ai_ci` collation compares them. `ABC` and `abc`, `Café` and `CAFE` are
 * the same key; `a-b` and `a_b` are not.
 *
 * Every in-memory match, and every value a search index stores or filters
 * on, goes through normalize(), so it agrees with what the database's unique
 * indexes and lookups already treat as equal.
 */
final class KeyNormalizer
{
    public static function normalize(string $value): string
    {
        // Compatibility decomposition splits an accent off its letter (é → e +
        // ◌́) and maps full-width and ligature forms to their plain letters.
        $decomposed = Normalizer::normalize($value, Normalizer::FORM_KD);
        $decomposed = is_string($decomposed) ? $decomposed : $value;

        $unaccented = preg_replace('/\p{Mn}+/u', '', $decomposed) ?? $decomposed;

        // Full case folding, so ß folds to ss as the collation compares it.
        return mb_convert_case($unaccented, MB_CASE_FOLD, 'UTF-8');
    }
}
