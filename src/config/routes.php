<?php

namespace daandelange\SimpleStats;

// Only register routes when image tracking is enabled
if (option('daandelange.simplestats.tracking.method') !== SimpleStatsTrackingMode::OnImage) {
    return [];
}

$isMultiLang = kirby()->multilang();

$homeRoute = [
    'pattern' => 'counter.png',
    'action' => function () {
        $page = site()->homePage();

        if (!$page) return false;

        return SimpleStats::trackPageAndServeImageResponse($page);
    }
];

$subPageRoute = [
    'pattern' => '(:all)/counter.png',
    'action' => function ($id) {
        $page = page($id);

        if (!$page) return false;

        return SimpleStats::trackPageAndServeImageResponse($page);
    }
];

if ($isMultiLang) {
    $homeRoute['language'] = '*';
    $subPageRoute['language'] = '*';
}

return [
    $homeRoute,
    $subPageRoute,
];
