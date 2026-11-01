<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Service detail pages: grouping, related services, linked FAQs, and per-locale overview / scope / closing note. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $t) {
            $t->string('group', 16)->default('setup')->after('status'); // setup | grow | comply (Gate 3, G3-6)
            $t->json('related')->nullable()->after('group'); // slugs of related services, in display order
        });

        Schema::table('service_translations', function (Blueprint $t) {
            $t->text('overview')->nullable()->after('summary');
            $t->json('scope')->nullable()->after('overview'); // list of included items
            $t->string('note', 500)->nullable()->after('scope'); // closing line from the presentation
        });

        Schema::create('faq_service', function (Blueprint $t) {
            $t->foreignId('faq_id')->constrained()->cascadeOnDelete();
            $t->foreignId('service_id')->constrained()->cascadeOnDelete();
            $t->primary(['faq_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_service');
        Schema::table('service_translations', fn (Blueprint $t) => $t->dropColumn(['overview', 'scope', 'note']));
        Schema::table('services', fn (Blueprint $t) => $t->dropColumn(['group', 'related']));
    }
};
