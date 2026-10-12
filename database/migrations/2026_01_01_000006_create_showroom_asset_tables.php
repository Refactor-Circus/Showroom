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
        Schema::create(Tables::name('assets'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 191)->unique();
            $table->json('labels')->nullable();
            // Where the file lives; the disk is recorded so changing the
            // configured disk never strands existing files.
            $table->string('disk', 64);
            $table->string('path', 1024);
            $table->string('filename');
            $table->string('mime_type', 191)->nullable()->index();
            $table->unsignedBigInteger('size')->default(0);
            $table->char('checksum', 64)->nullable()->index();
            $table->timestamps();
        });

        // Polymorphic, so one asset serves products, product models and any
        // level of an ownership chain, each under a role.
        Schema::create(Tables::name('asset_links'), function (Blueprint $table): void {
            $table->foreignUlid('asset_id')->constrained(Tables::name('assets'))->cascadeOnDelete();
            $table->string('linkable_type', 64);
            $table->ulid('linkable_id');
            $table->string('role', 100)->default('media');
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['asset_id', 'linkable_type', 'linkable_id', 'role']);
            $table->index(['linkable_type', 'linkable_id', 'role', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('asset_links'));
        Schema::dropIfExists(Tables::name('assets'));
    }
};
