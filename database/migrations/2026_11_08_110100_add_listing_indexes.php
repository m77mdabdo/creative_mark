<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Indexes for the queries every public page runs: live records in their display order. */
return new class extends Migration
{
    private const INDEXES = [
        'regions' => ['status', 'sort_order'],
        'faqs' => ['status', 'needs_review', 'sort_order'],
        'testimonials' => ['status', 'sort_order'],
        'countries' => ['status', 'sort_order'],
        'stats' => ['verified', 'sort_order'],
        'case_studies' => ['status', 'sort_order'],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $columns) {
            Schema::table($table, fn (Blueprint $t) => $t->index($columns, "{$table}_listing_index"));
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::INDEXES) as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex("{$table}_listing_index"));
        }
    }
};
