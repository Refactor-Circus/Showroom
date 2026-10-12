<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Showroom\Support\Tables;

return new class extends Migration
{
    public function up(): void
    {
        // A root category is a tree; each tree is independent of the others.
        Schema::create(Tables::name('categories'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('parent_id')->nullable()->constrained(Tables::name('categories'))->restrictOnDelete();
            $table->string('code', 100)->unique();
            $table->json('labels')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            // "/root/…/self/": a whole branch is one prefix match.
            $table->text('path');
            $table->unsignedInteger('depth')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
        });

        Schema::create(Tables::name('category_product'), function (Blueprint $table): void {
            $table->foreignUlid('category_id')->constrained(Tables::name('categories'))->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained(Tables::name('products'))->cascadeOnDelete();

            $table->primary(['category_id', 'product_id']);
            $table->index('product_id');
        });

        Schema::create(Tables::name('category_product_model'), function (Blueprint $table): void {
            $table->foreignUlid('category_id')->constrained(Tables::name('categories'))->cascadeOnDelete();
            $table->foreignUlid('product_model_id')->constrained(Tables::name('product_models'))->cascadeOnDelete();

            $table->primary(['category_id', 'product_model_id']);
            $table->index('product_model_id');
        });
    }

    public function down(): void
    {
        // Categories reference each other, which a restricted drop would trip over.
        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::dropIfExists(Tables::name('category_product_model'));
            Schema::dropIfExists(Tables::name('category_product'));
            Schema::dropIfExists(Tables::name('categories'));
        });
    }
};
