<?php

/**
 * Laraguard-IP Configuration File
 * --------------------------------
 *
 * @version    1.0.0
 *
 * @author     Neeraj Saini <hax-neeraj@outlook.com>
 * @license    MIT License
 *
 * This configuration file controls how Laraguard-IP handles
 * IP and country-based access control, spam protection,
 * and IP blocklists.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Spam Protection
    |--------------------------------------------------------------------------
    |
    | If enabled, Laraguard-IP will fetch IPs from external sources (like
    | AbuseIPDB) and automatically block any incoming requests from blacklisted IPs.
    | Recommended for publicly exposed applications and APIs.
    |
    */

    'laraguardip_spam_protection' => env('LARAGUARDIP_SPAM_PROTECTION', false),

    /*
    |--------------------------------------------------------------------------
    | Whitelist Only Access
    |--------------------------------------------------------------------------
    |
    | If set to true, only IPs listed in 'whitelist_ips' will be allowed.
    | All other IPs will be denied access regardless of any other settings.
    | This provides strict access control based on IP.
    |
    */

    'whitelist_only_access' => env('LARAGUARDIP_WHITELIST_ONLY', false),

    /*
    |--------------------------------------------------------------------------
    | Country Whitelist Only
    |--------------------------------------------------------------------------
    |
    | If enabled, only visitors from the countries listed in 'country_whitelist'
    | will be allowed to access the application. All other countries will be blocked.
    | Requires GeoIP or IP-to-country detection service.
    |
    */

    'country_whitelist_only' => env('LARAGUARDIP_COUNTRY_WHITELIST_ONLY', false),

    /*
    |--------------------------------------------------------------------------
    | Whitelisted IPs
    |--------------------------------------------------------------------------
    |
    | Define all trusted IPs that should always be allowed access regardless
    | of blacklist or spam protection settings. Useful for developers, admins, etc.
    |
    */

    'whitelist_ips' => [
        '127.0.0.1', // Localhost IP
    ],

    /*
    |--------------------------------------------------------------------------
    | Whitelisted Countries
    |--------------------------------------------------------------------------
    |
    | ISO Alpha-2 country codes (e.g., IN, US, UK). Requests from these countries
    | will be allowed only if 'country_whitelist_only' is set to true.
    |
    */

    'country_whitelist' => [
        'IN', // India
        'US', // United States
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Blocklist Sources
    |--------------------------------------------------------------------------
    |
    | External sources (like AbuseIPDB mirrors) providing updated blocklists.
    | These IPs are considered malicious/spam and will be automatically blocked
    | when 'laraguardip_spam_protection' is enabled.
    |
    */

    'sources' => [
        // AbuseIPDB (14 Days)
        'https://raw.githubusercontent.com/borestad/blocklist-abuseipdb/main/abuseipdb-s100-14d.ipv4',

        // Optional 30 Days source
        // 'https://raw.githubusercontent.com/borestad/blocklist-abuseipdb/main/abuseipdb-s100-30d.ipv4',
    ],

    /*
    |---------------------------------------------------------------------------
    | Storage Path
    |---------------------------------------------------------------------------
    |
    | This option defines the path where the Laraguard-IP blocklist data will
    | be stored. You can specify a custom path here, or leave it as the default
    | to store the data in the Laravel framework's cache directory.
    |
    | The default value is set to:
    | storage_path('framework/cache/laraguard-ip.json')
    |
    */

    'path' => storage_path(
        env('LARAGUARDIP_STORAGE_PATH', 'framework/cache/laraguard-ip.json')
    ),

    /*
    |---------------------------------------------------------------------------
    | Data Compression
    |---------------------------------------------------------------------------
    |
    | This option allows you to enable or disable compression when storing the
    | blocklist data. By default, the blocklist data is compressed to save space
    | and improve performance when loading from storage.
    |
    | Set this to 'false' if you prefer not to compress the data. Default is true.
    |
    */

    'compress' => env('LARAGUARDIP_STORAGE_COMPRESS', true),

];
