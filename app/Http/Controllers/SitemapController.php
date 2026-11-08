<?php

namespace App\Http\Controllers;

use App\Models\CaseStudy;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Region;
use App\Models\Service;
use App\Support\Locales;
use Illuminate\Http\Response;

/** XML sitemap of every public page in all six languages, with hreflang alternates, and robots.txt. */
class SitemapController
{
    public function sitemap(): Response
    {
        $paths = Page::query()->whereNotNull('published_revision_id')->where('key', 'not like', 'service-%')->where('key', 'not like', 'region-%')->pluck('key')
            ->map(fn ($key) => $key === 'home' ? '' : "/{$key}")
            ->merge(Service::query()->live()->pluck('slug')->map(fn ($s) => "/services/{$s}"))
            ->merge(Region::query()->live()->pluck('slug')->map(fn ($s) => "/regions/{$s}"))
            ->merge(NewsArticle::query()->live()->pluck('slug')->map(fn ($s) => "/news/{$s}"))
            ->merge(CaseStudy::query()->live()->pluck('slug')->map(fn ($s) => "/case-studies/{$s}"))
            ->push('/consultation');

        $base = rtrim(config('app.url'), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
        foreach ($paths as $path) {
            $alternates = collect(Locales::codes())->map(fn ($l) => '<xhtml:link rel="alternate" hreflang="'.Locales::tag($l).'" href="'.e("{$base}/{$l}{$path}").'"/>')->implode('')
                .'<xhtml:link rel="alternate" hreflang="x-default" href="'.e("{$base}/".Locales::FALLBACK.$path).'"/>';
            foreach (Locales::codes() as $locale) {
                $xml .= '<url><loc>'.e("{$base}/{$locale}{$path}").'</loc>'.$alternates."</url>\n";
            }
        }

        return response($xml."</urlset>\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /'.trim(config('admin.path'), '/').'/', 'Disallow: /*/consultation/thank-you', 'Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml']
            : ['User-agent: *', 'Disallow: /']; // never index local or staging copies

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
