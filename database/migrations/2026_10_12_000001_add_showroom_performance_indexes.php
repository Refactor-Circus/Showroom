<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Indexes for what large catalogues query most.
 *
 * - Foreign keys MySQL indexes on its own but PostgreSQL and SQLite do not:
 *   owners, owner types, family variants, locales and products looked up
 *   from the rows that point at them (deletes and cascades included).
 * - `created_at`, `updated_at` and `published_version` on products: sorting
 *   and the published filter.
 * - The materialized `path` of owners and categories, which subtree queries
 *   match by prefix (`path LIKE '/root/…/%'`). The column stays `text`, so
 *   each driver gets the index its LIKE can use: a 255-character prefix
 *   index on MySQL/MariaDB (a path is 27 characters a level, so the first
 *   nine levels; deeper trees still narrow by them), `text_pattern_ops` on
 *   PostgreSQL (a plain index serves prefix LIKE only under the C
 *   collation), and `COLLATE NOCASE` on SQLite (its LIKE is
 *   case-insensitive). SQL Server cannot index `nvarchar(max)` and is left
 *   as it was.
 */
return new class extends Migration
{
    /**
     * Single-column indexes, by table key.
     *
     * @var array<string, array<int, string>>
     */
    private const array COLUMNS = [
        'products' => ['created_at', 'updated_at', 'published_version'],
    ];

    /**
     * Foreign keys without an index of their own, by table key.
     *
     * @var array<string, array<int, string>>
     */
    private const array FOREIGN_KEYS = [
        'products' => ['owner_id'],
        'product_models' => ['owner_id', 'family_variant_id'],
        'owners' => ['owner_type_id'],
        'product_completeness' => ['locale_id'],
        'product_unique_values' => ['product_id'],
    ];

    /**
     * Tables whose `path` subtree queries match by prefix.
     *
     * @var array<int, string>
     */
    private const array PATHS = ['owners', 'categories'];

    public function up(): void
    {
        foreach ($this->indexes() as $table => $columns) {
            Schema::table(Tables::name($table), function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $column) {
                    $blueprint->index($column, self::name($table, $column));
                }
            });
        }

        $path = $this->pathIndex();

        if ($path === null) {
            return;
        }

        foreach (self::PATHS as $table) {
            Schema::table(Tables::name($table), function (Blueprint $blueprint) use ($table, $path): void {
                $blueprint->rawIndex($path, self::name($table, 'path'));
            });
        }
    }

    public function down(): void
    {
        foreach ($this->pathIndex() === null ? [] : self::PATHS as $table) {
            Schema::table(Tables::name($table), function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropIndex(self::name($table, 'path'));
            });
        }

        foreach ($this->indexes() as $table => $columns) {
            Schema::table(Tables::name($table), function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $column) {
                    $blueprint->dropIndex(self::name($table, $column));
                }
            });
        }
    }

    /**
     * The indexes to add on this connection: MySQL already indexes every
     * foreign key, and dropping a second one there would fail while the key
     * relies on it.
     *
     * @return array<string, array<int, string>>
     */
    private function indexes(): array
    {
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return self::COLUMNS;
        }

        return array_merge_recursive(self::COLUMNS, self::FOREIGN_KEYS);
    }

    /**
     * The `path` index expression this driver's prefix LIKE can use, or null.
     */
    private function pathIndex(): ?string
    {
        return match (Schema::getConnection()->getDriverName()) {
            'mysql', 'mariadb' => 'path(255)',
            'pgsql' => 'path text_pattern_ops',
            'sqlite' => 'path collate nocase',
            default => null,
        };
    }

    private static function name(string $table, string $column): string
    {
        return Tables::name($table).'_'.$column.'_index';
    }
};
