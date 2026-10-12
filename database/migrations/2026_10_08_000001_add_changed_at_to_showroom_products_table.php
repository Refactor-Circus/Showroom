<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Showroom\Support\Tables;

/**
 * When anything a product shows last changed: its own columns, and what it
 * gets from elsewhere — categories, associations, assets, its model, family,
 * owner or channel. `updated_at` only moves with the product's own row, so a
 * client asking "what changed since?" needs this one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(Tables::name('products'), function (Blueprint $table): void {
            $table->timestamp('changed_at')->nullable()->index();
        });

        DB::table(Tables::name('products'))->update(['changed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table(Tables::name('products'), function (Blueprint $table): void {
            $table->dropIndex(['changed_at']);
            $table->dropColumn('changed_at');
        });
    }
};
