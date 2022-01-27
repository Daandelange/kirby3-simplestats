<?php

namespace daandelange\SimpleStats;

return [

    // Generate HTML that redirects to counter image
    'simpleStatsImage' => function() {
        // Don't serve the image when disabled
        if(
            // Correct tracking method ?
            SimpleStatsTrackingMode::OnImage === option('daandelange.simplestats.tracking.method', SimpleStatsTrackingMode::OnLoad)
            &&
            // Any tracking feature is enabled ?
            (
                true===option('daandelange.simplestats.tracking.enableDevices' , true) ||
                true===option('daandelange.simplestats.tracking.enableVisits'  , true) ||
                true===option('daandelange.simplestats.tracking.enableReferers', true) ||
                true===option('daandelange.simplestats.tracking.enableVisitLanguages', true)
            )
        ){

            // Like bnomei/pageviewcounter
            return \Kirby\Toolkit\Html::img(
                $this->url(
                    kirby()->languages()->count() > 1 ?
                        kirby()->languages()->first()->code() :
                        null
                ) . '/counter.png',
                [
                    'loading'   => 'lazy',
                    'alt'       => 'simplestats counter pixel',
                    'height'    => '1',
                    'width'     => '1',
                    'class'     => 'simplestats-image', // For custom css styling
                    'style'     => option('daandelange.simplestats.tracking.imageStyle'),
                ]
            );
        }
        return null;
    },

    // Add page method to retrieve stats, for use in templates for example
    'getSimplestatsCount' => function(){
        return 777;
    },

    // Return fully computed stats of the page
    'getPageStats' => function(){
        return Stats::onePageStats($this->id());
    }
];
