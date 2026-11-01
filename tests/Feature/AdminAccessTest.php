<?php

namespace Tests\Feature;

use App\Auth\PermissionMatrix;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }

    public function test_a_user_without_a_role_cannot_enter(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_a_deactivated_user_cannot_enter(): void
    {
        $user = User::factory()->withRole(Role::SuperAdmin)->inactive()->create();
        $this->actingAs($user->fresh())->get('/admin')->assertForbidden();
    }

    public function test_roles_requiring_mfa_are_sent_to_set_it_up(): void
    {
        $user = User::factory()->withRole(Role::ContentManager)->create();
        $this->actingAs($user)->get('/admin')->assertRedirectContains('multi-factor');
    }

    public function test_roles_without_mandatory_mfa_reach_the_dashboard(): void
    {
        $user = User::factory()->withRole(Role::Editor)->create();
        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_mass_assignment_cannot_activate_or_promote(): void
    {
        // Strict models refuse unknown or guarded attributes instead of silently dropping them.
        $this->expectException(MassAssignmentException::class);
        new User(['name' => 'x', 'email' => 'x@example.com', 'password' => 'Secret-password-1', 'is_active' => false]);
    }

    public function test_mfa_secret_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $raw = \DB::table('users')->where('id', $user->id)->value('app_authentication_secret');
        $this->assertNotSame('JBSWY3DPEHPK3PXP', $raw);
        $this->assertSame('JBSWY3DPEHPK3PXP', $user->fresh()->getAppAuthenticationSecret());
    }

    /** @return iterable<string, array{Role, string, bool}> 8 roles × 25 permissions, generated from the approved matrix. */
    public static function matrix(): iterable
    {
        foreach (Role::cases() as $role) {
            foreach (PermissionMatrix::all() as $permission => $roles) {
                yield "{$role->value} {$permission}" => [$role, $permission, $role === Role::SuperAdmin || in_array($role, $roles, true)];
            }
        }
    }

    #[DataProvider('matrix')]
    public function test_permission_matrix(Role $role, string $permission, bool $allowed): void
    {
        $user = User::factory()->withRole($role)->create();
        $this->assertSame($allowed, $user->can($permission), "{$role->value} → {$permission}");
    }
}
