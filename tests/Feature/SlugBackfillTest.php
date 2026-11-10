<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * The slug migration, as a fresh instance whose chunk size a test may set.
 *
 * @return Migration&object{chunk: int}
 */
function slugMigration(): Migration
{
    return require dirname(__DIR__, 2).'/database/migrations/2026_11_10_000001_add_slugs_to_showroom_products_and_owners.php';
}

/**
 * @return array<string, string|null>
 */
function slugsOf(string $table, string $column): array
{
    /** @var array<string, string|null> */
    return DB::table(Tables::name($table))->orderBy($column)->pluck('slug', $column)->all();
}

it('backfills every row in chunks, selecting only rows still without a slug', function (): void {
    foreach (['A/1', 'A 1', 'B', 'C', 'D'] as $identifier) {
        ProductModel::factory()->create(['identifier' => $identifier]);
    }

    $migration = slugMigration();
    $migration->down();

    $selects = [];
    DB::listen(function ($query) use (&$selects): void {
        if (str_contains($query->sql, Tables::name('products')) && str_contains($query->sql, '"slug" is null') && str_contains($query->sql, 'limit')) {
            $selects[] = $query->sql;
        }
    });

    $migration->chunk = 2;
    $migration->up();

    expect($selects)->toHaveCount(3)
        ->and($selects[0])->toContain('order by "id"')->toContain('limit 2')
        ->and(slugsOf('products', 'identifier'))->toBe(['A 1' => 'a-1-2', 'A/1' => 'a-1', 'B' => 'b', 'C' => 'c', 'D' => 'd']);
});

it('skips rows a partial backfill already filled when it runs again', function (): void {
    foreach (['A', 'B', 'C', 'D', 'E'] as $identifier) {
        ProductModel::factory()->create(['identifier' => $identifier]);
    }

    $migration = slugMigration();
    $migration->down();

    // A first run added the column and filled two rows before it stopped.
    Schema::table(Tables::name('products'), function (Blueprint $table): void {
        $table->string('slug', 191)->nullable();
    });
    DB::table(Tables::name('products'))->where('identifier', 'A')->update(['slug' => 'kept-a']);
    DB::table(Tables::name('products'))->where('identifier', 'B')->update(['slug' => 'c']);

    $migration->up();

    expect(slugsOf('products', 'identifier'))->toBe(['A' => 'kept-a', 'B' => 'c', 'C' => 'c-2', 'D' => 'd', 'E' => 'e']);
});

it('backfills empty and reserved sources by the same rules as creation', function (): void {
    foreach (['///', '!!!', 'versions'] as $identifier) {
        ProductModel::factory()->create(['identifier' => $identifier]);
    }

    OwnerModel::factory()->create(['code' => '///', 'labels' => []]);
    OwnerModel::factory()->create(['code' => 'acme', 'labels' => ['en' => 'Acme Corp']]);
    OwnerModel::factory()->create(['code' => 'globex', 'labels' => ['en' => '!!!']]);

    $migration = slugMigration();
    $migration->down();
    $migration->up();

    expect(slugsOf('products', 'identifier'))->toBe(['!!!' => 'product-2', '///' => 'product', 'versions' => 'versions-2'])
        ->and(slugsOf('owners', 'code'))->toBe(['///' => 'owner', 'acme' => 'acme-corp', 'globex' => 'globex']);
});

it('leaves the slug required and unique', function (): void {
    $product = ProductModel::factory()->create(['identifier' => 'A']);

    expect(fn () => ProductModel::factory()->create(['identifier' => 'B', 'slug' => $product->slug]))
        ->toThrow(UniqueConstraintViolationException::class);

    expect(fn () => DB::table(Tables::name('owners'))->where('id', OwnerModel::factory()->create()->id)->update(['slug' => null]))
        ->toThrow(QueryException::class);
});
