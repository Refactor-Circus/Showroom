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
        Schema::create(Tables::name('owner_types'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 100)->unique();
            $table->json('labels')->nullable();
            // False: any owner type may be the parent. True: only those in
            // showroom_owner_type_parents, which may be none.
            $table->boolean('restricts_parents')->default(false);
            $table->boolean('can_be_root')->default(true);
            $table->boolean('owns_products')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create(Tables::name('owner_type_parents'), function (Blueprint $table): void {
            $table->foreignUlid('owner_type_id')->constrained(Tables::name('owner_types'))->cascadeOnDelete();
            $table->foreignUlid('parent_type_id')->constrained(Tables::name('owner_types'))->cascadeOnDelete();

            $table->primary(['owner_type_id', 'parent_type_id']);
        });

        Schema::create(Tables::name('owners'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_type_id')->constrained(Tables::name('owner_types'))->restrictOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained(Tables::name('owners'))->restrictOnDelete();
            $table->string('code', 191)->unique();
            $table->json('labels')->nullable();
            // "/root/…/self/": everything beneath an owner is one prefix match.
            $table->text('path');
            $table->unsignedInteger('depth')->default(0);
            $table->timestamps();

            $table->index('parent_id');
        });

        Schema::table(Tables::name('products'), function (Blueprint $table): void {
            $table->foreignUlid('owner_id')->nullable()->after('parent_id')->constrained(Tables::name('owners'))->restrictOnDelete();
        });

        Schema::table(Tables::name('product_models'), function (Blueprint $table): void {
            $table->foreignUlid('owner_id')->nullable()->after('parent_id')->constrained(Tables::name('owners'))->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Owners reference each other, which a restricted drop would trip over.
        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::table(Tables::name('product_models'), function (Blueprint $table): void {
                $table->dropConstrainedForeignId('owner_id');
            });

            Schema::table(Tables::name('products'), function (Blueprint $table): void {
                $table->dropConstrainedForeignId('owner_id');
            });

            Schema::dropIfExists(Tables::name('owners'));
            Schema::dropIfExists(Tables::name('owner_type_parents'));
            Schema::dropIfExists(Tables::name('owner_types'));
        });
    }
};
