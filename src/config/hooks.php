<?php

namespace daandelange\SimpleStats;

//use Kirby\Http\Router;
use ErrorException;
use Throwable;

return [
    ( option('daandelange.simplestats.tracking.onLoad', true) !== true )?[]:
    'route:after' => function ($path, $method, $result, $final) { // Available = ($route, $path, $method, $result, $final)

        // Call general track object

            if( $final === true && empty($result) === false && $method==='GET') { // Only log visits when the page object was found
                //var_dump($result);
                $page = $path;
                if( is_a($result, 'Kirby\Cms\Page') ) {
                    //var_dump( $result->id() );
                    $page = $result->id();

                    try {
                        SimpleStats::track($page);
                    } catch (Throwable $e) {

                        // If logging enable, initialize model and add record
                        if (option('daandelange.simplestats.log.tracking') === true) {
                            Logger::logTracking('Error tracking page: '.$page.'. Error='.$e->getMessage().'(file: '.$e->getFile().'#L'.$e->getLine().')');
                        }
                    }

                    return $result;
                }
                else {
                    // Panel and other requests are not Page objects. (HttpResponse)
                    // Idea: track downloaded files ?
                    //var_dump($result);
                    // Ignore
                    return $result;
                }

            }


            //var_dump($method);
            //var_dump($result); // Page Object
            //var_dump($final);

        return $result;
    },
];
