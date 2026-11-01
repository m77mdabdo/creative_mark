<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_active')->default(true)->after('password');
            $t->string('admin_locale', 8)->default('ar')->after('is_active');
            // Filament app authentication (TOTP); both stored encrypted via model casts
            $t->text('app_authentication_secret')->nullable()->after('admin_locale');
            $t->text('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');
            $t->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn([
            'is_active', 'admin_locale', 'app_authentication_secret', 'app_authentication_recovery_codes', 'last_login_at',
        ]));
    }
};
