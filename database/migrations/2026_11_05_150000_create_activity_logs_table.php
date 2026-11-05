<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Who did what in the dashboard. Field names only, never values, so no personal data or secrets are copied here. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('user_name', 120)->nullable(); // kept if the account is later deleted
            $t->string('event', 32);
            $t->string('subject_type', 64)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->string('subject_label', 200)->nullable();
            $t->json('fields')->nullable();
            $t->string('ip_hash', 64)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['created_at']);
            $t->index(['subject_type', 'subject_id']);
            $t->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
