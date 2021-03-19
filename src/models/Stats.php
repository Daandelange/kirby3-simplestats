<?php
declare(strict_types=1); // Dont mix types

namespace daandelange\SimpleStats;

use Kirby\Database\Database;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Obj;

use ErrorException;
use Throwable;

// This class retrieves analytics from the database
/*
function collectKeys($page) {

}
*/
function getTimeFromMonthYear($monthyear) : int {
    $year=intval(substr(''.$monthyear, 0,4));
    $month=intval(substr(''.$monthyear, 4,2));
    return mktime(0,0,0,$month,1,$year);
}

function getDateFromMonthYear($monthyear, $dateformat='M Y') : string {
    return date( $dateformat, getTimeFromMonthYear($monthyear) ); // todo : strftime for multilang
}

class Stats extends SimpleStatsDb {

    // Like bnomei/pageviewcounter
    public static function pixel(){
        $IMG = \imagecreate(1, 1);
        $background = \imagecolorallocate($IMG, 0, 0, 0);
        \header("Content-type: image/png");
        \imagepng($IMG);
        \imagecolordeallocate($IMG, $background);
        \imagedestroy($IMG);
        exit;
    }

    // Lists database version, history and upgrade status
    public static function listDbInfo(): array {
        $dbVersion = '';
        $dbArray = [];
        $dbvQ = self::database()->query("SELECT `version`, `migrationdate` FROM `simplestats` ORDER BY `migrationdate` DESC LIMIT 0,100");
        if( $dbvQ ){
            if( $dbvQ->isNotEmpty() ){
                $dbVersion = intval($dbvQ->first()->version, 10);
                foreach($dbvQ as $v){
                    $dbArray[]=[
                        'version' => intval($v->version, 10),
                        'date'    => $v->migrationdate,
                    ];
                }
            }
            // No version but table exists = weird !
            else {
                $dbVersion = 'None!';
            }
        }
        else {
            $error = self::database()->lastError()->getMessage();
            // v1 didn't have the simplestats table (only way to detect)
            if( stripos($error, 'no such table:') !== false && stripos($error, 'simplestats') !== false ){
                $dbVersion = '1 (versionless)';
            }
            else {
                $dbVersion = 'Unable to query! '.$error;
            }
        }

        return [
            'softwareDbVersion' => self::engineDbVersion,
            'dbVersion'         => $dbVersion,
            'dbHistoryLabels'   => [
                ['label'=>'Db version', 'field'=>'version', 'type'=>'number',   'sort'=>true,  'search'=>true, 'width'=>'1fr'],
                ['label'=>'Used since', 'field'=>'date',    'type'=>'text',     'sort'=>true,  'search'=>true, 'width'=>'3fr'],
            ],
            'dbHistory'         => $dbArray,
            'upgradeRequired'   => self::engineDbVersion != $dbVersion,
        ];
    }

    public static function listvisitors(): array {
        //$log  = new Log;
        //var_dump( self::singleton()->database() );
        //$db = self::database();
        $result = self::database()->query("SELECT `visitedpages`, `osfamily`, `devicetype`, `browserengine`, `timeregistered` FROM `pagevisitors` LIMIT 0,1000");
        if($result){
            //var_dump(array_keys($result->get(0)->toArray()));
            //var_dump(($result));

            // Get keys
            $keys = [];
            foreach($result as $visitor){
                $keys = array_keys($visitor->toArray());
                //var_dump(array_keys($visitor->toArray()));
                break; // 1 iteration should be enough here
            }

            // Format keys
            foreach($keys as $key => $value){
                $keys[$key] = ['label'=>$value,'field'=>$value,'type'=>'text','sort'=>false,'search'=>false,'class'=>'myClass','width'=>'1fr'];
            }

            $rows = $result->toArray();
            // Format rows
            foreach($rows as $key => $value){
                //var_dump($value->toArray());
                //$rows[$key] = ['props'=>['value'=>$value]];
                $rows[$key] = array_merge(['id'=>$key, 'text'=>'text', 'title'=>'title', 'dragText'=>'dragText', 'info'=>'info!!'], $value->toArray());
//                 $rows[$key] = ['id'=>$key, 'text'=>'text', 'dragText'=>'dragText', 'info'=>'info!!'];//, 'props'=>$value->toArray()];
//
//                 foreach($value as $k => $v){
//                     $rows[$key][$k] = ['label'=>$v];
//                 }
                // convert date format
                $rows[$key]['timeregistered'] = date('Y-m-d h:i', intval($rows[$key]['timeregistered']) );

            }

            return [
                'data'  => [
                    'columns'   => $keys,
                    'rows'      => $rows,
                ],
            ];
        }
        return [];
    }

    public static function deviceStats() {
        //var_dump( kirby()->roles() );//->toArray();
        //return[];
        // tmp
        //self::syncDayStats();

        //$db = self::database();

        // Get devices
        $allDevices = [];
        $allDevicesResult = self::database()->query("SELECT `device`, `hits` FROM `devices` GROUP BY `device` ORDER BY `device` DESC LIMIT 0,1000");
        if($allDevicesResult){
            // parse sql result, line by line
            foreach($allDevicesResult as $device){
                //var_dump($device->toArray());
                $allDevices[] = [$device->device,$device->hits];
            }
        }

        // Get Systems
        $allSystems = [];
        $allSystemsResult = self::database()->query("SELECT `system`, `hits` FROM `systems` GROUP BY `system` ORDER BY `system` DESC LIMIT 0,1000");
        if($allSystemsResult){
            // parse sql result, line by line
            foreach($allSystemsResult as $system){
                //var_dump($device->toArray());
                $allSystems[] = [$system->system,$system->hits];
            }
        }

        // Get Engines
        $allEngines = [];
        $allEnginesResult = self::database()->query("SELECT `engine`, `hits` FROM `engines` GROUP BY `engine` ORDER BY `engine` DESC LIMIT 0,1000");
        if($allEnginesResult){
            // parse sql result, line by line
            foreach($allEnginesResult as $engine){
                //var_dump($device->toArray());
                $allEngines[] = [$engine->engine,$engine->hits];
            }
            //var_dump($allEngines);
        }

        // Get Devices over time
        $devicesOverTimeData=[];
        $devicesOverTime = self::database()->query("SELECT `device`, SUM(`hits`) AS `hits`, `monthyear` FROM `devices` GROUP BY `device`, `monthyear` ORDER BY `monthyear` ASC, `device` ASC LIMIT 0,1000");
        if($devicesOverTime){
            $deviceMonths=[];
            foreach($devicesOverTime as $device){
                $monthyear = intval($device->monthyear);
                $name = $device->device;
                //echo 'NAME=='.$name."\n";

                // Need to create the first entry ?
                if(!array_key_exists($name, $devicesOverTimeData)){
                    $devicesOverTimeData[$name]=[
                        'name' => $name,
                        'data' => [],
                    ];
                }

                // Remember period
                if(array_search($monthyear, $deviceMonths)===false){
                    $deviceMonths[]=$monthyear;
                }
                // value
                $devicesOverTimeData[$name]['data'][$monthyear]=intval($device->hits);
            }

            // Process data
            $tmp=[];
            foreach($devicesOverTimeData as $name => $data){

                // Add missing keys / zero values
                foreach($deviceMonths as $month){
                    if(!array_key_exists($month, $devicesOverTimeData[$name]['data'])){
                        $devicesOverTimeData[$name]['data'][$month]=0;
                    }
                }

                // Convert monthyear to date string
                $devicesOverTimeData[$name]['data2']=[];
                foreach($devicesOverTimeData[$name]['data'] as $my => $hits){
                    $devicesOverTimeData[$name]['data2'][getDateFromMonthYear(intval($my),'Y-m-d')]=$hits;
                }
                $devicesOverTimeData[$name]['data']=$devicesOverTimeData[$name]['data2'];
                unset($devicesOverTimeData[$name]['data2']);


                // Object to array (remove key)
                $tmp[]=$devicesOverTimeData[$name];

                // Should be ok now
            }
            $devicesOverTimeData=$tmp;
            unset($tmp);
            //var_dump($devicesOverTimeData);
        }

        return [
            //'deviceslabels'  => $deviceLabels, // $devicetypes,
            'devicesdata'       => $allDevices,
            'systemsdata'       => $allSystems,
            //'systemslabels'     => $systemsLabels,
            'enginesdata'       => $allEngines,
            'devicesovertime'   => $devicesOverTimeData,
            //'engineslabels' => $enginesLabels,
        ];
    }

    public static function refererStats(): ?array {

        $referersByDomainData =[];
        //$referersByDomainLabels=[];

        $referersByMediumData=[];
        //$referersByMediumLabels=[];

        $referersByMediumOverTimeData=[];

        $referersByDomainRecentData=[];
        //$referersByDomainRecentLabels=[];

        $allReferersRows = [];
        $allReferersColumns = [];

        //$db = self::database();

        //$globalStats = $db->query("SELECT `referer`, `domain`, SUM(`hits`) AS hits, `medium` from `referers`, MIN(`referers`.`monthyear`) AS firstseen, MAX(`monthyear`) AS lastseen GROUP BY `monthyear` ORDER BY `lastseen` DESC LIMIT 0,100");
        $globalStats = self::database()->query("SELECT `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, MIN(`monthyear`) AS `firstseen`, MAX(`monthyear`) AS `lastseen`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers` ) GROUP BY `domain` ORDER BY `lastseen` DESC, `domain` ASC LIMIT 0,1000");
        if($globalStats){
            //echo 'RESULT=';
            //var_dump($globalStats->toArray());
            //return $globalStats->toArray();

            foreach($globalStats as $referer){
                //var_dump($referer);
                $referersByDomainData[] = [$referer->domain, $referer->hits];
                //$referersByDomainLabels[] = $referer->domain;
            }

        }
        else {
            Logger::LogWarning("refererStats(globalStats) : db error =".self::database()->lastError()->getMessage() );
            //echo 'DBERROR=';var_dump($db->lastError()->getMessage() );
        }


        $mediumStats = self::database()->query("SELECT `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, MIN(`monthyear`) AS `firstseen`, MAX(`monthyear`) AS `lastseen`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers` ) GROUP BY `medium` ORDER BY `lastseen` DESC, `medium` ASC LIMIT 0,1000");

        if($mediumStats){
            //echo 'RESULT=';
            //var_dump($globalStats->toArray());
            //return $globalStats->toArray();

            foreach($mediumStats as $referer){
                //var_dump($referer);
                $referersByMediumData[] =   [$referer->medium, $referer->hits];
                //$referersByMediumLabels[] = $referer->medium;
            }

        }
        else {
            Logger::LogWarning("refererStats(mediumStats) : db error =".self::database()->lastError()->getMessage() );
            //echo 'DBERROR=';var_dump($db->lastError()->getMessage() );
        }

        // Mediums over time
        $mediumStatsOverTime = self::database()->query("SELECT  `domain`, `medium`, SUM(`hits`) AS `hits`, `monthyear` FROM `referers` GROUP BY `medium`, `monthyear` ORDER BY `monthyear` ASC, `medium` ASC LIMIT 0,1000");
        if($mediumStatsOverTime){
            //$mediumNames=[];
            $mediumMonths=[];
            //$num = 0;
            foreach($mediumStatsOverTime as $medium){
                $monthyear = intval($medium->monthyear);
                $name = $medium->medium;
                //echo 'NAME=='.$name."\n";

                // Need to create the first entry ?
                if(!array_key_exists($name, $referersByMediumOverTimeData)){
                    $referersByMediumOverTimeData[$name]=[
                        'name' => $name,
                        'data' => [],
                    ];
                }

                // Remember period
                if(array_search($monthyear, $mediumMonths)===false){
                    $mediumMonths[]=$monthyear;
                }
                // value
                $referersByMediumOverTimeData[$name]['data'][$monthyear]=intval($medium->hits);
                //$referersByMediumOverTimeData[$monthyear]['data']=[];
                //$referersByMediumOverTimeData[$monthyear]['name']=$name;
            }

            // Process data
            $tmp=[];
            foreach($referersByMediumOverTimeData as $name => $data){

                // Add missing keys / zero values
                foreach($mediumMonths as $month){
                    if(!array_key_exists($month, $referersByMediumOverTimeData[$name]['data'])){
                        $referersByMediumOverTimeData[$name]['data'][$month]=0;
                    }
                }

                // Convert monthyear to date string
                $referersByMediumOverTimeData[$name]['data2']=[];
                foreach($referersByMediumOverTimeData[$name]['data'] as $my => $hits){
                    //$referersByMediumOverTimeData[$name]['data2'][getDateFromMonthYear($my, 'Y-m-d')]=$hits;
                    $referersByMediumOverTimeData[$name]['data2'][getDateFromMonthYear($my)]=$hits;
                }
                $referersByMediumOverTimeData[$name]['data']=$referersByMediumOverTimeData[$name]['data2'];
                unset($referersByMediumOverTimeData[$name]['data2']);

                // Object to array (remove key)
                $tmp[]=$referersByMediumOverTimeData[$name];
                //unset($referersByMediumOverTimeData[$name]);

                // Should be ok now
            }
            $referersByMediumOverTimeData=$tmp;
        }
        else {
            Logger::LogWarning("refererStats(mediumStatsOverTime) : db error =".self::database()->lastError()->getMessage() );
        }

        // Recent stats
        $monthyear = date('Ym');
        $domainRecentStats = self::database()->query("SELECT `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, `monthyear`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers` WHERE `monthyear`=${monthyear} ) WHERE `monthyear`=${monthyear} GROUP BY `domain` ORDER BY `medium` ASC, `domain` ASC LIMIT 0,1000");
        if($domainRecentStats){

            foreach($domainRecentStats as $referer){
                $referersByDomainRecentData[]   = [$referer->domain, $referer->hits];
            }

        }
        else{
            Logger::LogWarning("refererStats(domainRecentStats) : db error =".self::database()->lastError()->getMessage() );
            //else echo 'DBERROR=';var_dump($db->lastError()->getMessage() );
        }


        $AllDomainStats = self::database()->query("SELECT `id`, `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, MIN(`monthyear`) AS `timefrom`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers` ) GROUP BY `domain` ORDER BY `medium` ASC, `domain` ASC LIMIT 0,1000;");
        if($AllDomainStats){

            // Set column names
            $allReferersColumns = [
                //['label'=>'ID','field'=>'id','type'=>'text','sort'=>false,'search'=>false,'class'=>'myClass','width'=>'1fr'],
                ['label'=>'URL',        'field'=>'url',         'type'=>'text',     'sort'=>true,  'search'=>true,    'class'=>'myClass', 'width'=>'4fr'],
                ['label'=>'Domain',     'field'=>'domain',      'type'=>'text',     'sort'=>true,  'search'=>true,    'class'=>'myClass', 'width'=>'3fr'],
                ['label'=>'Medium',     'field'=>'medium',      'type'=>'text',     'sort'=>true,  'search'=>true,    'class'=>'myClass', 'width'=>'2fr'],
                ['label'=>'Hits',       'field'=>'hits',        'type'=>'number',   'sort'=>true,  'search'=>true,    'class'=>'myClass', 'width'=>'1fr'],
                ['label'=>'Percentage', 'field'=>'hitspercent', 'type'=>'text',   'sort'=>true,  'search'=>false,   'class'=>'percent', 'width'=>'2fr'],
                ['label'=>'Time From',  'field'=>'timefrom',    'type'=>'text', 'sort'=>true,  'search'=>false,   'class'=>'myClass', 'width'=>'2fr'],
            ];

            // Get max for calc
            $max = 0;
            foreach($AllDomainStats as $referer){
                if( $referer->hits>$max ) $max = $referer->hits;
            }

            // Set rows
            foreach($AllDomainStats as $referer){
                $allReferersRows[] = [
                    //'id'          => $referer->id,
                    'url'           => $referer->referer,
                    'domain'        => $referer->domain,
                    'medium'        => $referer->medium,
                    'hits'          => $referer->hits,
                    'hitspercent'   => round(($referer->hits/$max)*100),
                    'timefrom'      => getDateFromMonthYear($referer->timefrom),
                ];
            }
        }
        else Logger::LogWarning("refererStats(AllDomainStats) : db error =".self::database()->lastError()->getMessage() );

        return [
            'referersbydomaindata'          => $referersByDomainData,
            'referersbymediumdata'          => $referersByMediumData,
            'referersbymediumovertimedata'  => $referersByMediumOverTimeData,
            'referersbydomainrecentdata'    => $referersByDomainRecentData,
            'allreferersrows'               => $allReferersRows,
            'allrefererscolumns'            => $allReferersColumns,
        ];
    }

    public static function pageStats(): ?array {
        $pageStatsData  =[];
        $pageStatsLabels=[];

        $visitsOverTimeData    =[];
        $pageVisitsOverTimeData=[];

        $globalLanguagesData  =[];
        $languagesOverTimeData=[];

        // SYNC (todo: make this an option?)
        self::syncDayStats(); // tmp

        //$db = selfdatabase();
        $langQuery = '';
        if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ){
            foreach( kirby()->languages() as $l ){
                $langQuery .= ', SUM(`hits_'.$l->code().'`) AS `hits_'.$l->code().'`';
            }
        }

        $visitedPages = self::database()->query("SELECT `uid`, MIN(`monthyear`) AS `firstvisited`, MAX(`monthyear`) AS `lastvisited`, SUM(`hits`) AS `hits` ${langQuery} FROM `pagevisits` GROUP BY `uid` ORDER BY `uid` ASC, `monthyear` DESC LIMIT 0,1000;");
        if($visitedPages){
            // Set column names
            $pageStatsLabels = [
                //['label'=>'UID',            'field'=>'uid',             'type'=>'text',     'sort'=>true,  'search'=>true,    'class'=>'', 'width'=>'1fr'],
                //['label'=>'URL',            'field'=>'url',             'type'=>'text',     'sort'=>true,  'search'=>true,    'class'=>'', 'width'=>'4fr'],
                ['label'=>'UID',            'field'=>'uid',             'type'=>'text',     'sort'=>true,  'search'=>true,    'class'=>'', 'width'=>'4fr'],
                ['label'=>'Title',          'field'=>'title',           'type'=>'text',     'sort'=>true,  'search'=>true,    'class'=>'', 'width'=>'3fr'],
                ['label'=>'Hits',           'field'=>'hits',            'type'=>'number',   'sort'=>true,  'search'=>true,    'class'=>'', 'width'=>'1fr'],
                ['label'=>'Percentage',     'field'=>'hitspercent',     'type'=>'text',     'sort'=>true,  'search'=>false,   'class'=>'percent', 'width'=>'2fr'],
                ['label'=>'First Visited',  'field'=>'firstvisited',    'type'=>'text',     'sort'=>true,  'search'=>true,    'class'=>'', 'width'=>'2fr'],
                ['label'=>'Last Visited',   'field'=>'lastvisited',     'type'=>'text',     'sort'=>true,  'search'=>true,    'class'=>'', 'width'=>'2fr'],
            ];

            // Add language columns
            if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ){
                foreach( kirby()->languages() as $l ){
                    $pageStatsLabels[] = ['label'=>$l->name(), 'field'=>'hits_'.$l->code(), 'type'=>'number', 'sort'=>true,   'search'=>false,   'class'=>'', 'width'=>'1fr'];
                }
            }

            // Get max for calc
            $max = 0;
            foreach($visitedPages as $page){
                if( $page->hits > $max ) $max = $page->hits;
            }

            // Set rows
            foreach($visitedPages as $page){
                $kirbyPage = kirby()->page($page->uid);

                // Pages that don't exist (anymore?)
                if(!$kirbyPage){
                    $pageStatsData[] = [
                        'uid'           => '<a href="#'.$page->uid.'">'.$page->uid.'</a>',
                        'title'         => $page->uid . ' (404)',
                        'hits'          => intval($page->hits, 10),
                        'hitspercent'   => round(($page->hits/$max)*100),
                        'firstvisited'  => getDateFromMonthYear($page->firstvisited),
                        'lastvisited'   => getDateFromMonthYear($page->lastvisited),
                    ];
                    continue;
                }

                $pageStatsData[] = [
                    //'uid'           => $page->uid,
                    'uid'           => '<a href="'.$kirbyPage->url().'">'.$page->uid.'</a>',
                    //'url'           => $kirbyPage->url(),
                    'title'         => $kirbyPage->title()->value(),
                    'hits'          => intval($page->hits, 10),
                    'hitspercent'   => round(($page->hits/$max)*100),
                    'firstvisited'  => getDateFromMonthYear($page->firstvisited),
                    'lastvisited'   => getDateFromMonthYear($page->lastvisited),
                ];

                // Inject language data
                if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ){
                    $lastEntry = count($pageStatsData)-1;
                    foreach( kirby()->languages() as $l ){
                        $langStr = 'hits_'.$l->code();
                        $pageStatsData[$lastEntry][$langStr] = intval($page->$langStr, 10);
                    }
                }
            }
        }

        // Compute visits over time (monthly)
        $visitsOverTime = self::database()->query("SELECT `monthyear`, SUM(`hits`) AS `hits` FROM `pagevisits` GROUP BY `monthyear` ORDER BY `monthyear` ASC LIMIT 0,1000;");
        if($visitsOverTime){

            foreach($visitsOverTime as $timeFrame){
                $visitsOverTimeData[]=[ getDateFromMonthYear($timeFrame->monthyear,'Y-m-d'), $timeFrame->hits ];
                //$visitsOverTimeLabels[]=date('M Y',$time);//"${month} - ${year}";
                //$visitsOverTimeData[]=$timeFrame->hits;
            }
        }
        else Logger::LogWarning("pageStats(visitsOverTime) : db error =".self::database()->lastError()->getMessage() );

        // Get pages over time
        // Todo: Add total and remove visitsOverTimeData, see https://stackoverflow.com/a/39374290/58565
        $pageVisitsOverTimeData=[];
        $pageVisitsOverTime = self::database()->query("SELECT `uid`, SUM(`hits`) AS `hits`, `monthyear` FROM `pagevisits` GROUP BY `UID`, `monthyear` ORDER BY `monthyear` ASC, `uid` ASC LIMIT 0,1000");
        if($pageVisitsOverTime){
            $pageMonths=[];
            foreach($pageVisitsOverTime as $page){
                $monthyear = intval($page->monthyear);
                $name = $page->uid;

                // Need to create the first entry ?
                if(!array_key_exists($name, $pageVisitsOverTimeData)){
                    $pageVisitsOverTimeData[$name]=[
                        'name' => $name,
                        'data' => [],
                    ];
                }

                // Remember period
                if(array_search($monthyear, $pageMonths)===false){
                    $pageMonths[]=$monthyear;
                }
                // value
                $pageVisitsOverTimeData[$name]['data'][$monthyear]=intval($page->hits);
            }

            // Process data
            $tmp=[];
            foreach($pageVisitsOverTimeData as $name => $data){

                // Add missing keys / zero values
                foreach($pageMonths as $month){
                    if(!array_key_exists($month, $pageVisitsOverTimeData[$name]['data'])){
                        $pageVisitsOverTimeData[$name]['data'][$month]=0;
                    }
                }

                // Convert monthyear to date string
                $pageVisitsOverTimeData[$name]['data2']=[];
                foreach($pageVisitsOverTimeData[$name]['data'] as $my => $hits){
                    $pageVisitsOverTimeData[$name]['data2'][getDateFromMonthYear($my)]=$hits;
                }
                $pageVisitsOverTimeData[$name]['data']=$pageVisitsOverTimeData[$name]['data2'];
                unset($pageVisitsOverTimeData[$name]['data2']);

                // Convert uid to title
                if( $kirbyPage = kirby()->page($pageVisitsOverTimeData[$name]['name']) ){
                    $pageVisitsOverTimeData[$name]['name']=$kirbyPage->title()->value();
                }

                // Object to array (remove key)
                $tmp[]=$pageVisitsOverTimeData[$name];

                // Should be ok now
            }
            $pageVisitsOverTimeData=$tmp;
        }
        else Logger::LogWarning("pageStats(pageVisitsOverTime) : db error =".self::database()->lastError()->getMessage() );



        // Compute Global languages data
        if( option('daandelange.simplestats.tracking.enableVisitLanguages') === true /* && kirby()->multilang() */  ) {
            // Build langs part of query
            $queryLangs = '';
            $kirbyLangs = [];
            if( kirby()->multilang() ){
                foreach( kirby()->languages() as $language){
                    $queryLangs .= ', SUM(`hits_'.$language->code().'`) AS `'.$language->code().'`';
                    $kirbyLangs[] = $language->code();

                    // Create keys for each language
                    $languagesOverTimeData[$language->code()]=[
                        'name' => $language->name(),
                        'data' => [], // holds pairs of [date,value]
                    ];

                    // Init $globalLanguagesData
                    $globalLanguagesData[$language->code()] = [$language->name(),0];
                }
            }
            else {
                //$queryLangs .= ', SUM(`hits_en`) AS `en`';
                //$kirbyLangs[] = 'en';
            }

            // Compute $languagesOverTime and $globalLanguagesData
            $languagesOverTimeQ = self::database()->query("SELECT `monthyear` ${queryLangs} FROM `pagevisits` GROUP BY `monthyear` ORDER BY `monthyear` ASC LIMIT 0,1000;");
            if($languagesOverTimeQ){
                //$allLangMonths = [];
                foreach($languagesOverTimeQ as $timeFrame){
                    $monthyear = getDateFromMonthYear(intval($timeFrame->monthyear),'Y-m-d');

                    // Get hits for each lang on this period
                    foreach($kirbyLangs as $l){
                        // value
                        $languagesOverTimeData[$l]['data'][$monthyear]=intval($timeFrame->$l);

                        // compute globals
                        $globalLanguagesData[$l][1] += $languagesOverTimeData[$l]['data'][$monthyear];
                    }

                    // Remember this period, needed to consolidate data for charts
                    //if(array_search($monthyear, $allLangMonths)===false){
                    //    $allLangMonths[]=$monthyear;
                    //}
                }

                // Check if all languages have values (add zero-values)
                // Not needed here, commented if needed in the future
                //foreach($allLangMonths as $m){
                //    foreach($kirbyLangs as $l){
                //        if( !array_key_exists($m, $languagesOverTimeData[$l]['data'])) $languagesOverTimeData[$l]['data'][$m]=0;
                //    }
                //}

                // Remove empty rows in $languagesOverTimeData
                foreach($kirbyLangs as $l){
                   if( array_key_exists($l, $languagesOverTimeData) && array_key_exists('data', $languagesOverTimeData[$l]) ){
                       $total = 0;
                       foreach($languagesOverTimeData[$l]['data'] as $hits){
                           $total+=$hits;
                       }
                       if($total===0){
                           unset($languagesOverTimeData[$l]);
                       }
                   }
                }

                // Rename keys to nums so that the charts accept the data
                foreach( $languagesOverTimeData as $key => $data) {
                    $languagesOverTimeData[]=$data;
                    unset($languagesOverTimeData[$key]);
                };
                //var_dump($languagesOverTimeData);

                // Rename keys to nums so that the charts accept the data
                foreach( $globalLanguagesData as $key => $data) {
                    // Only keep non-zero values (so panel sees them empty)
                    if( isset($data[1]) && $data[1]!=0 ) $globalLanguagesData[]=$data;
                    unset($globalLanguagesData[$key]);
                };
                //var_dump($globalLanguagesData);
            }
            else Logger::LogWarning("pageStats(languagesOverTime) : db error =".self::database()->lastError()->getMessage() );
        }


        return [
            'pagestatsdata'         => $pageStatsData,
            'pagestatslabels'       => $pageStatsLabels,

            'visitsovertimedata'    => $visitsOverTimeData,
            'pagevisitsovertimedata'=> $pageVisitsOverTimeData,

            'globallanguagesdata'   => $globalLanguagesData,
            'languagesovertimedata' => $languagesOverTimeData,
            'languagesAreEnabled'   => (option('daandelange.simplestats.tracking.enableVisitLanguages') === true) && kirby()->multilang(),
        ];
    }

    // Collect garbage, synthetize it and anonymously store it in permanent db
    public static function syncDayStats(): bool {

        // init db
        //$db = self::database();

        // init return variabes
        //$sitePages = [];
        $newPageVisits = [];
        $newDevices = [];
        $newEngines = [];
        $newSystems = [];

        // Get visitors older then 1 day
        $yesterday = time() - option('daandelange.simplestats.tracking.uniqueSeconds', 24*60*60);
        $visitors = self::database()->query("SELECT `userunique`, `visitedpages`, `osfamily`, `devicetype`, `browserengine`, `timeregistered` FROM `pagevisitors` WHERE `timeregistered` < ${yesterday} ORDER BY `timeregistered` ASC LIMIT 0,1000;");

        if($visitors){
            //echo 'RESULT='."SELECT `userunique`, `visitedpages`, `osfamily`, `devicetype`, `browserengine`, `timeregistered` FROM `pagevisitors` WHERE `timeregistered` < ${yesterday} ORDER BY `timeregistered` ASC LIMIT 0,1000;";
            //var_dump($visitors->toArray());

            // process each one
            foreach($visitors as $visitor){
                //var_dump($visitor);
                $yearMonth = date('Ym', intval($visitor->timeregistered) );

                // Compute visited pages
                if( $visitor->visitedpages && !empty( $visitor->visitedpages ) ){
                    // Create keys
                    if( !array_key_exists($yearMonth, $newPageVisits) ) $newPageVisits[$yearMonth]=[];

                    $visitorPages = [];
                    foreach( explode(',', $visitor->visitedpages) as $page){
                        $page = trim($page);

                        // Default lang (normally never used)
                        $pageLang = kirby()->multilang()?kirby()->language()->code():'en';

                        // Remove lang part
                        //if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ) {
                        if( ($pos=strpos($page, '::')) !== false ){
                            // Separate page::lang
                            $tmpLang = substr($page, $pos+strlen('::'));
                            $page = substr($page, 0, $pos);
                            $tmpL = kirby()->language($tmpLang);
                            // Valid lang
                            if($tmpL) $pageLang = $tmpL->code();
                            // Unvalid
                            //else $pageLang = kirby()->defaultLanguage()->code();
                        }

                        // Newly visited page ?
                        if( !array_key_exists($page, $visitorPages) ){
                            $visitorPages[$page]=[
                                //'uid',
                                'hits' => 1, // Only counts 1 global visit if user visited the same page in multiple languages
                                'langs' => [],
                            ];
                            // Add languages
                            if( kirby()->multilang() ){
                                foreach( kirby()->languages() as $language){
                                    $visitorPages[$page]['langs'][$language->code()]=0;
                                }
                            }
                            else {
                                $visitorPages[$page]['langs'][$pageLang] = 0; // Create only, incremented below
                            }
                        }

                        // Count a language visit
                        $visitorPages[$page]['langs'][$pageLang]++;
                    }

                    // Insert composed $visitorPages into $newPageVisits
                    foreach($visitorPages as $page => $pageInfo){

                        // Insert page ?
                        $key = array_search($page, array_column($newPageVisits[$yearMonth], 'uid') );
                        if( $key === false ){
                            //echo 'Created $newPageVisits['.$yearMonth.'][] --- as '.$page."\n";
                            $newPageVisits[$yearMonth][]=[
                                'hits' => 1,//$pageInfo['hits'],
                                'uid'  => $page,
                                'yearmonth' => $yearMonth,
                                'langhits' => $pageInfo['langs'],
                            ];
                        }
                        // Increment existing page ?
                        else {
                            //echo 'Incrementing $newPageVisits['.$yearMonth.']['.$key.'] '."\n";

                            // increment total hits
                            $newPageVisits[$yearMonth][$key]['hits']++;// +=$pageInfo['hits'];

                            // Append lang visits
                            foreach( $newPageVisits[$yearMonth][$key]['langhits'] as $lang => $hits ){
                                if( $hits > 0 ) $newPageVisits[$yearMonth][$key]['langhits'][$lang]++; // += $hits;
                            }

                        }
                    }
                }

                // Compute Devices
                if( $visitor->devicetype && !empty( $visitor->devicetype ) ){
                    if(!array_key_exists($yearMonth, $newDevices)) $newDevices[$yearMonth] = [];

                    // Insert device ?
                    $key = array_search($visitor->devicetype, array_column($newDevices[$yearMonth], 'device') );
                    if( $key === false ){
                        $newDevices[$yearMonth][]=[
                            'hits' => 1,
                            'device'  => $visitor->devicetype,
                            'yearmonth' => $yearMonth,
                        ];
                    }
                    // Increment ?
                    else {
                        $newDevices[$yearMonth][$key]['hits']++;
                    }
                }

                // Compute Systems
                if( $visitor->osfamily && !empty( $visitor->osfamily ) ){
                    if(!array_key_exists($yearMonth, $newSystems)) $newSystems[$yearMonth] = [];

                    // Insert system ?
                    $key = array_search($visitor->osfamily, array_column($newSystems[$yearMonth], 'system') );
                    if( $key === false ){
                        $newSystems[$yearMonth][]=[
                            'hits' => 1,
                            'system'  => $visitor->osfamily,
                            'yearmonth' => $yearMonth,
                        ];
                    }
                    // Increment ?
                    else {
                        $newSystems[$yearMonth][$key]['hits']++;
                    }
                }

                // Compute Engines
                if( $visitor->browserengine && !empty( $visitor->browserengine ) ){
                    if(!array_key_exists($yearMonth, $newEngines)) $newEngines[$yearMonth] = [];

                    // Insert system ?
                    $key = array_search($visitor->browserengine, array_column($newEngines[$yearMonth], 'engine') );
                    if( $key === false ){
                        $newEngines[$yearMonth][]=[
                            'hits' => 1,
                            'engine'  => $visitor->browserengine,
                            'yearmonth' => $yearMonth,
                        ];
                    }
                    // Increment ?
                    else {
                        $newEngines[$yearMonth][$key]['hits']++;
                    }
                }

                // Delete entry
                // Todo: Assumes that saving the data won't fail. Make this deletion happen on succesful sync.
                if( $visitor->userunique && !self::database()->query("DELETE FROM `pagevisitors` WHERE `userunique`='".$visitor->userunique."'; ") ){
                    Logger::LogWarning('DBFAIL. Error on syncing stats. On delete visitor. Error='.self::database()->lastError()->getMessage() );
                }
            }

            //var_dump($newPageVisits);
            //var_dump($newDevices);
            //var_dump($newSystems);
            //var_dump($newEngines);

            // Update page visits
            if( count($newPageVisits)>0 ){

                // Build langs part of query
                // Todo: maybe not needed here ??
                $queryLangs = '';
                if( kirby()->multilang() ){
                    foreach( kirby()->languages() as $language){
                        $queryLangs .= ((strlen($queryLangs)>0)?', ':'').'`hits_'.$language->code().'`';
                    }
                }
                else {
                    $queryLangs = '`hits_en`';
                }

                // Loop dates
                foreach( $newPageVisits as $monthYear => $monthlyPageVisits ){

                    //echo 'Updating page visits for '.$monthYear."\n"; continue;
                    $existingPages = self::database()->query("SELECT `id`, `uid`, `hits`, ${queryLangs} FROM `pagevisits` WHERE `monthyear` = ${monthYear} LIMIT 0,1000;");

                    // Dirty security for if languages make the request fail
/*
                    if( !$existingPages ){
                        // retry query without the language strings
                        $existingPages = self::database()->query("SELECT `id`, `uid`, `hits` FROM `pagevisits` WHERE `monthyear` = ${monthYear} LIMIT 0,1000;");
                    }
*/
                    // Query ok ?
                    if($existingPages){
                        //echo "EXISTING=";var_dump($existingPages->toArray());

                        $monthPages = $existingPages->toArray();


                        // Loop newly visited pages (existing)
                        foreach( $monthlyPageVisits as $newPageInfo ){
                            $newHits = $newPageInfo['hits'];

                            $key = array_search( $newPageInfo['uid'], array_column($monthPages, 'uid') );
                            // Needs new entry this month ?
                            if( $key === false ){
                                //echo "NEED TO INSERT PAGE@DATE\n";
                                //$newHits = $newPageInfo['hits'];
                                $uid = $newPageInfo['uid'];

                                // Ignore non-existent pages
                                if( !kirby()->page($uid) ){
                                    //echo 'Page not found !';
                                    Logger::LogVerbose("Error syncing new visits : Kirby could not find the registered page (${uid}). Has it been deleted ?");
                                    continue;
                                }

                                // Compose languages
                                $langKeys = '';
                                $langValues = '';
                                // Multilang query
                                if( kirby()->multilang() ){
                                    foreach( kirby()->languages() as $language){
                                        //
                                        $langKeys .= ', `hits_'.$language->code().'`';

                                        if( isset($newPageInfo['langhits'][$language->code()]) && $newPageInfo['langhits'][$language->code()] > 0 ){
                                            $langValues .= ', '.$newPageInfo['langhits'][$language->code()];
                                        }
                                        else {
                                            $langValues .= ', 0';
                                        }
                                    }
                                }
                                // Single lang query
                                else {
                                    $langKeys = ', `hits_en`';
                                    if( $newPageInfo['langhits']['en'] > 0 ){
                                        $langValues .= ', '.$newPageInfo['langhits']['en'];
                                    }
                                    else {
                                        $langValues .= ', 0';
                                    }
                                }

                                // Save
                                if(!self::database()->query("INSERT INTO `pagevisits` (`uid`, `hits`, `monthyear` ${langKeys} ) VALUES ('${uid}', ${newHits}, ${monthYear} ${langValues})")){
                                    Logger::LogWarning("Could not INSERT pagevisits while syncing. Error=".self::database()->lastError()->getMessage());
                                }
                            }
                            // Update existing entry
                            elseif($newHits>0) { // Todo : if robots dont count as a hit, this will need reviewed.
                                //echo "---";var_dump($monthPages[$key]->id );
                                //$newHits = intval($newPageInfo['hits']) + intval($monthPages->get($key)['hits']);
                                $id = $monthPages[$key]->id;

                                // Prepare lang query
                                $langEdits = '';
                                // Multilang query
                                if( kirby()->multilang() ){
                                    foreach( kirby()->languages() as $language){

                                        if( isset($newPageInfo['langhits'][$language->code()]) && $newPageInfo['langhits'][$language->code()] > 0 ){
                                            $langEdits .= ', `hits_'.$language->code().'` = `hits_'.$language->code().'` + '.$newPageInfo['langhits'][$language->code()];
                                        }
                                    }
                                }
                                // Single lang query
                                else {
                                    $tmpL = 'en';
                                    // Ignore 0 hits
                                    if( isset($newPageInfo['langhits'][$tmpL]) && $newPageInfo['langhits'][$tmpL] > 0 ){
                                        $langEdits .= ', `hits_'.$tmpL.'`=`hits_'.$tmpL.'` + '.$newPageInfo['langhits'][$tmpL];
                                    }
                                }

                                //echo "UPDATE PAGE@DATE, HITS=${newHits} !\n";
                                if(!self::database()->query("UPDATE `pagevisits` SET `hits`=`hits` + ${newHits} ${langEdits} WHERE `id`=${id}") ){
                                    Logger::LogWarning("Could not UPDATE pagevisits while syncing. Error=".self::database()->lastError()->getMessage());
                                }
                            }

                        }
                    }
                    else{
                        Logger::LogWarning("Could not SELECT pagevisits while syncing stats. Error=".self::database()->lastError()->getMessage());
                    }

                }
            }

            // Update Devices
            if( count($newDevices)>0 ){

                // Loop months
                foreach( $newDevices as $monthYear => $monthlyDevices ){
                    // Query existing db
                    $existingDevices = self::database()->query("SELECT `id`, `device`, `hits` FROM `devices` WHERE `monthyear` = '${monthYear}' LIMIT 0,1000;");

                    if($existingDevices){
                        //echo "EXISTING=";var_dump($existingPages->toArray());
                        $existingDevicesA = $existingDevices->toArray();

                        // Loop visited devices (existing)
                        foreach( $monthlyDevices as $newDeviceInfo ){
                            $newHits = $newDeviceInfo['hits'];

                            $key = array_search( $newDeviceInfo['device'], array_column($existingDevicesA, 'device') );
                            // Needs new entry ?
                            if( $key === false ){
                                // Todo : verify validity of data ?
                                // Save
                                if(!self::database()->query("INSERT INTO `devices` (`device`, `hits`, `monthyear`) VALUES ('".$newDeviceInfo['device']."', ${newHits}, ${monthYear})")){
                                    Logger::LogWarning("Could not INSERT new device while syncing. Error=".self::database()->lastError()->getMessage());
                                }
                            }
                            // Update existing entry
                            elseif($newHits>0) {
                                $id = $existingDevicesA[$key]->id;
                                if(!self::database()->query("UPDATE `devices` SET `hits`=`hits` + ${newHits} WHERE `id`=${id};") ){
                                    Logger::LogWarning("Could not UPDATE devices hits while syncing. Error=".self::database()->lastError()->getMessage());
                                }
                            }

                        }
                    }
                    else {
                        Logger::LogWarning("Could not SELECT devices while syncing. Error=".self::database()->lastError()->getMessage());
                    }
                }
            }

            // Update Systems
            if( count($newSystems)>0 ){

                // Loop months
                foreach( $newSystems as $monthYear => $monthlySystems ){
                    // Query existing db
                    $existingSystems = self::database()->query("SELECT `id`, `system`, `hits` FROM `systems` WHERE `monthyear` = '${monthYear}' LIMIT 0,1000;");

                    if($existingSystems){
                        $existingSystemsA = $existingSystems->toArray();

                        // Loop visited systems (existing)
                        foreach( $monthlySystems as $newSystemInfo ){
                            $newHits = $newSystemInfo['hits'];

                            $key = array_search( $newSystemInfo['system'], array_column($existingSystemsA, 'system') );
                            // Needs new entry ?
                            if( $key === false ){
                                // Todo : verify validity of data ?
                                // Save
                                if(!self::database()->query("INSERT INTO `systems` (`system`, `hits`, `monthyear`) VALUES ('".$newSystemInfo['system']."', ${newHits}, ${monthYear})")){
                                    //echo 'DBFAIL [insert new system]'."\n";
                                    Logger::LogWarning("Could not INSERT systems while syncing. Error=".self::database()->lastError()->getMessage());
                                }
                            }
                            // Update existing entry
                            elseif($newHits>0) {
                                $id = $existingSystemsA[$key]->id;
                                if(!self::database()->query("UPDATE `systems` SET `hits`=`hits` + ${newHits} WHERE `id`=${id}") ){
                                    Logger::LogWarning("Could not UPDATE system hits while syncing. Error=".self::database()->lastError()->getMessage());
                                }
                            }

                        }
                    }
                    else{
                        Logger::LogWarning("Could not SELECT monthly systems while syncing. Error=".self::database()->lastError()->getMessage());
                    }
                }
            }

            // Update Engines
            if( count($newEngines)>0 ){

                // Loop months
                foreach( $newEngines as $monthYear => $monthlyEngines ){
                    // Query existing db
                    $existingEngines = self::database()->query("SELECT `id`, `engine`, `hits` FROM `engines` WHERE `monthyear` = '${monthYear}' LIMIT 0,1000;");

                    if($existingEngines){
                        $existingEnginesA = $existingEngines->toArray();

                        // Loop visited engines (existing)
                        foreach( $monthlyEngines as $newEngineInfo ){
                            $newHits = $newEngineInfo['hits'];

                            $key = array_search( $newEngineInfo['engine'], array_column($existingEnginesA, 'engine') );
                            // Needs new entry ?
                            if( $key === false ){
                                // Todo : verify validity of data ?
                                // Save
                                if(!self::database()->query("INSERT INTO `engines` (`engine`, `hits`, `monthyear`) VALUES ('".$newEngineInfo['engine']."', ${newHits}, ${monthYear})")){
                                    Logger::LogWarning("Could not INSERT engines while syncing stats. Error=".self::database()->lastError()->getMessage());
                                }
                                //else echo "INSERTED_ENGINE!";
                            }
                            // Update existing entry
                            elseif($newHits>0) {
                                $id = $existingEnginesA[$key]->id;
                                if(!self::database()->query("UPDATE `engines` SET `hits`=`hits` + ${newHits} WHERE `id`=${id};") ){
                                    Logger::LogWarning("Could not UPDATE engine hits while syncing stats. Error=".self::database()->lastError()->getMessage());
                                }
                                //else echo "UPDATED_ENGINE!";
                            }

                        }
                    }
                    else {
                        Logger::LogWarning("Could not SELECT monthly engines while syncing stats. Error=".self::database()->lastError()->getMessage());
                    }
                }
            }

            // Todo : Prevent data loss on errors ?

            return true;
        }
        else {
            Logger::LogWarning("Error selecting visitors from DB while syncing stats. Error=".self::database()->lastError()->getMessage());
        }

        return false;
    }
}
