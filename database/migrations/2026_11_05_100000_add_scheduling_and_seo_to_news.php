<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Stories can go live at a chosen time, and can have their own Google title and description per language. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $t) {
            $t->dateTime('publish_at')->nullable()->after('status');
            $t->index(['status', 'publish_at']);
        });
        Schema::table('news_article_translations', function (Blueprint $t) {
            $t->string('seo_title', 160)->nullable()->after('takeaway');
            $t->string('seo_description', 320)->nullable()->after('seo_title');
        });
    }

    public function down(): void
    {
        Schema::table('news_article_translations', fn (Blueprint $t) => $t->dropColumn(['seo_title', 'seo_description']));
        Schema::table('news_articles', function (Blueprint $t) {
            $t->dropIndex(['status', 'publish_at']);
            $t->dropColumn('publish_at');
        });
    }
};
