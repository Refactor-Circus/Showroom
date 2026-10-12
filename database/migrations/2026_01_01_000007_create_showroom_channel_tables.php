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
        Schema::create(Tables::name('locales'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 20)->unique();
            $table->json('labels')->nullable();
            $table->timestamps();
        });

        Schema::create(Tables::name('channels'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 100)->unique();
            $table->json('labels')->nullable();
            // ISO 4217 codes the channel sells in.
            $table->json('currencies')->nullable();
            // The root category of the branch this channel sells.
            $table->foreignUlid('category_tree_id')->nullable()->constrained(Tables::name('categories'))->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create(Tables::name('channel_locale'), function (Blueprint $table): void {
            $table->foreignUlid('channel_id')->constrained(Tables::name('channels'))->cascadeOnDelete();
            // Restrict: a locale a channel publishes in cannot vanish under it.
            $table->foreignUlid('locale_id')->constrained(Tables::name('locales'))->restrictOnDelete();

            $table->primary(['channel_id', 'locale_id']);
            $table->index('locale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('channel_locale'));
        Schema::dropIfExists(Tables::name('channels'));
        Schema::dropIfExists(Tables::name('locales'));
    }
};
