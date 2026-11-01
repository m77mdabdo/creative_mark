<?php

namespace Tests\Feature\Admin;

use App\Enums\ReviewStatus;
use App\Enums\Role;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceResourceTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RbacSeeder::class, ContentSeeder::class]);
        $this->service = Service::where('slug', 'company-formation')->firstOrFail();
    }

    private function as(Role $role): User
    {
        $user = User::factory()->withRole($role)->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_content_manager_lists_and_edits_services_and_the_site_follows(): void
    {
        $this->as(Role::ContentManager);
        Livewire::test(ListServices::class)->assertCanSeeTableRecords(Service::orderBy('sort_order')->limit(10)->get())->assertCountTableRecords(11);

        Livewire::test(EditService::class, ['record' => $this->service->getRouteKey()])
            ->assertSchemaStateSet(['translations.en.title' => 'Company Formation', 'translations.ar.title' => 'تأسيس الشركات'])
            ->fillForm(['translations.en.title' => 'Company Setup', 'translations.ar.review_status' => 'approved'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->service->load('translations');
        $this->assertSame('Company Setup', $this->service->t('title', 'en'));
        $this->assertSame(ReviewStatus::Approved, $this->service->translations->firstWhere('locale', 'ar')->review_status);
        $this->get('/en')->assertSee('Company Setup');
    }

    public function test_viewer_and_sales_roles_cannot_open_services(): void
    {
        foreach ([Role::Viewer, Role::SalesAgent, Role::SeoManager] as $role) {
            $this->as($role);
            $this->get('/admin/services')->assertForbidden();
        }
    }

    public function test_editor_cannot_change_a_published_service(): void
    {
        $this->as(Role::Editor);
        $this->get("/admin/services/{$this->service->id}/edit")->assertForbidden();
    }

    public function test_editor_creates_drafts_only_and_their_text_goes_to_review(): void
    {
        $this->as(Role::Editor);
        Livewire::test(CreateService::class)
            ->fillForm([
                'slug' => 'new-service', 'sort_order' => 12, 'status' => 'published',
                'translations.en.title' => 'New service', 'translations.ar.title' => 'خدمة جديدة', 'translations.ar.review_status' => 'approved',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Service::where('slug', 'new-service')->with('translations')->firstOrFail();
        $this->assertSame('draft', $created->status);
        $this->assertSame(ReviewStatus::Review, $created->translations->firstWhere('locale', 'ar')->review_status);
        $this->assertStringNotContainsString('New service', $this->get('/en')->getContent());
    }

    public function test_slug_must_be_safe_and_unique(): void
    {
        $this->as(Role::ContentManager);
        Livewire::test(CreateService::class)
            ->fillForm(['slug' => 'Bad Slug!', 'sort_order' => 1, 'translations.en.title' => 'x'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
        Livewire::test(CreateService::class)
            ->fillForm(['slug' => 'company-formation', 'sort_order' => 1, 'translations.en.title' => 'x'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }
}
