<?php

namespace App\Http\Controllers;

use App\Content\PageData;
use App\Content\RegionPageBuilder;
use App\Models\Region;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A region's detail page (FR-051). It is an editable CMS page (key "region-{slug}") created from the region record
 * the first time it is needed; the original fixed layout remains as a fallback.
 */
class RegionController
{
    public function __invoke(PageData $data, string $slug): Response
    {
        $region = Region::query()->where('slug', $slug)->live()->with('translations')->firstOrFail();
        $page = RegionPageBuilder::ensure($region);

        return $page->published_revision_id
            ? Inertia::render('ContentPage', $data->forPage($page->key, app()->getLocale()))
            : Inertia::render('RegionPage', $data->forRegion($slug, app()->getLocale()));
    }
}
