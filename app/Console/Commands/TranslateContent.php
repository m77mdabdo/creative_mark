<?php

namespace App\Console\Commands;

use App\Content\PublishPage;
use App\Models\CaseStudy;
use App\Models\Country;
use App\Models\Faq;
use App\Models\MediaAsset;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Region;
use App\Models\Service;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Support\Locales;
use App\Translation\ContentTranslator;
use App\Translation\TranslationUnavailable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Fills every missing language of the site with machine translations (marked "machine" until reviewed). */
#[Signature('cm:translate
    {--locale=* : Only these locales (default: all six)}
    {--refresh : Redo earlier machine translations (never human-reviewed text)}
    {--publish : Publish the translated pages so the live site shows them}')]
#[Description('Machine translate missing languages of CMS content, settings and interface labels')]
class TranslateContent extends Command
{
    private const MODELS = [Service::class, Region::class, Faq::class, Stat::class, Testimonial::class, Country::class, NewsArticle::class, CaseStudy::class, MediaAsset::class];

    public function handle(ContentTranslator $translator): int
    {
        $targets = $this->option('locale') ?: null;
        if ($targets && array_diff($targets, Locales::codes())) {
            $this->components->error('Unknown locale. Use: '.implode(', ', Locales::codes()));

            return self::INVALID;
        }
        $refresh = (bool) $this->option('refresh');

        try {
            $this->components->task('Interface labels', fn () => $translator->translateInterface($targets, $refresh) >= 0);
            $this->components->task('Settings (navigation, offices, labels)', fn () => $translator->translateSettings($targets, $refresh) >= 0);

            foreach (self::MODELS as $model) {
                $this->components->task(class_basename($model), function () use ($model, $translator, $targets, $refresh) {
                    $model::query()->each(fn ($record) => $translator->translateRecord($record, $targets, $refresh));
                });
            }

            foreach (Page::all() as $page) {
                $this->components->task("Page: {$page->key}", fn () => $translator->translatePage($page, $targets, $refresh) >= 0);
                if ($this->option('publish')) {
                    (new PublishPage)($page->fresh(), note: 'Machine translations added');
                }
            }
        } catch (TranslationUnavailable $e) {
            $this->components->error($e->getMessage());
            $this->line('Start the translation service: docker compose -f infra/translation/docker-compose.yml up -d');

            return self::FAILURE;
        }

        $this->components->info('Done. Machine translations are marked for review in the dashboard.');

        return self::SUCCESS;
    }
}
