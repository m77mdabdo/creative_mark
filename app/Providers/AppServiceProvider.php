<?php

namespace App\Providers;

use App\Support\CanonicalUrl;
use App\Audit\Audit;
use App\Enums\Role;
use App\Models\CaseStudy;
use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Models\Faq;
use App\Models\NewsArticle;
use App\Models\NotificationRecipient;
use App\Models\Region;
use App\Models\Service;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Models\User;
use App\Policies\CataloguePolicy;
use App\Policies\ConsultationPolicy;
use App\Policies\NotificationRecipientPolicy;
use App\Policies\UserPolicy;
use App\Translation\Drivers\LibreTranslateDriver;
use App\Translation\Drivers\MyMemoryDriver;
use App\Translation\TranslationMemory;
use App\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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

        // Links in emails always point at APP_URL, whatever Host header a request carried.
        CanonicalUrl::apply($this->app);

        // Super Admin passes every gate (Phase E/F §8 rule 1); every other role is deny-by-default.
        Gate::before(fn (User $user) => $user->is_active && $user->hasRole(Role::SuperAdmin->value) ? true : null);

        foreach ([Service::class, Region::class, Faq::class, Stat::class, Testimonial::class, Country::class, NewsArticle::class, CaseStudy::class] as $model) {
            Gate::policy($model, CataloguePolicy::class);
        }

        Gate::policy(ConsultationRequest::class, ConsultationPolicy::class);
        Gate::policy(NotificationRecipient::class, NotificationRecipientPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Activity log: staff changes to any content model, and sign-ins.
        foreach (['created', 'updated', 'deleted'] as $event) {
            Event::listen("eloquent.{$event}: App\\Models\\*", fn (string $name, array $data) => Audit::modelEvent($event, $data[0]));
        }
        Event::listen(Login::class, function (Login $e) {
            if ($e->user instanceof User) {
                $e->user->forceFill(['last_login_at' => now()])->saveQuietly();
                Audit::record('login', $e->user, user: $e->user);
            }
        });
        Event::listen(Logout::class, fn (Logout $e) => $e->user instanceof User ? Audit::record('logout', $e->user, user: $e->user) : null);
        Event::listen(Failed::class, fn (Failed $e) => Audit::record('login_failed', null, (string) ($e->credentials['email'] ?? ''), type: 'User'));

        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->uncompromised());
    }
}
