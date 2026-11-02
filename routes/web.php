<?php

use App\Http\Controllers\LocaleRedirectController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ServiceController;
use App\Http\Middleware\SetLocale;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

Route::get('/', LocaleRedirectController::class);

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
        Route::get('/regions', PageController::class)->defaults('key', 'regions')->name('regions');
        Route::get('/regions/{slug}', RegionController::class)->where('slug', '[a-z0-9-]+')->name('regions.show');
    });
