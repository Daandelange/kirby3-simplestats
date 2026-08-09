<?php

namespace daandelange\SimpleStats;

use Kirby\Cms\Page;

return [
    'route:after' => function ($path, $method, $result, $final) {

        // Only track on GET requests with final page results
        if ($method !== 'GET' || $final !== true || empty($result)) {
            return $result;
        }

        // Only track if the tracking method is "OnLoad"
        if (SimpleStatsTrackingMode::OnLoad !== option('daandelange.simplestats.tracking.method', SimpleStatsTrackingMode::OnLoad)) {
            return $result;
        }

        // Only track if any tracking feature is enabled
        $trackingEnabled = option('daandelange.simplestats.tracking.enableDevices', true)
                        || option('daandelange.simplestats.tracking.enableVisits', true)
                        || option('daandelange.simplestats.tracking.enableReferers', true)
                        || option('daandelange.simplestats.tracking.enableVisitLanguages', true);

        if (!$trackingEnabled) {
            return $result;
        }

        // Track if the result is a published Page
        if ($result instanceof Page && $result->exists() && $result->isPublished()) {
            SimpleStats::safeTrack($result->id());
        }
        // Optionally: could track other resources here (e.g., downloaded files)
        // else { ignore or handle non-page requests }

        return $result;
    },
];
