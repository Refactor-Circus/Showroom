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
        Schema::create(Tables::name('flags'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 100)->unique();
            $table->json('labels')->nullable();
            // The subject types the flag may be assigned to: "product", "owner" or both.
            $table->json('applies_to');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create(Tables::name('flag_assignments'), function (Blueprint $table): void {
            // A flag cannot be deleted while it is assigned.
            $table->foreignUlid('flag_id')->constrained(Tables::name('flags'))->restrictOnDelete();
            // The subject's morph alias and key: a product or an owner.
            $table->string('subject_type', 50);
            $table->ulid('subject_id');

            $table->primary(['flag_id', 'subject_type', 'subject_id']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('flag_assignments'));
        Schema::dropIfExists(Tables::name('flags'));
    }
};
