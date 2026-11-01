<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Silent bugs become loud ones in development: lazy loading, unknown attributes, discarded fills.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Super Admin passes every gate (Phase E/F §8 rule 1); every other role is deny-by-default.
        Gate::before(fn (User $user) => $user->is_active && $user->hasRole(Role::SuperAdmin->value) ? true : null);

        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->uncompromised());
    }
}
