<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Support\Tables;

it('indexes foreign keys, sort columns and tree paths', function (string $table, string $column): void {
    expect(Schema::hasIndex(Tables::name($table), Tables::name($table).'_'.$column.'_index'))->toBeTrue();
})->with([
    ['products', 'owner_id'],
    ['products', 'created_at'],
    ['products', 'updated_at'],
    ['products', 'published_version'],
    ['product_models', 'owner_id'],
    ['product_models', 'family_variant_id'],
    ['owners', 'owner_type_id'],
    ['owners', 'path'],
    ['categories', 'path'],
    ['product_completeness', 'locale_id'],
    ['product_unique_values', 'product_id'],
]);

it('finds a subtree through the path index', function (): void {
    $this->postJson('/showroom/categories', ['code' => 'master'])->assertCreated();

    $query = CategoryModel::query()->subtreeOf(CategoryModel::query()->where('code', 'master')->firstOrFail());
    $plan = collect(DB::select('explain query plan '.$query->toSql(), $query->getBindings()))->pluck('detail')->implode(' ');

    expect($plan)->toContain(Tables::name('categories').'_path_index');
});
