<?php

namespace App\Http\Controllers;

use App\Content\PageData;
use Inertia\Inertia;
use Inertia\Response;

/** A business solution's detail page (FR-031..033). */
class ServiceController
{
    public function __invoke(PageData $data, string $slug): Response
    {
        return Inertia::render('ServicePage', $data->forService($slug, app()->getLocale()));
    }
}
