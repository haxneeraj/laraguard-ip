<?php

namespace Haxneeraj\LaraguardIp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LaraguardIpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laraguardip:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'LaraguardIpCommand';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Fetching spam ips from the sources...');

        // Fetch spam ips
        $ips = $this->fetchSpamIps(config('laraguard-ip.sources'));

        // return if empty with error message
        if (empty($ips)) {
            $this->error('Failed to fetch spam ips.');

            return;
        }

        // Check if config is set for compress true
        if (config('laraguard-ip.compress')) {
            $this->info('Compressing spam ips...');

            // Compress ips
            $ips = $this->compressIps($ips);
        }

        // Save the ips to the laraguard-ip.json file. Path from config
        $this->info('Saving spam ips to laraguard-ip.json...');
        try {
            file_put_contents(config('laraguard-ip.path'), json_encode($ips, config('laraguard-ip.compress') ? 0 : JSON_PRETTY_PRINT));
        } catch (\Exception) {
            $this->error('Failed to save spam ips.');

            return;
        }

        $this->info('Caching spam ips...');

        // Remember Ips for forever
        try {
            Cache::forever('laraguardip', $ips);
        } catch (\Exception $e) {
            Cache::forget('laraguardip');

            $this->warn('IP spam list saved to file, but is too long to cache in database');

            return;
        }

        $this->info('Spam ips cached successfully!');
    }

    /**
     * Fetch spam ips from the sources.
     */
    private function fetchSpamIps(array $sources): array
    {
        $ips = [];

        // Fetch ips from the provided sources
        foreach ($sources as $source) {
            $response = Http::get($source);

            if ($response->successful()) {
                $source_ips = $this->parseIps($response->body());
                $ips = array_merge($ips, $source_ips);
            } else {
                $this->error('Failed to fetch ips from '.$source);
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * Compress ips.
     */
    public function compressIps(array $ips): array
    {
        return array_map(fn (string $ip) => ip2long($ip), $ips);
    }

    /**
     * Parse Ips from the response.
     */
    public function parseIps(string $response): array
    {
        $ips = explode("\n", $response);

        // Remove inline comments and validate that every ip contains a valid IP address
        return array_filter(
            array_map(fn ($ip) => preg_replace('/\s*#.*$/', '', trim($ip)), $ips),
            fn ($ip) => filter_var($ip, FILTER_VALIDATE_IP) !== false
        );
    }
}
