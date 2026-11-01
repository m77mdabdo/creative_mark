<?php

use App\Http\Controllers\LocaleRedirectController;
use App\Http\Controllers\PageController;
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
    });
