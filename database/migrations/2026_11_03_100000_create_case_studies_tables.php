<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Case studies: a client's starting point, the steps taken, what was delivered and where it leads.
 * `illustrative` marks examples built from the standard engagement rather than a named client (RULE-02, RULE-03):
 * they always carry a visible label on the site. Client names appear only with recorded consent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_studies', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 120)->unique();
            $t->string('sector', 32); // catalogue industry key
            $t->string('origin', 2); // country code (countries table)
            $t->string('region', 32); // region slug
            $t->string('media', 100)->nullable();
            $t->json('services')->nullable(); // service slugs, in display order
            $t->boolean('illustrative')->default(true);
            $t->timestamp('consent_recorded_at')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->string('status', 16)->default('draft');
            $t->timestamps();
        });

        Schema::create('case_study_translations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $t->string('locale', 8);
            $t->string('title', 300);
            $t->string('client', 200); // "A Turkish industrial manufacturer" or, with consent, the client's name
            $t->string('entity', 200)->nullable(); // legal form chosen
            $t->string('summary', 600);
            $t->text('challenge');
            $t->json('step_titles')->nullable();
            $t->json('step_texts')->nullable();
            $t->json('delivered')->nullable();
            $t->text('outcome');
            $t->string('review_status', 16)->default('missing'); // App\Enums\ReviewStatus
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->unique(['case_study_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_study_translations');
        Schema::dropIfExists('case_studies');
    }
};
