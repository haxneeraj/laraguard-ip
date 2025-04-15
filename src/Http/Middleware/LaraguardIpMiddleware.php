<?php

namespace Haxneeraj\LaraguardIp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class LaraguardIpMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $config = config('laraguard-ip');

        // Handle IP Whitelist-Only Access
        if (! empty($config['whitelist_only_access']) && ! in_array($ip, $config['whitelist_ips'] ?? [])) {
            return $this->denyAccess($request, 'IP not allowed');
        }

        // if country whitelist only is enabled
        if (! empty($config['country_whitelist_only'])) {
            $countryCode = $this->getCountryCodeFromIp($ip);
            if (! in_array($countryCode, $config['country_whitelist'] ?? [])) {
                return $this->denyAccess($request, 'Country not allowed');
            }
        }

        // if spam protection is enabled
        if (! empty($config['laraguardip_spam_protection']) && $this->is_spam_ip($ip)) {
            return $this->denyAccess($request, 'Blacklisted IP');
        }

        return $next($request);
    }

    protected function is_spam_ip($ip)
    {
        if (is_string($ip) && config('laraguard-ip.compress')) {
            $ip = is_numeric($ip) ? (int) $ip : ip2long($ip);
        }

        return in_array($ip, $this->spamList(), true);
    }

    protected function spamList()
    {
        return Cache::get('laraguardip', function () {
            $path = config('laraguard-ip.path');

            return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        });
    }

    protected function denyAccess(Request $request, string $message = 'Forbidden', int $status = 403)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], $status)
            : abort($status, $message);
    }

    protected function getCountryCodeFromIp(string $ip): string
    {
        // Service to convert IP to Country here
        return 'US';
    }
}
