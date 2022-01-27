<?php

namespace daandelange\SimpleStats;

//use Kirby\Http\Router;
use ErrorException;
use Throwable;
use Kirby\Cms\Page;

return [
    'route:after' => function ($path, $method, $result, $final) { // Available = ($route, $path, $method, $result, $final)

        if(
            // Correct tracking method ?
            SimpleStatsTrackingMode::OnLoad === option('daandelange.simplestats.tracking.method', SimpleStatsTrackingMode::OnLoad)
            &&
            // Any tracking feature is enabled ?
            (
                true===option('daandelange.simplestats.tracking.enableDevices' , true) ||
                true===option('daandelange.simplestats.tracking.enableVisits'  , true) ||
                true===option('daandelange.simplestats.tracking.enableReferers', true) ||
                true===option('daandelange.simplestats.tracking.enableVisitLanguages', true)
            )
        ){

            // Call general track object
            if( $final === true && empty($result) === false && $method==='GET') { // Only log visits when the page object was found

                if( $result instanceof Page && $result->exists() && $result->isPublished() ) {
                    SimpleStats::safeTrack($result->id());
                    return $result;
                }
                else {
                    // Panel and other requests are not Page objects. (HttpResponse)
                    // Idea: track downloaded files ?
                    //var_dump(get_class($result), is_a($result, 'Page'), $result);
                    // Ignore
                }
            }
        }

        return $result;
    },
];
