<?php

namespace App\Http\Controllers;

use App\Content\PageData;
use App\Content\ServicePageBuilder;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A business solution's detail page (FR-031..033). It is an editable CMS page (key "service-{slug}") created from
 * the service record the first time it is needed; the original fixed layout remains as a fallback.
 */
class ServiceController
{
    public function __invoke(PageData $data, string $slug): Response
    {
        $service = Service::query()->where('slug', $slug)->where('status', 'published')->with('translations')->firstOrFail();
        $page = ServicePageBuilder::ensure($service);

        return $page->published_revision_id
            ? Inertia::render('ContentPage', $data->forPage($page->key, app()->getLocale()))
            : Inertia::render('ServicePage', $data->forService($slug, app()->getLocale()));
    }
}
