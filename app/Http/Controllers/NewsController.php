<?php

namespace App\Http\Controllers;

use App\Content\PageData;
use Inertia\Inertia;
use Inertia\Response;

/** A news article: the team's summary of a published report, with a link to the original. */
class NewsController
{
    public function __invoke(PageData $data, string $slug): Response
    {
        return Inertia::render('NewsArticlePage', $data->forArticle($slug, app()->getLocale()));
    }
}
