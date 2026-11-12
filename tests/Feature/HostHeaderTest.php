<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Generated links must point at APP_URL whatever Host header the request carried (host-header poisoning), and the
 * live site answers only its own host.
 */
class HostHeaderTest extends TestCase
{
    protected function tearDown(): void
    {
        // The trusted-host list is static on Symfony's Request.
        Request::setTrustedHosts([]);

        parent::tearDown();
    }

    /** Boot as on the live server: production, with the real APP_URL. */
    private function production(): void
    {
        config(['app.url' => 'https://site.example.sa']);
        $this->app->detectEnvironment(fn () => 'production');
        $this->app->getProvider(AppServiceProvider::class)->boot();
    }

    public function test_a_forged_host_does_not_change_generated_links(): void
    {
        $this->production();

        $forged = Request::create('http://attacker.test/admin/login', 'POST');
        $this->app->instance('request', $forged);
        $this->app['url']->setRequest($forged);

        $link = route('filament.admin.auth.login');

        $this->assertStringStartsWith('https://site.example.sa/', $link);
        $this->assertStringNotContainsString('attacker.test', $link);
    }

    public function test_requests_for_another_host_are_refused_in_production(): void
    {
        $this->production();

        $this->get('https://site.example.sa/up')->assertOk();
        $this->get('https://attacker.test/up')->assertStatus(400);
    }

    public function test_production_sends_a_report_only_content_security_policy(): void
    {
        $this->production();

        $this->get('https://site.example.sa/admin/login')
            ->assertHeader('Content-Security-Policy-Report-Only', SecurityHeaders::POLICY)
            ->assertHeaderMissing('Content-Security-Policy');
    }
}
