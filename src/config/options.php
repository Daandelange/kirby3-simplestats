<?php

namespace daandelange\SimpleStats;

/*
|--------------------------------------------------------------------------
| SimpleStats Configuration
|--------------------------------------------------------------------------
| This file configures the SimpleStats analytics engine for Kirby CMS.
|
| README:
| - RGPD compliance: You remain responsible for user privacy. Changing options can affect your obligations.
| - Logs: Some user data may be logged. Consider disabling logging in production or regularly clearing logs.
| - Security: Consider .htaccess rules for .sqlite and .log/.txt files to prevent direct access.
| - Hosting sensitive data: Follow getkirby.com/guides/secure for privacy best practices.
*/

define('SIMPLESTATS_DUMMY_DB_LIMIT', 1000000);
// Use a large number for big databases. Tip: while upgrading DB, use a huge number for safety.

return [

    /*
    |--------------------------------------------------------------------------
    | Logging Options
    |--------------------------------------------------------------------------
    | Controls how errors, warnings, and verbose info are logged.
    */
    'log' => [
        'tracking' => true,  // Track errors
        'warnings' => true,  // Functional warnings (mostly DB-related)
        'verbose'  => true,  // Dev/testing: logs almost anything else
        'file'     => SimpleStatsDb::getLogsPath('simplestats_errors.txt'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking Options
    |--------------------------------------------------------------------------
    | Controls what data is collected, how it is anonymized, and how hits are counted.
    | WARNING: Changing some options may require a new database file.
    */
    'tracking' => [

        // -----------------------------
        // Database & Time Frame
        // -----------------------------
        'database'         => SimpleStatsDb::getLogsPath('simplestats.sqlite'),
        'timeFrameUtility' => new SimpleStatsTimeFrameUtilityMonthly(),
        // Options: 'daily', weekly', 'monthly', or any SimpleStatsTimeFrameUtility instance
        // WARNING: Changing this requires creating a new database

        // -----------------------------
        // What to Track
        // -----------------------------
        'enableReferers'       => true,       // Track referring sites (who links your pages)
        'enableDevices'        => true,       // Track minimal hardware/device info
        'enableVisits'         => true,       // Track page visits
        'enableVisitLanguages' => true,       // Track hits per language in multi-language setups

        // -----------------------------
        // Anonymization & Privacy
        // -----------------------------
        'salt'          => 'CHANGEME',        // Obfuscates unique user strings
        'uniqueSeconds' => 1 * 24 * 60 * 60,  // Time before anonymized user data becomes void
        'anonimizeIpBits'=> 1,                // Strip last n bits from IP address for privacy

        // -----------------------------
        // Tracking Image Styling (for OnImage method)
        // -----------------------------
        'imageStyle' => 'position: absolute; right: 0; pointer-events: none; height: 1px; width: 1px; opacity: 0;',

        // -----------------------------
        // Blacklist / Ignore Rules
        // -----------------------------
        'ignore' => [
            'roles'       => ['admin'],       // Do not track users with these roles
            'pages'       => [],              // Page IDs to ignore
            'templates'   => ['error'],       // Template names to ignore (lowercase)
            'localhost'   => false,           // Ignore localhost hits
            'bots'        => false,           // Globally ignore bots
            'botVisits'   => true,            // Count bot page visits
            'botReferers' => true,            // Record referrers from bots
            // 'botDevices' => false,         // Optionally hide bot device info
        ],

        // -----------------------------
        // Tracking Method
        // -----------------------------
        'method' => SimpleStatsTrackingMode::OnLoad,
        /*
        Options:
            - OnLoad: Track when page is served by router
                + Pros: All visits tracked immediately
                + Cons: Slower, includes bots
            - OnImage: Track via invisible image
                + Pros: Faster, excludes most bots
                + Cons: Must include image in templates
        */
        // 'onLoad' => true, // Optional: keep true for now. Alternative: false for OnImage tracking
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel / API Options
    |--------------------------------------------------------------------------
    | Controls access to the SimpleStats admin panel and API,
    | what users can see, and how initial views behave.
    */
    'panel' => [

        // -----------------------------
        // Panel Activation
        // -----------------------------
        'enable' => true,
        // true: Panel and API are accessible
        // false: Panel unusable, API disabled

        'dismissDisclaimer' => false,
        // true: Hide the disclaimer message in the panel
        // false: Show disclaimer to remind users about privacy/logging

        // -----------------------------
        // Access Control
        // -----------------------------
        'authorizedRoles' => ['admin'],
        // Only users with these roles (by ID) can view panel statistics

        // -----------------------------
        // Panel Display Options
        // -----------------------------
        'breadcrumbLabel' => 'SimpleStats',
        // Label that appears in the panel breadcrumb navigation

        'hideBots' => false,
        // true: Exclude bot traffic from the Devices tab
        // false: Show bots like regular devices

        // -----------------------------
        // Initial Time Span
        // -----------------------------
        'defaultTimeSpan' => -1,
        // Number of periods (weeks/months) shown initially
        // -1: Full database span
        // Positive integer: Shows that many periods from the latest
    ],

    /*
    |--------------------------------------------------------------------------
    | TODO / Ideas for Later
    |--------------------------------------------------------------------------
    | Future improvements and optional features:
    | - Respect DNT headers
    | - pageCounterMode: hits, uniqueHitsUA, uniqueHitsSession
    | - Option to track only unique hits
    | - Exclude bots from hit counter (still track device info)
    | - Sync daily stats via cron or afterLoad
    | - Track referrers via pingback / webmentions
    | - K2-stats style options: stats.days, stats.date.format
    */
];
