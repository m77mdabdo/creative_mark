<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasEmailAuthentication
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /** Mirrors the column defaults so new instances are complete before they are saved. */
    protected $attributes = [
        'is_active' => true,
        'admin_locale' => 'ar',
        'last_login_at' => null,
    ];

    /** is_active and roles are never mass-assignable: they change only through user management actions. */
    protected $fillable = ['name', 'email', 'password', 'admin_locale'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /** Only active staff with admin access may enter the panel (deny by default). */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->can('admin.access');
    }

    /** Every sign-in is finished with a code sent to this email (App\Auth\EmailSignInCode); it cannot be switched off. */
    public function hasEmailAuthentication(): bool
    {
        return true;
    }

    public function toggleEmailAuthentication(bool $condition): void {}
}
