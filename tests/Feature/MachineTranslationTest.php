<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Service;
use App\Translation\ContentTranslator;
use App\Translation\TranslationMemory;
use App\Translation\TranslationUnavailable;
use App\Translation\Translator;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\FakeMachineTranslator;
use Tests\TestCase;

class MachineTranslationTest extends TestCase
{
    use RefreshDatabase;

    private FakeMachineTranslator $fake;

    protected function setUp(): void
    {
        parent::setUp();
        // Never write the real lang/ui files from a test.
        $dir = storage_path('framework/testing/ui');
        File::deleteDirectory($dir);
        File::copyDirectory(lang_path('ui'), $dir);
        config(['translation.ui_strings_path' => $dir]);

        $this->fake = new FakeMachineTranslator;
        $this->app->instance(Translator::class, new Translator([$this->fake], config('translation.glossary'), 60, config('translation.phrasebook')));
    }

    private function translator(): Translator
    {
        return app(Translator::class);
    }

    public function test_glossary_terms_are_never_translated(): void
    {
        $this->assertSame('[fr] Creative Mark supports investors', $this->translator()->translateOne('Creative Mark supports investors', 'en', 'fr'));
        $this->assertSame('[ar] كرييتف مارك supports investors', $this->translator()->translateOne('Creative Mark supports investors', 'en', 'ar'));
    }

    public function test_mangled_tokens_fall_back_to_translating_around_the_terms(): void
    {
        $this->app->instance(Translator::class, new Translator([new FakeMachineTranslator(mangleTokens: true)], config('translation.glossary'), 60));
        $out = $this->translator()->translateOne('Why choose Creative Mark today', 'en', 'fr');
        $this->assertStringContainsString('Creative Mark', $out);
        $this->assertStringNotContainsString('ZQ', $out);
    }

    public function test_professional_translation_memory_wins_and_is_not_marked_machine(): void
    {
        $this->app->instance(Translator::class, new Translator([$this->fake], [], 60, [], new TranslationMemory(config('translation.memory_path'))));
        $this->seed(ContentSeeder::class);
        $service = Service::where('slug', 'company-formation')->firstOrFail();

        app(ContentTranslator::class)->translateRecord($service, ['zh']);

        $zh = $service->translations()->where('locale', 'zh')->first();
        $this->assertSame('公司注册设立', $zh->title);
        $this->assertSame(ReviewStatus::Review, $zh->review_status);
        $this->assertSame([], $this->fake->calls);
    }

    public function test_translation_memory_covers_every_master_string_in_every_locale(): void
    {
        $dir = config('translation.memory_path');
        $keys = array_keys(json_decode(file_get_contents("{$dir}/fr.json"), true));
        foreach (['hi', 'ur', 'zh'] as $locale) {
            $this->assertSame($keys, array_keys(json_decode(file_get_contents("{$dir}/{$locale}.json"), true)), $locale);
        }
        $this->assertGreaterThan(390, count($keys));
    }

    public function test_phrasebook_wins_over_the_machine(): void
    {
        $this->assertSame('Notre avantage', $this->translator()->translateOne('Our edge', 'en', 'fr'));
        $this->assertSame([], $this->fake->calls);
    }

    public function test_next_driver_is_used_when_one_is_down_and_results_are_cached(): void
    {
        $backup = new FakeMachineTranslator('backup');
        $this->app->instance(Translator::class, new Translator([new FakeMachineTranslator('primary', down: true), $backup], [], 60));

        $this->assertSame('[hi] Company formation services', $this->translator()->translateOne('Company formation services', 'en', 'hi'));
        $this->translator()->translateOne('Company formation services', 'en', 'hi');
        $this->assertCount(1, $backup->calls);
    }

    public function test_all_drivers_down_raises_a_clear_error(): void
    {
        $this->app->instance(Translator::class, new Translator([new FakeMachineTranslator(down: true)], [], 60));
        $this->expectException(TranslationUnavailable::class);
        $this->translator()->translateOne('Hello world again', 'en', 'fr');
    }

    public function test_records_get_missing_languages_marked_machine_and_reviewed_text_is_kept(): void
    {
        $this->seed(ContentSeeder::class);
        $service = Service::where('slug', 'company-formation')->firstOrFail();
        $service->translations()->create(['locale' => 'fr', 'title' => 'Création de société (relue)', 'review_status' => ReviewStatus::Approved]);

        $written = app(ContentTranslator::class)->translateRecord($service, refresh: true);

        $rows = $service->translations()->get()->keyBy('locale');
        $this->assertSame(3, $written); // hi, ur, zh — ar is human "review" text, fr is approved
        $this->assertSame('Création de société (relue)', $rows['fr']->title);
        $this->assertSame('[zh] Company Formation', $rows['zh']->title);
        $this->assertSame(ReviewStatus::Machine, $rows['zh']->review_status);
        $this->assertSame('تأسيس الشركات', $rows['ar']->title);
    }

    public function test_arabic_only_content_reaches_english_and_the_other_languages(): void
    {
        $faq = Faq::create(['category' => 'setup', 'sort_order' => 99, 'status' => 'published']);
        $faq->translations()->create(['locale' => 'ar', 'question' => 'هل تساعدون في الترخيص؟', 'answer' => 'نعم.', 'review_status' => ReviewStatus::Approved]);

        app(ContentTranslator::class)->translateRecord($faq);

        $rows = $faq->translations()->get()->keyBy('locale');
        $this->assertSame('[en] هل تساعدون في الترخيص؟', $rows['en']->question);
        $this->assertCount(6, $rows);
    }

    public function test_page_blocks_are_translated_in_the_draft_and_go_live_on_publish(): void
    {
        $this->seed(ContentSeeder::class);
        $this->artisan('cm:translate', ['--locale' => ['fr'], '--publish' => true])->assertSuccessful();

        $page = Page::where('key', 'home')->first();
        $this->assertSame('[fr] Company formation, investment licensing and business solutions for investors building in Saudi Arabia.', $page->draft_sections[0]['subtitle']['fr']);
        $this->get('/fr')->assertInertia(fn (Assert $p) => $p
            ->where('sections.0.slides.1.title', '[fr] Riyadh')
            ->where('reviewStatus', 'machine')
            ->where('site.nav.2.label', 'Notre avantage'));
    }

    public function test_command_reports_a_missing_service_instead_of_crashing(): void
    {
        $this->seed(ContentSeeder::class);
        $this->app->instance(Translator::class, new Translator([new FakeMachineTranslator(down: true)], [], 60));
        $this->artisan('cm:translate', ['--locale' => ['zh']])->assertFailed();
    }
}
