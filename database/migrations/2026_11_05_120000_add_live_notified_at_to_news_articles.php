<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** When the team was told that a scheduled story went live, so each story is announced only once. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', fn (Blueprint $t) => $t->dateTime('live_notified_at')->nullable()->after('publish_at'));
    }

    public function down(): void
    {
        Schema::table('news_articles', fn (Blueprint $t) => $t->dropColumn('live_notified_at'));
    }
};
