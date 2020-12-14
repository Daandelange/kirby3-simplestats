<?php

namespace daandelange\SimpleStats;

//use Kirby\Http\Router;

return [
    'route:after' => function (/* $route, */ $path, $method, $result, $final) {
        // Call general track object
        if ( option('daandelange.simplestats.tracking.onLoad') === true ){
            if( $final === true && empty($result) === false && $method==='GET') { // Only log visits when the page object was found
                //var_dump($result);
                $page = $path;
                if( is_a($result, 'Page') ) {
                    //var_dump( $result->uri() );
                    $page = $result->uri();
                }
                else {
                    // Panel and other requests are not Page objects. (HttpResponse)
                    // Idea: track downloaded files ?
                    //var_dump($result);
                    // Ignore
                    return $result;
                }



                try {
                    SimpleStats::track($page);
                } catch (\Throwable $e) {
                    //var_dump($e);
                    // If logging enable, initialize model and add record
                    if (option('daandelange.simplestats.log.tracking') === true) {
                        Logger::logTracking('Error tracking page: '.$page.'. Error='.$e->getMessage());
                    }
                }
                return $result;
            }


            //var_dump($method);
            //var_dump($result); // Page Object
            //var_dump($final);
        }
        return $result;
    }
];
