<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Optional Google title and description per case study and language. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_study_translations', function (Blueprint $t) {
            $t->string('seo_title', 160)->nullable()->after('outcome');
            $t->string('seo_description', 320)->nullable()->after('seo_title');
        });
    }

    public function down(): void
    {
        Schema::table('case_study_translations', fn (Blueprint $t) => $t->dropColumn(['seo_title', 'seo_description']));
    }
};
