<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Unpublished changes to content visitors can already see (one row per record or settings screen). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drafts', function (Blueprint $t) {
            $t->id();
            $t->string('key', 191)->unique();
            $t->string('target', 191);
            $t->string('label', 191)->nullable();
            $t->string('edit_url', 500)->nullable();
            $t->json('payload');
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drafts');
    }
};
