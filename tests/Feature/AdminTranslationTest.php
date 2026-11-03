<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Middleware\EnsurePrivilegedRolesUseMfa;
use App\Models\ConsultationRequest;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Arabic dashboard shows no untranslated English interface text: every admin page is opened as a Super Admin
 * in Arabic and every string it looks up must have an Arabic translation.
 */
class AdminTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_arabic_dashboard_has_no_untranslated_interface_text(): void
    {
        $this->seed([RbacSeeder::class, ContentSeeder::class]);
        $this->withoutMiddleware(EnsurePrivilegedRolesUseMfa::class);
        $admin = User::factory()->withRole(Role::SuperAdmin)->create(['name' => 'مدير', 'email' => 'boss@example.org']);
        $admin->forceFill(['admin_locale' => 'ar'])->save();
        ConsultationRequest::create([
            'reference' => 'CM-261103-TEST', 'name' => 'عميل', 'email' => 'c@example.org', 'phone' => '+966500000000',
            'meeting_mode' => 'video', 'preferred_date' => now()->addDays(3)->toDateString(), 'preferred_slot' => '10:00',
            'locale' => 'ar', 'consent_at' => now(), 'stage' => 'ready',
        ]);

        $urls = ['/admin', '/admin/profile', '/admin/pages/home', '/admin/pages/journey', '/admin/settings/contact'];
        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            foreach (['index', 'create', 'view', 'edit'] as $page) {
                if (! $resource::hasPage($page)) {
                    continue;
                }
                $record = in_array($page, ['view', 'edit'], true) ? $resource::getModel()::query()->first() : null;
                if (in_array($page, ['view', 'edit'], true) && ! $record) {
                    continue;
                }
                $urls[] = parse_url($resource::getUrl($page, $record ? ['record' => $record] : []), PHP_URL_PATH);
            }
        }

        // Every interface string the dashboard asks for in Arabic must exist in lang/ar.json (or Filament's own files).
        $missing = [];
        app('translator')->handleMissingKeysUsing(function (string $key, array $replace, ?string $locale) use (&$missing) {
            // Skipped: vendor keys, labels that are already Arabic (translated twice) and language names shown natively.
            if ($locale === 'ar' && ! str_contains($key, '::') && ! preg_match('/[^\x00-\x7F]/u', $key) && $key !== 'English') {
                $missing[$key] = true;
            }

            return $key;
        });
        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        ksort($missing);
        $this->assertSame([], array_keys($missing), 'Untranslated dashboard strings (add them to lang/ar.json)');
    }
}
