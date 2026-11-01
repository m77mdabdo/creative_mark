<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Content domain for the public site (ADR-0003, ADR-0004).
 * Every translatable record has a *_translations table: one row per locale with its own review status.
 * Only features available on MariaDB 10.4 / MySQL 8 / SQLite are used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('group', 64);
            $t->string('key', 64);
            $t->json('value')->nullable();
            $t->boolean('is_sensitive')->default(false);
            $t->timestamps();
            $t->unique(['group', 'key']);
        });

        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('key', 64)->unique();
            $t->string('status', 16)->default('draft');
            $t->json('draft_sections');
            $t->unsignedBigInteger('published_revision_id')->nullable();
            $t->unsignedInteger('lock_version')->default(0);
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        Schema::create('page_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('page_id')->constrained()->cascadeOnDelete();
            $t->json('sections');
            $t->string('kind', 16)->default('publish');
            $t->string('note', 255)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->nullable();
            $t->index(['page_id', 'created_at']);
        });

        Schema::table('pages', function (Blueprint $t) {
            $t->foreign('published_revision_id')->references('id')->on('page_revisions')->nullOnDelete();
        });

        $this->translations('page', function (Blueprint $t) {
            $t->string('slug', 191)->nullable();
            $t->string('title', 255);
            $t->string('seo_title', 255)->nullable();
            $t->string('seo_description', 500)->nullable();
        }, uniqueSlug: true);

        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 191)->unique();
            $t->string('icon', 64)->nullable();
            $t->string('media', 191)->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->string('status', 16)->default('draft');
            $t->boolean('needs_review')->default(false); // summary quotes a figure not yet verified (RULE-01)
            $t->timestamps();
            $t->index(['status', 'sort_order']);
        });
        $this->translations('service', function (Blueprint $t) {
            $t->string('title', 255);
            $t->string('tagline', 255)->nullable();
            $t->text('summary')->nullable();
            $t->string('cta', 120)->nullable();
        });

        Schema::create('regions', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 64)->unique(); // also the map shape key
            $t->string('media', 191)->nullable();
            $t->json('industries')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->string('status', 16)->default('draft');
            $t->string('source', 120)->nullable();
            $t->timestamps();
        });
        $this->translations('region', function (Blueprint $t) {
            $t->string('name', 120);
            $t->string('capital', 120)->nullable();
            $t->string('best_for', 255)->nullable();
            $t->text('description')->nullable();
            $t->json('opportunities')->nullable();
        });
        Schema::create('region_service', function (Blueprint $t) {
            $t->foreignId('region_id')->constrained()->cascadeOnDelete();
            $t->foreignId('service_id')->constrained()->cascadeOnDelete();
            $t->primary(['region_id', 'service_id']);
        });

        Schema::create('stats', function (Blueprint $t) {
            $t->id();
            $t->string('key', 64)->unique();
            $t->string('value', 32);
            $t->string('group', 16)->default('primary');
            $t->boolean('verified')->default(false);
            $t->string('source', 255)->nullable();
            $t->string('source_url', 500)->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });
        $this->translations('stat', function (Blueprint $t) {
            $t->string('label', 191);
        });

        Schema::create('countries', function (Blueprint $t) {
            $t->id();
            $t->char('code', 2)->unique();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->string('status', 16)->default('published');
            $t->timestamps();
        });
        $this->translations('country', function (Blueprint $t) {
            $t->string('name', 120);
        });

        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->string('category', 32);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->string('status', 16)->default('draft');
            $t->boolean('show_on_home')->default(false);
            $t->boolean('needs_review')->default(false); // contains a figure or claim not yet verified (RULE-01)
            $t->timestamps();
        });
        $this->translations('faq', function (Blueprint $t) {
            $t->string('question', 500);
            $t->text('answer');
        });

        Schema::create('testimonials', function (Blueprint $t) {
            $t->id();
            $t->boolean('verified')->default(false);
            $t->timestamp('consent_recorded_at')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->string('status', 16)->default('draft');
            $t->timestamps();
        });
        $this->translations('testimonial', function (Blueprint $t) {
            $t->text('quote');
            $t->string('author', 191)->nullable();
            $t->string('context', 191)->nullable();
        });
    }

    private function translations(string $model, Closure $fields, bool $uniqueSlug = false): void
    {
        $parent = $model.'_id';
        Schema::create($model.'_translations', function (Blueprint $t) use ($model, $parent, $fields, $uniqueSlug) {
            $t->id();
            $t->foreignId($parent)->constrained(str($model)->plural()->value())->cascadeOnDelete();
            $t->string('locale', 8);
            $fields($t);
            $t->string('review_status', 16)->default('missing'); // App\Enums\ReviewStatus
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->unique([$parent, 'locale']);
            if ($uniqueSlug) {
                $t->unique(['locale', 'slug']);
            }
        });
    }

    public function down(): void
    {
        foreach (['testimonial', 'faq', 'country', 'stat', 'region', 'service', 'page'] as $m) {
            Schema::dropIfExists($m.'_translations');
        }
        Schema::dropIfExists('region_service');
        foreach (['testimonials', 'faqs', 'countries', 'stats', 'regions', 'services'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('pages', fn (Blueprint $t) => $t->dropForeign(['published_revision_id']));
        Schema::dropIfExists('page_revisions');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('settings');
    }
};
