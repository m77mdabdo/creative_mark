<?php

namespace App\Http\Controllers;

use App\Content\HomePageData;
use Inertia\Inertia;
use Inertia\Response;

class HomeController
{
    public function __invoke(HomePageData $data): Response
    {
        return Inertia::render('Home', $data->forLocale(app()->getLocale()));
    }
}
