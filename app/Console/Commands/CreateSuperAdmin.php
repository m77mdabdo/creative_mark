<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/** Creates the first Super Admin interactively, so no password ever appears in code, seeders or chat. */
#[Signature('cm:create-admin')]
#[Description('Create a Super Admin account for the Creative Mark dashboard')]
class CreateSuperAdmin extends Command
{
    public function handle(): int
    {
        $this->callSilently('db:seed', ['--class' => RbacSeeder::class, '--force' => true]);

        $name = text('Full name', required: true);
        $email = text('Email', required: true, validate: fn (string $v) => Validator::make(['email' => $v], ['email' => ['email', 'unique:users,email']])->errors()->first('email') ?: null);
        $secret = password('Password (12+ characters, upper and lower case, a number)', required: true,
            validate: fn (string $v) => Validator::make(['password' => $v], ['password' => [Password::defaults()]])->errors()->first('password') ?: null);

        $user = new User(['name' => $name, 'email' => $email, 'password' => $secret]);
        $user->forceFill(['is_active' => true, 'email_verified_at' => now()])->save();
        $user->assignRole(Role::SuperAdmin->value);

        $this->components->info("Super Admin created: {$email}. Sign in at ".url('/admin').' and set up two-factor authentication when asked.');

        return self::SUCCESS;
    }
}
