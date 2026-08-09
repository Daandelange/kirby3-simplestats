<?php

namespace daandelange\SimpleStats;

use Kirby\Toolkit\Html;

return [

    // Generate HTML that redirects to counter image
    'simpleStatsImage' => function () {

        // Only serve the image if tracking is enabled and method is OnImage
        if (SimpleStatsTrackingMode::OnImage !== option('daandelange.simplestats.tracking.method', SimpleStatsTrackingMode::OnImage)) {
            return null;
        }

        // Check if any tracking feature is enabled
        $trackingEnabled = option('daandelange.simplestats.tracking.enableDevices', true)
                        || option('daandelange.simplestats.tracking.enableVisits', true)
                        || option('daandelange.simplestats.tracking.enableReferers', true)
                        || option('daandelange.simplestats.tracking.enableVisitLanguages', true);

        if (!$trackingEnabled) {
            return null;
        }

        // Determine base URL
        $langCode = kirby()->languages()->count() > 1
            ? kirby()->languages()->first()->code()
            : null;

        $url = $this->url($langCode) . '/counter.png';

        return Html::img($url, [
            'loading' => 'lazy',
            'alt'     => 'simplestats counter pixel',
            'height'  => '1',
            'width'   => '1',
            'class'   => 'simplestats-image', // For custom CSS styling
            'style'   => option('daandelange.simplestats.tracking.imageStyle'),
        ]);
    },

    // Add page method to retrieve stats, for use in templates
    'getSimplestatsCount' => function () {
        return Stats::onePageStats($this->id())['visits'] ?? 0;
    },

    // Return fully computed stats of the page
    'getPageStats' => function () {
        return Stats::onePageStats($this->id());
    }
];
