<?php

namespace App\Http\Controllers;

use App\Content\PageData;
use App\Support\Locales;
use Inertia\Inertia;
use Inertia\Response;

/** Shows a page's unpublished draft to signed-in editors, exactly as visitors would see it once published. */
class PagePreviewController
{
    public function __invoke(PageData $data, string $key, string $locale): Response
    {
        abort_unless(in_array($locale, Locales::codes(), true), 404);
        app()->setLocale($locale);

        return Inertia::render('ContentPage', [...$data->forPage($key, $locale, draft: true), 'preview' => true])
            ->withViewData(['noindex' => true]);
    }

    /** A case study as it will look, even while it is a draft. */
    public function caseStudy(PageData $data, string $slug, string $locale): Response
    {
        abort_unless(in_array($locale, Locales::codes(), true), 404);
        app()->setLocale($locale);

        return Inertia::render('CaseStudyPage', [...$data->forCaseStudy($slug, $locale, draft: true), 'preview' => true]);
    }

    /** A news story as it will look, even while it is a draft. */
    public function article(PageData $data, string $slug, string $locale): Response
    {
        abort_unless(in_array($locale, Locales::codes(), true), 404);
        app()->setLocale($locale);

        return Inertia::render('NewsArticlePage', [...$data->forArticle($slug, $locale, draft: true), 'preview' => true]);
    }
}
