<?php

namespace daandelange\SimpleStats;

// README :
// - RGPD stuff : you remain responsible. Depending on how you configure SimpleStats, behaviour changes and so do you obligations. Know what you are doing.
// - Logs : Be aware that some user data can get logged. Better turn logging off in production, or be sure to read and erase them.
// - Maybe : Add .htaccess rules for .sqlite and .log/.txt files, prevent direct access.
// - With simplestats as analytics engine, you are hosting sensitive user data. Read getkirby.com/guides/secure for optimal privacy recomendations.

define('SIMPLESTATS_DUMMY_DB_LIMIT', 1000000); // Use great number if you have a big DB. Tip while db upgrading : use an incredibly huge number.

return [
    'log' => [
        'tracking'  => true, // Enable tracking errors.
        'warnings'  => true, // Functional warnings. Mostly db related.
        'verbose'   => true, // For testing / dev, logs almost anything else.
        'file'      => SimpleStatsDb::getLogsPath('simplestats_errors.txt'),
    ],

    // Tracking options
    'tracking' => [
        'database'              => SimpleStatsDb::getLogsPath('simplestats.sqlite'),
        'timeFrameUtility'      => new SimpleStatsTimeFrameUtilityMonthly(), // 'weekly' or 'monthly' or any instance of SimpleStatsTimeFrameUtility. Do not change without creating a new db file !!
        'enableReferers'        => true, // Enables tracking of referers. Gives an insigt of who links your website.
        'enableDevices'         => true, // Enables tracking of minimal hardware configurations (device information)
        'enableVisits'          => true, // Enables tracking of page visits
        'enableVisitLanguages'  => true, // In multilanguage setups, separately count language hits with page counts
        'salt'                  => 'CHANGEME', // Salt used to obfuscate unique user string.
        'uniqueSeconds'         => 1*24*60*60, // Anonimised user data is deleted after this delay to become void
        'imageStyle'            => 'position: absolute; right: 0; pointer-events: none; height: 1px; width: 1px; opacity: 0;', // The style applied to the tracking image. Only for tracking.method=OnImage on $page->simpleStatsImage();
        'anonimizeIpBits'       => 1, // how many bits to strip from the end of the IP ? (for IP anonimization)

        // Tracking blacklist
        'ignore' => [
            'roles'         => ['admin'],//kirby()->roles()->toArray( function($v){return $v->id();} ), // By default, don't track connected users. --- Cannot call kirby() here
            'pages'         => [], // Array of plain text page ids.
            'templates'     => ['error'], // Array of plain template names not to track (use lowercase) (checked against intendedTemplate and template)
            'localhost'     => false, // Beta.#18 To ignore tracking (and any syncing?) from localhost. The database remains viewable trough the panel.
            'bots'          => false, // Beta.#21 Globally disable tracking bots (when they are detected as so).
            'botVisits'     => true, // Beta.#21 Enable if you'd like bots to increment page visit counters.
            'botReferers'   => true, // Beta.#21 Enable if you'd like to record referrers send by bots.
            //'botDevices'    => false, // Beta.#21 Enable if you'd like bot device information to be saved. Note: no option as bot device info is needed by the 2 options above. Could become an option to hide bots from panel stats.
        ],

        // Dont change onLoad yet !!! (keep to true)
        //'onLoad'   => true, // Tracks when the page is served by the router (increases load time). If false, you need to add an image to all trackable templates (not yet available), you'll get better load performance, and "naturaly" exclude most bots.
        // Set to false to track from an image, which can naturally prevent calls from most robots, and speed up page loads. (recommended: set to false)
        // Track hits on page serve or using an image ?
        'method' => SimpleStatsTrackingMode::OnLoad, // Notyet. Important: SimpleStatsTrackingMode
    ],

    // Enable/Disable the admin panel and API
    'panel' => [
        'enable'            => true, // Only disables the API (for now...) = makes the panel unusable.
        'dismissDisclaimer' => false,
        'authorizedRoles'   => ['admin'], // Role (ids) that are allowed to view page statistics.
        'breadcrumbLabel'   => 'SimpleStats',
        'hideBots'          => false,           // To hide bots in the devices tab.
    ]


    // IDEAS 4 LATER
    // Respect DNT
    // pagecountermode : hits, uniquehitsUA, uniquehitsSession (not yet)
    // 'trackUniqueHitsOnly'   => true, // set to false for hitcounter behaviour
    // Todo: option to exclude bots from hitcounter (still tracking the device)
    // Todo : Sync daily stats by crontask or afterLoad ? (increases page load speed)
    // Todo: Add refferer tracking via pingback ? Webmentions ?

    // (Settings ideas from k2-stats)
    // stats.days - Keep daily stats for how many days ?
    // stats.date.format

];
