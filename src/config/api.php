<?php

namespace daandelange\SimpleStats;

return [

    // Routes for the stats api in the panel
    'routes' => [
        [
            'pattern' => 'simplestats/listvisitors',
            'method'  => 'GET',
            'action'  => function () {
                return Stats::listvisitors();
            }
        ],
        [
            'pattern' => 'simplestats/devicestats',
            'method'  => 'GET',
            'action'  => function () {
                return Stats::deviceStats();
            },
        ],
        [
            'pattern' => 'simplestats/refererstats',
            'method'  => 'GET',
            'action'  => function () {
                return Stats::refererStats();
            },
        ],
        [
            'pattern' => 'simplestats/pagestats',
            'method'  => 'GET',
            'action'  => function () {
                return Stats::pageStats();
            },
        ],
    ],

];
