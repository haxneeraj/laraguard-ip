<?php

namespace Haxneeraj\LaraguardIp\Tests\Feature;

use Haxneeraj\LaraguardIp\Http\Middleware\LaraguardIpMiddleware;
use Haxneeraj\LaraguardIp\Providers\LaraguardIpServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class LaraguardIpMiddlewareTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaraguardIpServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Route::middleware(LaraguardIpMiddleware::class)->get('/test-ip', function () {
            return 'ok';
        });

        // Load default configuration
        $this->app['config']->set('laraguard-ip', include __DIR__.'/../../config/laraguard-ip.php');
    }

    /** @test */
    public function it_allows_access_when_all_protections_are_disabled()
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/test-ip');
        $response->assertStatus(200);
        $response->assertSee('ok');
    }

    /** @test */
    public function it_blocks_ips_in_spam_list_when_spam_protection_enabled()
    {
        // Enable spam protection and add IP to spam list
        config(['laraguard-ip.laraguardip_spam_protection' => true]);
        $blockedIp = ip2long('1.0.0.11');
        Cache::forever('laraguardip', [$blockedIp]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '1.0.0.11'])->get('/test-ip');
        $response->assertStatus(403);
        $response->assertSee('Blacklisted IP');
    }

    /** @test */
    public function it_allows_ips_not_in_spam_list_when_spam_protection_enabled()
    {
        config(['laraguard-ip.laraguardip_spam_protection' => true]);
        Cache::forever('laraguardip', [ip2long('1.0.0.11')]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/test-ip');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_enforces_whitelist_only_access()
    {
        config([
            'laraguard-ip.whitelist_only_access' => true,
            'laraguard-ip.whitelist_ips' => ['192.168.0.1'],
        ]);

        // Test allowed IP
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.0.1'])->get('/test-ip');
        $response->assertStatus(200);

        // Test blocked IP
        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/test-ip');
        $response->assertStatus(403);
        $response->assertSee('IP not allowed');
    }

    /** @test */
    public function it_enforces_country_whitelist()
    {
        config([
            'laraguard-ip.country_whitelist_only' => true,
            'laraguard-ip.country_whitelist' => ['US'], // Middleware returns 'US' by default
        ]);

        // Test allowed country (US)
        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/test-ip');
        $response->assertStatus(200);

        config(['laraguard-ip.country_whitelist' => ['CA']]);

        // Test blocked country
        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/test-ip');
        $response->assertStatus(403);
        $response->assertSee('Country not allowed');
    }

    /** @test */
    public function it_handles_missing_spam_list_gracefully()
    {
        config(['laraguard-ip.laraguardip_spam_protection' => true]);
        Cache::forget('laraguardip');

        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/test-ip');
        $response->assertStatus(200);
    }
}
