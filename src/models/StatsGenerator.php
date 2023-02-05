<?php

declare(strict_types=1);

namespace daandelange\SimpleStats;

//use Kirby\Http\Header;
@include_once __DIR__ . '/vendor/autoload.php';

// use Kirby\Database\Database;
// use Kirby\Toolkit\Collection;
// use Kirby\Toolkit\F;
// use Kirby\Toolkit\Obj;
use \Kirby\Cms\User;

// class StatsGeneratorDb extends SimpleStatsDb {}
// class StatsGeneratorDb extends Stats {}
// class StatsGeneratorDb extends StatsGeneratorDb {}

class StatsGenerator extends SimpleStatsDb {

    public static  $useragentSamples = [
        'Mozilla/5.0 (Linux; Android 10; SM-G980F Build/QP1A.190711.020; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/78.0.3904.96 Mobile Safari/537.36',
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)',
        'Mozilla/5.0 (X11; U; Linux armv7l like Android; en-us) AppleWebKit/531.2+ (KHTML, like Gecko) Version/5.0 Safari/533.2+ Kindle/3.0+',
        'Mozilla/5.0 (Nintendo Switch; WifiWebAuthApplet) AppleWebKit/601.6 (KHTML, like Gecko) NF/4.0.0.5.10 NintendoBrowser/5.1.0.13343',
        'Mozilla/5.0 (PlayStation; PlayStation 5/2.26) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0 Safari/605.1.15',
        'AppleTV11,1/11.1',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36',
        'Mozilla/5.0 (X11; CrOS x86_64 8172.45.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.64 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9',
        'Mozilla/5.0 (Linux; Android 11; Lenovo YT-J706X) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36',
        'Mozilla/5.0 (Windows Phone 10.0; Android 6.0.1; Microsoft; RM-1152) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Mobile Safari/537.36 Edge/15.15254',
        'Mozilla/5.0 (iPhone9,4; U; CPU iPhone OS 10_0_1 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/14A403 Safari/602.1',
        'Mozilla/5.0 (Linux; Android 6.0; HTC One X10 Build/MRA58K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/61.0.3163.98 Mobile Safari/537.36',
        'Mozilla/5.0 (Linux; Android 7.1.1; G8231 Build/41.2.A.0.219; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/59.0.3071.125 Mobile Safari/537.36',
        'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1',
    ];
    public static $refererSamples = [
        'https://getkirby.com/',
        'http://duckduckgo.com/',
        'http://google.com/',
        'http://bing.com/',
        'https://forum.getkirby.com/',
        'https://bestmagazines.com/',
        'https://github.com/',
        'https://www.pouet.net/',
    ];

    public static function getRandomUserAgent() : string {
        return static::$useragentSamples[ rand(0,count(static::$useragentSamples)-1) ];
    }
    public static function getRandomReferer() : string {
        return static::$refererSamples[ rand(0,count(static::$refererSamples)-1) ];
    }
    public static function getRandomHeaders() : array {
        $ret = [
            'Referer'      => static::getRandomReferer(),
            'User-Agent'   => static::getRandomUserAgent(),
        ];

        return $ret;
    }

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
            $browserHeaders = static::getRandomHeaders();

            // Todo: Set longer php time-out ?

            // Prepare some variables
            $user = new User([]); // Dummy user, to ensure the admin doesn't get tracked.
            $pagekeys = $pagesobject->keys();
            $pagesMaxDepth = max($pagesobject->pluck('depth'));
            $languages = kirby()->languages();
            if($languages) $languages = $languages->pluck('code');

            $visitsCounter = 0;
            $errorCounter = 0;
            $periodsCounter = 0;
            $languagesCounter = [];
            $executionUniqueNum = hrtime(true);

            // Get Time frames
            for( $timeFrame=getTimeFromPeriod(getPeriodFromTime($timefrom)); $timeFrame <= $timeto; $timeFrame=incrementTime($timeFrame) ){
                // Prepare visit
                $timeStr = date('Y-m-d', $timeFrame);

                // Track / visit pages
                if( $visitmode=='randomsingle' ){
                    $randomPage = $pagesobject->get( $pagekeys[rand(0,count($pagekeys)-1)] );
                    $randomLang = $languages ? $languages[rand(0,count($languages)-1)] : null;
                    $tracked = SimpleStats::track( $randomPage->id(), $timeFrame, $user, $randomLang, $browserHeaders );

                    if($tracked){
                        $visitsCounter++;
                        if(!isset($languagesCounter[$randomLang])) $languagesCounter[$randomLang]=1;
                        else $languagesCounter[$randomLang]++;
                    }
                    else{
                        $errorCounter++;
                    }
                }
                elseif( $visitmode=='all' || $visitmode=='randommulti' ){
                    foreach( $pagesobject as $p ){
                        if( $visitmode=='randommulti' ){
                            if( rand(0,100) < (
                                20 // 20% base chance
                                +(hrtime(true)%5) // 5% loop-specific randomness
                                +(abs(($periodsCounter+$executionUniqueNum)%(50*2)-50))*(25/50) // 25% period-specific variance every 50 periods
                                +(($p->depth())/$pagesMaxDepth)*40 // 0-40% chance of skipping deeper pages
                                // leaves 10% chance for any page to be visited
                            )){
                                continue;
                            }
                        }
                        
                        // Randomize context
                        if((rand(0,99)%7)>2) $browserHeaders = static::getRandomHeaders();
                        $lang = $languages ? $languages[min(rand(0,count($languages)), count($languages)-1)] : null; // (1st lang has more chance)

                        // Try to track
                        $tracked = SimpleStats::track( $p->id(), $timeFrame, $user, $lang, $browserHeaders );

                        if( $tracked ){
                            $visitsCounter++;
                            if( !isset($languagesCounter[$lang]) ) $languagesCounter[$lang]=1;
                            else $languagesCounter[$lang]++;
                        }
                        else{
                            $errorCounter++;
                        }

                    }
                }

                // Sync DB every period
                Stats::SyncDayStats( $timeFrame + 24*60*60);//incrementTime( $timeFrame ) ); // Directly parse all stats
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
