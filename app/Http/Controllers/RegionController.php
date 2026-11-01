<?php

namespace App\Http\Controllers;

use App\Content\PageData;
use Inertia\Inertia;
use Inertia\Response;

/** A region's detail page (FR-051). */
class RegionController
{
    public function __invoke(PageData $data, string $slug): Response
    {
        return Inertia::render('RegionPage', $data->forRegion($slug, app()->getLocale()));
    }
}
