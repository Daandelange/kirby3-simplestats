<?php

namespace daandelange\SimpleStats;

return (false===option('daandelange.simplestats.tracking.onLoad', true))?[]:[
    // Intercept counter pixel on home page
    [
        'pattern' => 'counter.png',
        'language' => '*',
        'action' => function ($language) {
            try {
                //SimpleStats::track( site()->homePage()-> );
            } catch (\Throwable $e) {
                // If logging enable, initialize model and add record
                if (option('daandelange.simplestats.log.tracking') === true) {
                    Logger::logTracking('Error tracking page: '.$page.'. Error='.$e->getMessage());
                }
            }

        },
    ],
    // On all other pages
    [
        'pattern' => '(:all)/counter.png',
        'language' => '*',
        'action' => function ($language, $uid) {
            throw new Exception("Todo: count page via image !");
            // SimpleStats::track($uid);
        },
    ],
];
