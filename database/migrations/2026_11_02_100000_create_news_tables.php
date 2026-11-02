<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Newsroom: market news summarised by the team, each pointing to the original report.
 * Facts are per locale (value + label lists of equal length) because units and wording change by language.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_articles', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 120)->unique();
            $t->string('category', 32);
            $t->string('media', 100)->nullable();
            $t->date('published_on'); // date of the original report
            $t->string('source_name', 120);
            $t->string('source_url', 500);
            $t->boolean('featured')->default(false);
            $t->string('status', 16)->default('draft');
            $t->timestamps();
            $t->index(['status', 'published_on']);
        });

        Schema::create('news_article_translations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('news_article_id')->constrained()->cascadeOnDelete();
            $t->string('locale', 8);
            $t->string('title', 300);
            $t->string('excerpt', 600);
            $t->text('body');
            $t->string('takeaway', 1000)->nullable(); // "What it means for investors"
            $t->json('fact_values')->nullable();
            $t->json('fact_labels')->nullable();
            $t->string('review_status', 16)->default('missing'); // App\Enums\ReviewStatus
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->unique(['news_article_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_translations');
        Schema::dropIfExists('news_articles');
    }
};
