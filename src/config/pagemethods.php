<?php

namespace daandelange\SimpleStats;

return [

    // Generate HTML that redirects to counter image
    'simpleStatsImage' => function() {
        // Don't serve the image when disabled
        if (false===option('daandelange.simplestats.tracking.onLoad', true)) return '';
        if (
            false===option('daandelange.simplestats.tracking.enableDevices' , true) &&
            false===option('daandelange.simplestats.tracking.enableVisits'  , true) &&
            false===option('daandelange.simplestats.tracking.enableReferers', true)
        ) return '';

        throw new Exception("Todo: count page via image !");

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
                'style'     => 'position: absolute; right: 0; pointer-events: none; height: 1px; width: 1px; opacity: 0;',
                // todo: 'style' => option('daandelange.simplestats.tracking.image.style'),
            ]
        );
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
