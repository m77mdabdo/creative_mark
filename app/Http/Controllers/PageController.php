<?php

namespace App\Http\Controllers;

use App\Content\PageData;
use Inertia\Inertia;
use Inertia\Response;

/** Renders any CMS page by its key (routes pass the key as a default). */
class PageController
{
    public function __invoke(PageData $data, string $key = 'home'): Response
    {
        return Inertia::render('ContentPage', $data->forPage($key, app()->getLocale()));
    }
}
