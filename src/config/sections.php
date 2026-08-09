<?php

return [
    'pagestats' => [
        'props' => [
            'showTotals' => function (bool $showTotals = true) {
                return $showTotals;
            },
            'showTimeline' => function (bool $showTimeline = false) {
                return $showTimeline;
            },
            'showLanguages' => function (bool $showLanguages = false) {
                return $showLanguages;
            },
            'size'  => function(string $size = 'medium') {
                return $size;
            },
        ],
        'computed' => [
            'statsdata' => function () {
                return $this->model()->getPageStats();
            }
        ]
    ],
];
