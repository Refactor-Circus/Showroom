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
        Schema::create(Tables::name('attribute_groups'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 100)->unique();
            // Locale => label, so translations need no schema change later.
            $table->json('labels')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create(Tables::name('attributes'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 100)->unique();
            $table->string('type', 32)->index();
            // Restrict, not null: a group is emptied deliberately before it goes.
            $table->foreignUlid('attribute_group_id')->nullable()
                ->constrained(Tables::name('attribute_groups'))->restrictOnDelete();
            $table->json('labels')->nullable();
            $table->boolean('is_unique')->default(false);
            // Stored now, enforced once channels and locales land.
            $table->boolean('is_localizable')->default(false);
            $table->boolean('is_scopable')->default(false);
            // Type-specific validation, shaped by AttributeType::settingsRules().
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['attribute_group_id', 'sort_order']);
        });

        Schema::create(Tables::name('attribute_options'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('attribute_id')->constrained(Tables::name('attributes'))->cascadeOnDelete();
            $table->string('code', 100);
            $table->json('labels')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'code']);
            $table->index(['attribute_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('attribute_options'));
        Schema::dropIfExists(Tables::name('attributes'));
        Schema::dropIfExists(Tables::name('attribute_groups'));
    }
};
