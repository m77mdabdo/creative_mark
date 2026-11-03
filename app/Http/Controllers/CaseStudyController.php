<?php

namespace App\Http\Controllers;

use App\Content\PageData;
use Inertia\Inertia;
use Inertia\Response;

/** A case study: starting point, steps, what was delivered and where it leads. */
class CaseStudyController
{
    public function __invoke(PageData $data, string $slug): Response
    {
        return Inertia::render('CaseStudyPage', $data->forCaseStudy($slug, app()->getLocale()));
    }
}
