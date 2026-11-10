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

    public function test_every_role_reaches_the_dashboard_once_signed_in(): void
    {
        foreach ([Role::ContentManager, Role::Editor] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())->get('/admin')->assertOk();
        }
    }

    public function test_mass_assignment_cannot_activate_or_promote(): void
    {
        // Strict models refuse unknown or guarded attributes instead of silently dropping them.
        $this->expectException(MassAssignmentException::class);
        new User(['name' => 'x', 'email' => 'x@example.com', 'password' => 'Secret-password-1', 'is_active' => false]);
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
