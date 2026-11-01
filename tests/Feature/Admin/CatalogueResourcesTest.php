<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Http\Middleware\EnsurePrivilegedRolesUseMfa;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Region;
use App\Models\Service;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Every catalogue screen renders for a Content Manager and is closed to roles without content.edit. */
class CatalogueResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RbacSeeder::class, ContentSeeder::class]);
    }

    public static function resources(): array
    {
        return [
            'services' => ['services', Service::class],
            'regions' => ['regions', Region::class],
            'faqs' => ['faqs', Faq::class],
            'stats' => ['stats', Stat::class],
            'testimonials' => ['testimonials', Testimonial::class],
            'countries' => ['countries', Country::class],
        ];
    }

    #[DataProvider('resources')]
    public function test_content_manager_can_list_and_open_records(string $slug, string $model): void
    {
        $this->actingAs(User::factory()->withRole(Role::Editor)->create()); // Editor: no forced MFA redirect in tests
        $this->get("/admin/{$slug}")->assertOk();

        $this->actingAs(User::factory()->withRole(Role::ContentManager)->create());
        $this->withoutMiddleware(EnsurePrivilegedRolesUseMfa::class);
        $this->get("/admin/{$slug}")->assertOk();
        $this->get("/admin/{$slug}/".$model::query()->value('id').'/edit')->assertOk();
    }

    #[DataProvider('resources')]
    public function test_roles_without_content_edit_are_refused(string $slug): void
    {
        foreach ([Role::Viewer, Role::SalesManager, Role::Consultant] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create());
            $this->withoutMiddleware(EnsurePrivilegedRolesUseMfa::class);
            $this->get("/admin/{$slug}")->assertForbidden();
        }
    }

    public function test_regions_cannot_be_created(): void
    {
        $this->actingAs(User::factory()->withRole(Role::SuperAdmin)->create());
        $this->withoutMiddleware(EnsurePrivilegedRolesUseMfa::class);
        $this->get('/admin/regions/create')->assertNotFound();
    }
}
