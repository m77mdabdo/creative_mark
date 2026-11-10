<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sign-in codes now arrive by email (App\Auth\EmailSignInCode), so the authenticator-app secrets are no longer used.
 * They are removed rather than left behind: a secret that is not needed should not be stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['app_authentication_secret', 'app_authentication_recovery_codes']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->text('app_authentication_secret')->nullable()->after('admin_locale');
            $t->text('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');
        });
    }
};
