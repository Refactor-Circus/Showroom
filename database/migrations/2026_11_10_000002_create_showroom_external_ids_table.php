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
        Schema::create(Tables::name('external_ids'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            // The subject's morph alias and key: a product or an owner.
            $table->string('subject_type', 50);
            $table->ulid('subject_id');
            // The source (an ERP, a shop) and the record's id there, as
            // Roster's organization links name them.
            $table->string('source', 64);
            $table->string('external_id', 191);
            // When the source last wrote or confirmed the id.
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // One holder per id in a source, per kind of record: a product
            // and an owner may share one. Compared under the column's
            // collation, case- and accent-insensitive by default on MySQL.
            $table->unique(['subject_type', 'source', 'external_id']);
            // One id per record and source. Its leading columns also serve
            // the lookups of a record's ids.
            $table->unique(['subject_type', 'subject_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('external_ids'));
    }
};
