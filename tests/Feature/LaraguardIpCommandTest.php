<?php

namespace Haxneeraj\LaraguardIp\Tests\Unit;

use Haxneeraj\LaraguardIp\Console\Commands\LaraguardIpCommand;
use Haxneeraj\LaraguardIp\Providers\LaraguardIpServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class LaraguardIpCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaraguardIpServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Setup fake storage
        Storage::fake('local');

        // Configure package
        config([
            'laraguard-ip' => [
                'compress' => true,
                'path' => storage_path('app/laraguard-ip.json'),
                'sources' => ['http://example.com/ips'],
                'laraguardip_spam_protection' => true,
            ],
        ]);
    }

    /** @test */
    public function it_successfully_fetches_and_processes_ips()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://example.com/ips' => Http::response("1.1.1.1 # Bad IP\n2.2.2.2\n3.3.3.3\n2.2.2.2", 200),
        ]);

        $this->artisan(LaraguardIpCommand::class)
            ->expectsOutput('Fetching spam ips from the sources...')
            ->expectsOutput('Compressing spam ips...')
            ->expectsOutput('Saving spam ips to laraguard-ip.json...')
            ->expectsOutput('Caching spam ips...')
            ->expectsOutput('Spam ips cached successfully!')
            ->assertExitCode(0);

        // Verify file contents
        $fileContents = json_decode(file_get_contents(config('laraguard-ip.path')), true);
        $this->assertEquals([
            ip2long('1.1.1.1'),
            ip2long('2.2.2.2'),
            ip2long('3.3.3.3'),
        ], $fileContents);

        // Verify cache
        $this->assertEquals(
            [ip2long('1.1.1.1'), ip2long('2.2.2.2'), ip2long('3.3.3.3')],
            Cache::get('laraguardip')
        );
    }

    /** @test */
    public function it_handles_failed_source_fetching()
    {
        Http::fake([
            'http://example.com/ips' => Http::response(null, 500),
        ]);

        $this->artisan(LaraguardIpCommand::class)
            ->expectsOutput('Fetching spam ips from the sources...')
            ->expectsOutput('Failed to fetch ips from http://example.com/ips')
            ->expectsOutput('Failed to fetch spam ips.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_uncompressed_ips()
    {
        config(['laraguard-ip.compress' => false]);

        Http::fake([
            'http://example.com/ips' => Http::response("1.1.1.1\n2.2.2.2", 200),
        ]);

        $this->artisan(LaraguardIpCommand::class);

        $fileContents = json_decode(file_get_contents(config('laraguard-ip.path')), true);
        $this->assertEquals(['1.1.1.1', '2.2.2.2'], $fileContents);
    }

    /** @test */
    public function it_filters_invalid_ips()
    {
        Http::fake([
            'http://example.com/ips' => Http::response("invalid\n256.256.256.256\n4.4.4.4", 200),
        ]);

        $this->artisan(LaraguardIpCommand::class);

        $fileContents = json_decode(file_get_contents(config('laraguard-ip.path')), true);
        $this->assertEquals([ip2long('4.4.4.4')], $fileContents);
    }

    /** @test */
    public function it_handles_file_write_failure()
    {
        Http::fake(['http://example.com/ips' => Http::response('1.1.1.1', 200)]);

        // Make path unwritable
        config(['laraguard-ip.path' => '/invalid/path/laraguard-ip.json']);

        $this->artisan(LaraguardIpCommand::class)
            ->expectsOutput('Failed to save spam ips.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_cache_failure_gracefully()
    {
        Http::fake([
            'http://example.com/ips' => Http::response('1.1.1.1', 200),
        ]);

        // Mock cache forever to throw exception
        Cache::shouldReceive('forever')->andThrow(new \Exception);

        // Mock cache forget to avoid method not found error
        Cache::shouldReceive('forget')->once()->with('laraguardip');

        $this->artisan(LaraguardIpCommand::class)
            ->expectsOutput('IP spam list saved to file, but is too long to cache in database')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_removes_duplicates()
    {
        Http::fake([
            'http://example.com/ips' => Http::response("1.1.1.1\n1.1.1.1\n2.2.2.2", 200),
        ]);

        $this->artisan(LaraguardIpCommand::class);

        $fileContents = json_decode(file_get_contents(config('laraguard-ip.path')), true);
        $this->assertCount(2, $fileContents);
    }
}
