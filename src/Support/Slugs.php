<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Support;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * The slugs of products and owners: stable, URL-safe and unique per table.
 *
 * A product's slug comes from its identifier; an owner's from its
 * default-locale label, else its code (ownerSource()). A source that
 * slugifies to nothing becomes `product` or `owner`. A slug another record
 * holds, or one of the RESERVED route segments, takes the first free `-2`,
 * `-3`, … suffix, cut to fit LENGTH. Creation, batch writes and the
 * migration that backfilled existing rows all follow these rules.
 *
 * A slug is set once, when its record is created, and never changes.
 */
final class Slugs
{
    /**
     * The length of the `slug` columns.
     */
    public const int LENGTH = 191;

    /**
     * The literal route segments that sit beside or after a `{product}` or
     * `{owner}` key (`products/create`, `products/{product}/versions`), so a
     * slug never reads as one of them.
     *
     * @var list<string>
     */
    public const array RESERVED = ['create', 'revert', 'transitions', 'versions'];

    /**
     * The base a source that slugifies to nothing falls back to, by table key.
     *
     * @var array<string, string>
     */
    private const array FALLBACKS = ['products' => 'product', 'owners' => 'owner'];

    /**
     * The longest suffix the lookup plans for (`-999999`): every suffixed slug
     * of a base starts with the stem() this leaves.
     */
    private const int SUFFIX = 7;

    /**
     * Bases per query when looking for suffixed slugs, so one batch never
     * builds an expression deeper than a driver allows.
     */
    private const int LIKES_PER_QUERY = 100;

    /**
     * Bases per query when looking for exact matches.
     */
    private const int BASES_PER_QUERY = 500;

    /**
     * A free slug for one record of a table (`products` or `owners`).
     */
    public function for(string $table, string $source): string
    {
        return $this->forMany($table, [$source])[0];
    }

    /**
     * Free slugs for a batch of records of one table, keyed as the sources
     * are. The taken slugs are read once for the whole batch, and a slug
     * given to one source counts as taken for the rest, so two sources with
     * the same base get `-2` and `-3` rather than the same slug.
     *
     * @template TKey of array-key
     *
     * @param  array<TKey, string>  $sources
     * @return array<TKey, string>
     */
    public function forMany(string $table, array $sources): array
    {
        $bases = array_map(fn (string $source): string => self::base($table, $source), $sources);

        if ($bases === []) {
            return [];
        }

        $distinct = array_values(array_unique($bases));
        $taken = $this->existing($table, $distinct);
        $repeats = array_count_values($bases);

        // Bases that cannot be used as they are need their suffixed slugs
        // read too; a base that turns out taken later in the loop reads them
        // then.
        $crowded = array_values(array_filter(
            $distinct,
            fn (string $base): bool => isset($taken[$base]) || in_array($base, self::RESERVED, true) || $repeats[$base] > 1,
        ));
        $taken += $this->suffixed($table, $crowded);
        $read = array_fill_keys($crowded, true);

        $slugs = [];

        foreach ($bases as $key => $base) {
            if (isset($taken[$base]) && ! isset($read[$base])) {
                $taken += $this->suffixed($table, [$base]);
                $read[$base] = true;
            }

            $slugs[$key] = $slug = self::firstFree($base, $taken);
            $taken[$slug] = true;
        }

        return $slugs;
    }

    /**
     * Write a new record with a free slug. When another write takes that
     * slug between the lookup and the insert, the write runs once more with
     * a fresh lookup; a second clash is thrown.
     *
     * @template TResult
     *
     * @param  Closure(string): TResult  $write  Given the slug; inserts the record.
     * @return TResult
     */
    public function claim(string $table, string $source, Closure $write): mixed
    {
        try {
            return DB::transaction(fn (): mixed => $write($this->for($table, $source)));
        } catch (UniqueConstraintViolationException) {
            return DB::transaction(fn (): mixed => $write($this->for($table, $source)));
        }
    }

    /**
     * What an owner's slug is made from: its label in the app's default
     * locale, else its code, whichever slugifies to something; else an empty
     * source, which falls back to `owner`.
     *
     * @param  array<string, mixed>|null  $labels
     */
    public static function ownerSource(?array $labels, string $code): string
    {
        $locale = config('app.locale');
        $label = is_string($locale) ? ($labels[$locale] ?? null) : null;

        foreach ([$label, $code] as $candidate) {
            if (is_string($candidate) && self::slugify($candidate) !== '') {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * The slug a source would have if it were free: slugified, or the
     * table's fallback word when that leaves nothing, cut to LENGTH.
     *
     * @throws InvalidArgumentException
     */
    public static function base(string $table, string $source): string
    {
        if (! isset(self::FALLBACKS[$table])) {
            throw new InvalidArgumentException(sprintf('Slugs are kept for products and owners only, not [%s].', $table));
        }

        $slug = self::slugify($source);

        return self::cut($slug === '' ? self::FALLBACKS[$table] : $slug, self::LENGTH);
    }

    /**
     * The base itself when it is free and not reserved, else the base with
     * the first free suffix, the base cut so the whole fits LENGTH.
     *
     * @param  array<string, true>  $taken
     */
    public static function firstFree(string $base, array $taken): string
    {
        if (! isset($taken[$base]) && ! in_array($base, self::RESERVED, true)) {
            return $base;
        }

        for ($n = 2; ; $n++) {
            $suffix = '-'.$n;
            $slug = self::cut($base, self::LENGTH - strlen($suffix)).$suffix;

            if (! isset($taken[$slug])) {
                return $slug;
            }
        }
    }

    /**
     * `Str::slug()`, with every run of other characters read as a word break
     * first: `Str::slug()` drops a `/` or `.` outright, which would run
     * `A/B` together as `ab`.
     */
    private static function slugify(string $source): string
    {
        return Str::slug((string) preg_replace('/[^\pL\pN]+/u', ' ', $source));
    }

    /**
     * The slug at most $length long, with no dash left at the cut.
     */
    private static function cut(string $slug, int $length): string
    {
        return strlen($slug) <= $length ? $slug : rtrim(substr($slug, 0, $length), '-');
    }

    /**
     * The start every suffixed slug of a base shares: the base and its dash,
     * or for a base too long to take a suffix whole, the part of it any cut
     * leaves.
     */
    private static function stem(string $base): string
    {
        return strlen($base) + self::SUFFIX <= self::LENGTH
            ? $base.'-'
            : substr($base, 0, self::LENGTH - self::SUFFIX - 1);
    }

    /**
     * The bases already held as slugs.
     *
     * @param  list<string>  $bases
     * @return array<string, true>
     */
    private function existing(string $table, array $bases): array
    {
        $taken = [];

        foreach (array_chunk($bases, self::BASES_PER_QUERY) as $chunk) {
            foreach ($this->query($table)->whereIn('slug', $chunk)->pluck('slug') as $slug) {
                $taken[(string) $slug] = true;
            }
        }

        return $taken;
    }

    /**
     * The held slugs that are, or could be, a base or one of its suffixed
     * forms.
     *
     * @param  list<string>  $bases
     * @return array<string, true>
     */
    private function suffixed(string $table, array $bases): array
    {
        $taken = [];

        foreach (array_chunk($bases, self::LIKES_PER_QUERY) as $chunk) {
            $query = $this->query($table)->where(function (Builder $query) use ($chunk): void {
                foreach ($chunk as $base) {
                    // Slugs hold only letters, digits and dashes: no LIKE wildcards.
                    $query->orWhere('slug', $base)->orWhere('slug', 'like', self::stem($base).'%');
                }
            });

            foreach ($query->pluck('slug') as $slug) {
                $taken[(string) $slug] = true;
            }
        }

        return $taken;
    }

    private function query(string $table): Builder
    {
        return DB::table(Tables::name($table));
    }
}
