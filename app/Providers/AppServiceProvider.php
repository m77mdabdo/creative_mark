<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\CaseStudy;
use App\Models\Country;
use App\Models\Faq;
use App\Models\NewsArticle;
use App\Models\Region;
use App\Models\Service;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Models\User;
use App\Policies\CataloguePolicy;
use App\Translation\Drivers\LibreTranslateDriver;
use App\Translation\Drivers\MyMemoryDriver;
use App\Translation\TranslationMemory;
use App\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Translator::class, function () {
            $drivers = array_map(fn (string $name) => match ($name) {
                'libretranslate' => new LibreTranslateDriver(config('translation.libretranslate')),
                'mymemory' => new MyMemoryDriver(config('translation.mymemory')),
            }, config('translation.drivers'));

            return new Translator(
                $drivers,
                config('translation.glossary'),
                config('translation.cache_ttl'),
                config('translation.phrasebook'),
                new TranslationMemory(config('translation.memory_path')),
            );
        });
    }

    public function boot(): void
    {
        // Silent bugs become loud ones in development: lazy loading, unknown attributes, discarded fills.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Super Admin passes every gate (Phase E/F §8 rule 1); every other role is deny-by-default.
        Gate::before(fn (User $user) => $user->is_active && $user->hasRole(Role::SuperAdmin->value) ? true : null);

        foreach ([Service::class, Region::class, Faq::class, Stat::class, Testimonial::class, Country::class, NewsArticle::class, CaseStudy::class] as $model) {
            Gate::policy($model, CataloguePolicy::class);
        }

        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->uncompromised());
    }
}
