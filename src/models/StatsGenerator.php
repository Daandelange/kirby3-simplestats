<?php

declare(strict_types=1);

namespace daandelange\SimpleStats;

//use Kirby\Http\Header;
@include_once __DIR__ . '/vendor/autoload.php';

// use Kirby\Database\Database;
// use Kirby\Toolkit\Collection;
// use Kirby\Toolkit\F;
// use Kirby\Toolkit\Obj;

// class StatsGeneratorDb extends SimpleStatsDb {}
// class StatsGeneratorDb extends Stats {}
// class StatsGeneratorDb extends StatsGeneratorDb {}

class StatsGenerator extends SimpleStatsDb {

    // Caution, only use on backed-up or empty databases !
    public static function GenerateVisits(int $timefrom, int $timeto, $visitmode='randommulti', \Kirby\Cms\Pages $pagesobject = null) : array {
        // Protect
        if( kirby()->user()->hasSimpleStatsPanelAccess(true) ){
            // Todo: add option to enable the generator (disabled by default)

            // Verify Time is chronological, positive and before today
            if($timefrom <= 0 || $timeto <= 0 || $timefrom >= $timeto || $timefrom >= time() || $timeto >= time()){
                return ['status'=>false, 'error'=>'Time range error! (must be chronological and before today)', date('d-m-Y',$timefrom), date('d-m-Y',$timeto), $timefrom, $timeto];
            }

            // Prepare pages
            if( !$pagesobject || $pagesobject->count()==0 ){
                $pagesobject = kirby()->site()->index()->listed();

                // Append home page ?
                $pagesobject->append( kirby()->site()->homePage() );

                // Append error page ?
                //$pagesobject->append( kirby()->site()->errorPage() );
            }

            // Check pages
            if( $pagesobject->count()===0 ){
                return ['status'=>false, 'error'=>'No pages!'];
            }

            // Show pages
            //foreach( $pagesobject as $p ) echo $p->title()."\n";

            // Todo: Set longer php time-out ?

            // Prepare some variables
            $user = new \Kirby\Cms\User([]); // Dummy user, to ensure the admin doesn't get tracked.
            $pagekeys = $pagesobject->keys();
            $pagesMaxDepth = max($pagesobject->pluck('depth'));
            $languages = kirby()->languages();
            if($languages) $languages = $languages->pluck('code');

            $visitsCounter = 0;
            $errorCounter = 0;
            $periodsCounter = 0;
            $languagesCounter = [];

            // Get Time frames
            for( $timeFrame=getTimeFromPeriod(getPeriodFromTime($timefrom)); $timeFrame <= $timeto; $timeFrame=incrementTime($timeFrame) ){
                // Prepare visit
                $timeStr = date('Y-m-d', $timeFrame);

                // Track / visit pages
                if( $visitmode=='randomsingle' ){
                    $randomPage = $pagesobject->get( $pagekeys[rand(0,count($pagekeys)-1)] );
                    $randomLang = $languages ? $languages[rand(0,count($languages)-1)] : null;
                    $tracked = SimpleStats::track( $randomPage->id(), $timeFrame, $user, $randomLang );

                    if($tracked){
                        $visitsCounter++;
                        if(!isset($languagesCounter[$lang])) $languagesCounter[$lang]=1;
                        else $languagesCounter[$lang]++;
                    }
                    else{
                        $errorCounter++;
                    }

                    //echo $timeStr.'  -  '.$randomPage->title().'  === '.($tracked?'ok':'fail')."\n";
                }
                elseif( $visitmode=='all' || $visitmode=='randommulti' ){
                    foreach( $pagesobject as $p ){
                        if( $visitmode=='randommulti' ){
                            if(rand(0,100)<(20+$p->depth()*(80/$pagesMaxDepth))) continue; // Depth increases chance of skipping
                        }
                        $lang = $languages ? $languages[rand(0,count($languages)-1)] : null;
                        $tracked = SimpleStats::track( $p->id(), $timeFrame, $user, $lang );

                        if( $tracked ){
                            $visitsCounter++;
                            if( !isset($languagesCounter[$lang]) ) $languagesCounter[$lang]=1;
                            else $languagesCounter[$lang]++;
                        }
                        else{
                            $errorCounter++;
                        }

                        //echo $timeStr.'  -  '.$p->title().' ::'.$lang.'  === '.($tracked?'ok':'fail')."\n";
                    }
                }

                // Sync DB every period
                Stats::SyncDayStats( $timeFrame );

                // Remember
                $periodsCounter++;
            }

            return [
                'status'    => true,
                'message'   => 'Generated '.$visitsCounter.' (and '.$errorCounter.' errors/ignored visits) in '.$periodsCounter.' time periods using '.count($languagesCounter).' different languages. ('.json_encode($languagesCounter).')',
                'data'      => [
                    'generatorMode'     => $visitmode,
                    'generatedVisits'   => $visitsCounter,
                    'ignoredPagetracks' => $errorCounter,
                    'timePeriodCount'   => $periodsCounter,
                    'languagesCount'    => count($languagesCounter),
                    'languages'         => $languagesCounter??null,
                    'fromRange'         => date('d-m-Y', $timefrom),
                    'toRange'           => date('d-m-Y', $timeto),
                ],
            ];
        }
        else {
            throw new PermissionException('You are not authorised to administrate statistics !');
        }
    }
}
