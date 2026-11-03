<?php

use App\Http\Controllers\AdminLocaleController;
use App\Http\Controllers\CaseStudyController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\LocaleRedirectController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PagePreviewController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\SetLocale;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

Route::get('/', LocaleRedirectController::class);
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots']);
Route::get('/preview/{key}/{locale}', PagePreviewController::class)->where('key', '[a-z0-9-]+')
    ->middleware(['auth', 'can:pages.edit'])->name('admin.pages.preview');
Route::post('/'.trim(config('admin.path'), '/').'/locale', AdminLocaleController::class)->middleware(['auth', 'throttle:30,1'])->name('admin.locale');

Route::prefix('{locale}')
    ->where(['locale' => implode('|', Locales::codes())])
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', PageController::class)->name('home');
        Route::get('/about', PageController::class)->defaults('key', 'about')->name('about');
        Route::get('/services', PageController::class)->defaults('key', 'services')->name('services');
        Route::get('/services/{slug}', ServiceController::class)->where('slug', '[a-z0-9-]+')->name('services.show');
        Route::get('/our-edge', PageController::class)->defaults('key', 'our-edge')->name('our-edge');
        Route::get('/investor-guide', PageController::class)->defaults('key', 'investor-guide')->name('investor-guide');
        Route::get('/news', PageController::class)->defaults('key', 'news')->name('news');
        Route::get('/news/{slug}', NewsController::class)->where('slug', '[a-z0-9-]+')->name('news.show');
        Route::get('/start-your-company', PageController::class)->defaults('key', 'start-your-company')->name('start-your-company');
        Route::get('/faq', PageController::class)->defaults('key', 'faq')->name('faq');
        Route::get('/contact', PageController::class)->defaults('key', 'contact')->name('contact');
        Route::get('/privacy', PageController::class)->defaults('key', 'privacy')->name('privacy');
        Route::get('/terms', PageController::class)->defaults('key', 'terms')->name('terms');
        Route::get('/consultation', [ConsultationController::class, 'show'])->name('consultation');
        Route::post('/consultation', [ConsultationController::class, 'store'])->middleware('throttle:20,1')->name('consultation.store');
        Route::get('/consultation/thank-you', [ConsultationController::class, 'thanks'])->name('consultation.thanks');
        Route::get('/case-studies', PageController::class)->defaults('key', 'case-studies')->name('case-studies');
        Route::get('/case-studies/{slug}', CaseStudyController::class)->where('slug', '[a-z0-9-]+')->name('case-studies.show');
        Route::get('/regions', PageController::class)->defaults('key', 'regions')->name('regions');
        Route::get('/regions/{slug}', RegionController::class)->where('slug', '[a-z0-9-]+')->name('regions.show');
    });
