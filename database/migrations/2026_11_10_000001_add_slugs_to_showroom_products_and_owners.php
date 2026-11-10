<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Showroom\Support\Slugs;
use RefactorCircus\Showroom\Support\Tables;

/**
 * A stable, URL-safe `slug` for every product and owner.
 *
 * The column is added nullable, every existing row is given its slug in
 * chunks through Slugs (the same rules new records follow), and only once no
 * row is left without one does the column become required and unique. The
 * backfill reads only rows whose slug is still null, in key order, so a run
 * that stopped part way picks up where it left off.
 *
 * On MySQL the unique index on a large products table holds a lock while it
 * builds; see the upgrade notes.
 */
return new class extends Migration
{
    /**
     * Each step commits on its own: a long backfill is resumable rather than
     * one transaction, and SQLite can switch foreign keys off while it
     * rebuilds a table to make the column required.
     *
     * @var bool
     */
    public $withinTransaction = false;

    /**
     * Rows given their slugs per round.
     */
    public int $chunk = 1000;

    public function up(): void
    {
        $this->backfill('products', fn (stdClass $row): string => (string) $row->identifier);

        $this->backfill('owners', function (stdClass $row): string {
            $labels = is_string($row->labels) ? json_decode($row->labels, true) : null;

            return Slugs::ownerSource(is_array($labels) ? $labels : null, (string) $row->code);
        });

        foreach (['products', 'owners'] as $table) {
            $this->requireUnique($table);
        }
    }

    public function down(): void
    {
        foreach (['products', 'owners'] as $table) {
            Schema::table(Tables::name($table), function (Blueprint $blueprint): void {
                $blueprint->dropUnique(['slug']);
                $blueprint->dropColumn('slug');
            });
        }
    }

    /**
     * Add the nullable column unless a previous run did, then give every row
     * still without a slug its own.
     *
     * @param  Closure(stdClass): string  $source
     */
    private function backfill(string $table, Closure $source): void
    {
        $name = Tables::name($table);

        if (! Schema::hasColumn($name, 'slug')) {
            Schema::table($name, function (Blueprint $blueprint): void {
                $blueprint->string('slug', Slugs::LENGTH)->nullable();
            });
        }

        $slugs = app(Slugs::class);

        do {
            $rows = DB::table($name)->whereNull('slug')->orderBy('id')->limit($this->chunk)->get();

            $assigned = $slugs->forMany($table, $rows->mapWithKeys(fn (stdClass $row): array => [(string) $row->id => $source($row)])->all());

            DB::transaction(function () use ($name, $assigned): void {
                foreach ($assigned as $id => $slug) {
                    DB::table($name)->where('id', $id)->update(['slug' => $slug]);
                }
            });
        } while ($rows->count() === $this->chunk);
    }

    /**
     * Make the column required and unique, once no row is left without a
     * slug, unless a previous run already did.
     */
    private function requireUnique(string $table): void
    {
        $name = Tables::name($table);

        if (Schema::hasIndex($name, ['slug'], 'unique')) {
            return;
        }

        if (DB::table($name)->whereNull('slug')->exists()) {
            throw new RuntimeException(sprintf('Rows of [%s] are still without a slug; run the migration again to finish the backfill.', $name));
        }

        Schema::table($name, function (Blueprint $blueprint): void {
            $blueprint->string('slug', Slugs::LENGTH)->nullable(false)->change();
        });

        Schema::table($name, function (Blueprint $blueprint): void {
            $blueprint->unique('slug');
        });
    }
};
