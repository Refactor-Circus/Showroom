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
        Schema::create(Tables::name('association_types'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 100)->unique();
            $table->json('labels')->nullable();
            // Exists both ways: compatible parts, replacements.
            $table->boolean('is_two_way')->default(false);
            // Each item carries a quantity: bundles and kits.
            $table->boolean('is_quantified')->default(false);
            $table->timestamps();
        });

        // Polymorphic on both ends: products and product models relate to
        // products and product models.
        Schema::create(Tables::name('associations'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('association_type_id')->constrained(Tables::name('association_types'))->restrictOnDelete();
            $table->string('source_type', 64);
            $table->ulid('source_id');
            $table->string('target_type', 64);
            $table->ulid('target_id');
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['association_type_id', 'source_type', 'source_id', 'target_type', 'target_id'], 'showroom_associations_unique');
            $table->index(['source_type', 'source_id']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('associations'));
        Schema::dropIfExists(Tables::name('association_types'));
    }
};
