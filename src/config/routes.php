<?php

namespace daandelange\SimpleStats;

//return (false===option('daandelange.simplestats.tracking.onLoad', true))?[]:[
//return (SimpleStatsTrackingMode::OnImage===option('daandelange.simplestats.tracking.method', SimpleStatsTrackingMode::OnLoad))?[]:[
//var_dump(option('daandelange.simplestats.tracking.method')); die();
return [
    // Intercept counter pixel on home page
    [
        'pattern' => 'counter.png',
        'language' => '*',
        'action' => function ($language) {
            return SimpleStats::trackPageAndServeImageResponse( site()->homePage() );
        },
    ],
    // On all other pages
    [
        'pattern' => '(:all)/counter.png',
        'language' => '*',
        'action' => function ($language, $id) {
            return SimpleStats::trackPageAndServeImageResponse( page($id) );
        },
    ],
];
