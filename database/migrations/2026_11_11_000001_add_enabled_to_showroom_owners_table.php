<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Whether an owner is enabled. A product is effectively enabled only when it
 * is, and so is every owner of its chain: switching a vendor off switches
 * off every product beneath it. Existing owners stay enabled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(Tables::name('owners'), function (Blueprint $table): void {
            $table->boolean('enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table(Tables::name('owners'), function (Blueprint $table): void {
            $table->dropColumn('enabled');
        });
    }
};
