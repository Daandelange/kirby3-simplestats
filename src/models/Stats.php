<?php
declare(strict_types=1); // Dont mix types

namespace daandelange\SimpleStats;

use Kirby\Database\Database;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Obj;
use Kirby\Cms\Page;

use ErrorException;
use Exception;
use Throwable;

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
        $dbvQ = self::database()->query("SELECT `version`, `migrationdate` FROM `simplestats` ORDER BY `migrationdate` DESC LIMIT 0,1000");
        if( $dbvQ ){
            if( $dbvQ->isNotEmpty() ){
                $dbVersion = intval($dbvQ->first()->version, 10);
                foreach($dbvQ as $v){
                    $dbArray[]=[
                        'version' => intval($v->version, 10),
                        'date'    => date('Y-m-d', getTimeFromVersionDate(intval($v->migrationdate,10)) ),
                    ];
                }
            }
            // No version but table exists = weird !
            else {
                $dbVersion = t('simplestats.info.db.noversion', 'None!');
            }
        }
        else {
            $error = self::database()->lastError()->getMessage();
            // v1 didn't have the simplestats table (only way to detect)
            if( stripos($error, 'no such table:') !== false && stripos($error, 'simplestats') !== false ){
                $dbVersion = '1 ('.t('simplestats.info.db.versionless','versionless').')';
            }
            else {
                $dbVersion = t('simplestats.info.db.versionerror', 'Unable to query : ');//.$error;
            }
        }

        // Get Db file information
        $dbFile = option('daandelange.simplestats.tracking.database');
        $dbSize = '?? Kb';
        if($dbFile){
            try {
                if( file_exists($dbFile) && $fileSize = filesize($dbFile) ){
                    $dbSize = $fileSize;
                }
            } catch (Exception $e){
                // ignore
            }
            // Use short path for display
            $dbFile = str_replace( kirby()->root(),'', $dbFile);
        }

        return [
            'softwareDbVersion' => self::engineDbVersion,
            'dbVersion'         => $dbVersion,
            'dbHistoryLabels'   => [
                ['label'=>t('simplestats.info.db.table.dbversion'), 'field'=>'version', 'type'=>'number',   'sortable'=>true,  'width'=>'34%'],
                ['label'=>t('simplestats.info.db.table.usedsince'), 'field'=>'date',    'type'=>'date',     'sortable'=>true,  'width'=>'66%', 'dateInputFormat'=>'yyyy-MM-dd', 'dateOutputFormat'=>'dd MMM yyyy'], // todo: Date display should be customized to custom timespans
            ],
            'dbHistory'         => $dbArray,
            'upgradeRequired'   => self::engineDbVersion != $dbVersion,
            'databaseLocation'  => $dbFile ?? '[undefined]',
            'databaseSize'      => $dbSize,
        ];
    }

    public static function listvisitors(): array {
        //$log  = new Log;
        //var_dump( self::singleton()->database() );
        //$db = self::database();
        $result = self::database()->query('SELECT `visitedpages`, `osfamily`, `devicetype`, `browserengine`, `timeregistered` FROM `pagevisitors` LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($result){
            //var_dump(array_keys($result->get(0)->toArray()));
            //var_dump(($result));

            // Get keys
//             $keys = [];
//             foreach($result as $visitor){
//                 $keys = array_keys($visitor->toArray());
//                 //var_dump(array_keys($visitor->toArray()));
//                 break; // 1 iteration should be enough here
//             }
//
//             // Format keys
//             foreach($keys as $key => $value){
//                 $keys[$key] = ['label'=>$value,'field'=>$value, 'type'=>'text','sort'=>false,'search'=>false,'class'=>'myClass','width'=>'1fr'];
//             }
            $keys =[
                [ 'label' => t('simplestats.table.visitedpages',    'Visited Pages'),   'field' => 'visitedpages',      'type' => 'text', 'sortable' => false,  'width' => '50%' ],
                [ 'label' => t('simplestats.table.osfamily',        'OS Family'),       'field' => 'osfamily',          'type' => 'text', 'sortable' => true,   'width' => '15%' ],
                [ 'label' => t('simplestats.table.devicetype',      'Device Type'),     'field' => 'devicetype',        'type' => 'text', 'sortable' => true,   'width' => '10%' ],
                [ 'label' => t('simplestats.table.browserengine',   'Browser Engine'),  'field' => 'browserengine',     'type' => 'text', 'sortable' => true,   'width' => '10%' ],
                [ 'label' => t('simplestats.table.timeregistered',  'Time Registered'), 'field' => 'timeregistered',    'type' => 'date', 'sortable' => true,   'width' => '15%', 'globalSearchDisabled'=>true, 'dateInputFormat'=>'yyyy-MM-dd HH:mm', 'dateOutputFormat'=>'d MMMM yyyy HH:mm'],
            ];

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
                $rows[$key]['timeregistered'] = date( SIMPLESTATS_PRECISE_DATE_FORMAT, intval($rows[$key]['timeregistered']) );

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

        // Need to hide bots ?
        $hideBotsQueryPart = '';
        if( true === option('daandelange.simplestats.panel.hideBots', false) ) $hideBotsQueryPart = ' WHERE `device` != "server"';

        // Get devices
        $allDevices = [];
        $allDevicesResult = self::database()->query('SELECT `device`, `hits` FROM `devices`'.$hideBotsQueryPart.' GROUP BY `device` ORDER BY `device` DESC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($allDevicesResult){
            // parse sql result, line by line
            foreach($allDevicesResult as $device){
                //var_dump($device->toArray());
                $allDevices[] = [self::translateDeviceType($device->device),$device->hits];
            }
        }

        // Get Systems
        $allSystems = [];
        if( true === option('daandelange.simplestats.panel.hideBots', false) ) $hideBotsQueryPart = ' WHERE `system` != "bot"';
        $allSystemsResult = self::database()->query('SELECT `system`, `hits` FROM `systems`'.$hideBotsQueryPart.' GROUP BY `system` ORDER BY `system` DESC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($allSystemsResult){
            // parse sql result, line by line
            foreach($allSystemsResult as $system){
                //var_dump($device->toArray());
                $allSystems[] = [$system->system,$system->hits];
            }
        }

        // Get Engines
        $allEngines = [];
        if( true === option('daandelange.simplestats.panel.hideBots', false) ) $hideBotsQueryPart = ' WHERE `engine` != "bot"';
        $allEnginesResult = self::database()->query('SELECT `engine`, `hits` FROM `engines`'.$hideBotsQueryPart.' GROUP BY `engine` ORDER BY `engine` DESC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
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
        if( true === option('daandelange.simplestats.panel.hideBots', false) ) $hideBotsQueryPart = ' WHERE `device` != "server"';
        $devicesOverTime = self::database()->query('SELECT `device`, SUM(`hits`) AS `hits`, `monthyear` FROM `devices`'.$hideBotsQueryPart.' GROUP BY `device`, `monthyear` ORDER BY `monthyear` ASC, `device` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($devicesOverTime){
            $devicePeriods=[];
            foreach($devicesOverTime as $device){
                $devicePeriod = intval($device->monthyear, 10);
                $name = $device->device;
                //echo 'NAME=='.$name."\n";

                // Need to create the first entry ?
                if(!array_key_exists($name, $devicesOverTimeData)){
                    $devicesOverTimeData[$name]=[
                        'name' => self::translateDeviceType($name),
                        'data' => [],
                    ];
                }

                // Remember period
                if( array_search($devicePeriod, $devicePeriods)===false ){
                    $devicePeriods[]=$devicePeriod;
                }
                // value
                $devicesOverTimeData[$name]['data'][$devicePeriod]=intval($device->hits);
            }

            // Add missing periods from first date to now (happens when no data at all in a full period)
            if( count($devicePeriods) > 0 ) for($period=min($devicePeriods); $period <= getPeriodFromTime(); $period=incrementPeriod($period) ){
                if( array_search($period, $devicePeriods) === false ) $devicePeriods[]=$period;
            }

            // Process data
            $tmp=[];
            foreach($devicesOverTimeData as $name => $data){

                // Add missing keys / zero values
                foreach($devicePeriods as $month){
                    if(!array_key_exists($month, $devicesOverTimeData[$name]['data'])){
                        $devicesOverTimeData[$name]['data'][$month]=0;
                    }
                }

                // Convert periods to date string
                $devicesOverTimeData[$name]['data2']=[];
                foreach($devicesOverTimeData[$name]['data'] as $period => $hits){
                    $devicesOverTimeData[$name]['data2'][getDateFromPeriod(intval($period), SIMPLESTATS_TIMELINE_DATE_FORMAT)]=$hits;
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

    public static function translateDeviceType( string $key ) : string {
        if($translation = t('simplestats.devices.names.'.$key)){
            return $translation;
        }

        return $key;
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
        $globalStats = self::database()->query("SELECT `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, MIN(`monthyear`) AS `firstseen`, MAX(`monthyear`) AS `lastseen`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers` ) GROUP BY `domain` ORDER BY `lastseen` DESC, `domain` ASC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);
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


        $mediumStats = self::database()->query("SELECT `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, MIN(`monthyear`) AS `firstseen`, MAX(`monthyear`) AS `lastseen`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers` ) GROUP BY `medium` ORDER BY `lastseen` DESC, `medium` ASC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);

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
        $mediumStatsOverTime = self::database()->query("SELECT  `domain`, `medium`, SUM(`hits`) AS `hits`, `monthyear` FROM `referers` GROUP BY `medium`, `monthyear` ORDER BY `monthyear` ASC, `medium` ASC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);
        if($mediumStatsOverTime){
            //$mediumNames=[];
            $mediumPeriods=[];
            //$num = 0;
            foreach($mediumStatsOverTime as $medium){
                $mediumPeriod = intval($medium->monthyear, 10);
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
                if(array_search($mediumPeriod, $mediumPeriods)===false){
                    $mediumPeriods[]=$mediumPeriod;
                }
                // value
                $referersByMediumOverTimeData[$name]['data'][$mediumPeriod]=intval($medium->hits);
                //$referersByMediumOverTimeData[$mediumPeriod]['data']=[];
                //$referersByMediumOverTimeData[$mediumPeriod]['name']=$name;
            }

            // Add missing periods from first date to now (happens when no data at all in a full period)
            if( count($mediumPeriods) > 0 ) for($period=min($mediumPeriods); $period <= getPeriodFromTime(); $period=incrementPeriod($period) ){
                if( array_search($period, $mediumPeriods) === false ) $mediumPeriods[]=$period;
            }

            // Process data
            $tmp=[];
            foreach($referersByMediumOverTimeData as $name => $data){

                // Add missing keys / zero values
                foreach($mediumPeriods as $period){
                    if(!array_key_exists($period, $referersByMediumOverTimeData[$name]['data'])){
                        $referersByMediumOverTimeData[$name]['data'][$period]=0;
                    }
                }

                // Convert monthyear to date string
                $referersByMediumOverTimeData[$name]['data2']=[];
                foreach($referersByMediumOverTimeData[$name]['data'] as $my => $hits){
                    //$referersByMediumOverTimeData[$name]['data2'][getDateFromPeriod($my, SIMPLESTATS_TIMELINE_DATE_FORMAT)]=$hits;
                    $referersByMediumOverTimeData[$name]['data2'][getDateFromPeriod($my, SIMPLESTATS_TIMELINE_DATE_FORMAT)]=$hits;
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
        $todayPeriod = getPeriodFromTime();
        $domainRecentStats = self::database()->query("SELECT `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, `monthyear`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers` WHERE `monthyear`=${todayPeriod} ) WHERE `monthyear`=${todayPeriod} GROUP BY `domain` ORDER BY `medium` ASC, `domain` ASC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);
        if($domainRecentStats){

            foreach($domainRecentStats as $referer){
                $referersByDomainRecentData[]   = [$referer->domain, $referer->hits];
            }

        }
        else{
            Logger::LogWarning("refererStats(domainRecentStats) : db error =".self::database()->lastError()->getMessage() );
            //else echo 'DBERROR=';var_dump($db->lastError()->getMessage() );
        }


        $AllDomainStats = self::database()->query("SELECT `id`, `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, MIN(`monthyear`) AS `timefrom`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers` ) GROUP BY `domain` ORDER BY `medium` ASC, `domain` ASC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);
        if($AllDomainStats){

            // Set column names
            $allReferersColumns = [
                //['label'=>'ID','field'=>'id','type'=>'text','sort'=>false,'search'=>false,'class'=>'myClass','width'=>'1fr'],
                ['label'=>'URL',        'field'=>'url',         'type'=>'text',     'sortable'=>true,  'width'=>'35%'],
                ['label'=>'Domain',     'field'=>'domain',      'type'=>'text',     'sortable'=>true,  'width'=>'20%'],
                ['label'=>'Medium',     'field'=>'medium',      'type'=>'text',     'sortable'=>true,  'width'=>'15%'],
                ['label'=>'Hits',       'field'=>'hits',        'type'=>'number',   'sortable'=>true,  'globalSearchDisabled'=>true,    'width'=>'5%'   ],
                ['label'=>'Popularity', 'field'=>'hitspercent', 'type'=>'number',   'sortable'=>true,  'globalSearchDisabled'=>true,    'width'=>'15%'  ],
                ['label'=>'First seen', 'field'=>'timefrom',    'type'=>'date',     'sortable'=>true,  'globalSearchDisabled'=>true,    'width'=>'15%', 'dateInputFormat'=>'yyyy-MM-dd', 'dateOutputFormat'=>'MMM yyyy'], // todo: Date display should be customized to custom timespans

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
                    'hitspercent'   => round(($referer->hits/$max),2),
                    'timefrom'      => getDateFromPeriod(intval($referer->timefrom,10), SIMPLESTATS_TABLE_DATE_FORMAT),
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

        // SYNC inline (todo: allow syncing in multiple ways [callback, crontab, inline..] )
        self::syncDayStats(); // tmp

        //$db = selfdatabase();
        $langQuery = '';
        if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ){
            foreach( kirby()->languages() as $l ){
                $langQuery .= ', SUM(`hits_'.$l->code().'`) AS `hits_'.$l->code().'`';
            }
        }

        $visitedPages = self::database()->query("SELECT `uid`, MIN(`monthyear`) AS `firstvisited`, MAX(`monthyear`) AS `lastvisited`, SUM(`hits`) AS `hits` ${langQuery} FROM `pagevisits` GROUP BY `uid` ORDER BY `uid` ASC, `monthyear` DESC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);
        if($visitedPages){
            // Set column names
            $pageStatsLabels = [
                //['label'=>'UID',            'field'=>'uid',             'type'=>'text',     'sort'=>true,  'search'=>true,    'class'=>'', 'width'=>'1fr'],
                ['label'=>'URL',                                                'field'=>'url',             'type'=>'text',     'sortable'=>true,  'globalSearchDisabled'=>false,   'width'=>'0%',  'hidden'=>'true'],
                ['label'=>t('simplestats.table.uid','UID'),                     'field'=>'uid',             'type'=>'text',     'sortable'=>true,  'globalSearchDisabled'=>false,   'width'=>'35%'], // todo : add 'tooltip'
                ['label'=>'Depth',                                              'field'=>'depth',           'type'=>'number',   'sortable'=>false, 'globalSearchDisabled'=>true,    'width'=>'0%',  'hidden'=>'true' ],
                ['label'=>t('simplestats.table.pagetitle', 'Title'),            'field'=>'title',           'type'=>'text',     'sortable'=>true,  'globalSearchDisabled'=>false,   'width'=>'20%'],
                ['label'=>t('simplestats.table.hits','Hits'),                   'field'=>'hits',            'type'=>'number',   'sortable'=>true,  'globalSearchDisabled'=>true,    'width'=>'5%'],
                ['label'=>t('simplestats.table.popularity','Popularity'),       'field'=>'hitspercent',     'type'=>'percentage','sortable'=>true, 'globalSearchDisabled'=>true,    'width'=>'10%', 'align'=>'left'],
                ['label'=>t('simplestats.table.firstvisited','First Visited'),  'field'=>'firstvisited',    'type'=>'date',     'sortable'=>true,  'globalSearchDisabled'=>false,   'width'=>'10%', 'dateInputFormat'=>'yyyy-MM-dd', 'dateOutputFormat'=>getPanelPeriodFormat()], // todo: Date display should be customized to custom timespans
                ['label'=>t('simplestats.table.lastvisited','Last Visited'),    'field'=>'lastvisited',     'type'=>'date',     'sortable'=>true,  'globalSearchDisabled'=>false,   'width'=>'10%', 'dateInputFormat'=>'yyyy-MM-dd', 'dateOutputFormat'=>getPanelPeriodFormat()],
                ['label'=>'Icon',                                               'field'=>'icon',            'type'=>'text',     'sortable'=>false, 'globalSearchDisabled'=>true,    'width'=>'0%',  'hidden'=>'true' ],
            ];

            // Add language columns
            if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ){
                foreach( kirby()->languages() as $l ){
                    $pageStatsLabels[] = ['label'=>$l->name(), 'field'=>'hits_'.$l->code(), 'type'=>'number', 'sort'=>true,   'search'=>false,   'class'=>'', 'width'=>'5%'];
                }
            }

            // Get max for calc
            $max = 0;
            foreach($visitedPages as $page){
                if( $page->hits > $max ) $max = $page->hits;
            }

            // Set rows
            foreach($visitedPages as $page){
                $kirbyPage = kirby()->page($page->uid); // This is probably the slowest part to be optimized some day

                // Pages that don't exist (anymore?)
                if(!$kirbyPage){
                    $pageStatsData[] = [
                        'url'           => $page->uid,
                        'uid'           => $page->uid,
                        'title'         => $page->uid . ' (404)',
                        'hits'          => intval($page->hits, 10),
                        'hitspercent'   => round(($page->hits/$max),2),
                        'firstvisited'  => getDateFromPeriod(intval($page->firstvisited, 10), SIMPLESTATS_TABLE_DATE_FORMAT),
                        'lastvisited'   => getDateFromPeriod(intval($page->lastvisited, 10), SIMPLESTATS_TABLE_DATE_FORMAT),
                        'depth'         => 0,
                        'icon'          => 'page',
                    ];
                    //continue;
                }

                else {
                    $pageStatsData[] = [
                        //'uid'           => $page->uid,
                        'uid'           => $page->uid,
                        'url'           => $kirbyPage->url(),
                        'title'         => $kirbyPage->title()->value(),
                        'hits'          => intval($page->hits, 10),
                        'hitspercent'   => round(($page->hits/$max)*100)*0.01,
                        'firstvisited'  => getDateFromPeriod(intval($page->firstvisited,10), SIMPLESTATS_TABLE_DATE_FORMAT),
                        'lastvisited'   => getDateFromPeriod(intval($page->lastvisited,10), SIMPLESTATS_TABLE_DATE_FORMAT),
                        'depth'         => $kirbyPage->depth()-1,
                        'icon'          => $kirbyPage->blueprint()->icon(),//'page',//$kirbyPage->panelIcon()?$kirbyPage->panelIcon()['type']:'page',
                    ];
                }

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
        $visitsOverTime = self::database()->query("SELECT `monthyear`, SUM(`hits`) AS `hits` FROM `pagevisits` GROUP BY `monthyear` ORDER BY `monthyear` ASC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);
        if($visitsOverTime){
            $firstTimeFrame = 0;
            foreach($visitsOverTime as $timeFrame){
                // Add timeframe from db
                $visitsOverTimeData[]=[ getDateFromPeriod(intval($timeFrame->monthyear,10), SIMPLESTATS_TIMELINE_DATE_FORMAT), $timeFrame->hits ];
                if($firstTimeFrame===0) $firstTimeFrame = intval($timeFrame->monthyear, 10); // remember for later
                //$visitsOverTimeLabels[]=date('M Y',$time);//"${month} - ${year}";
                //$visitsOverTimeData[]=$timeFrame->hits;
            }
            // Add missing timeframes
            if($firstTimeFrame!==0){
                $visitsOverTimePeriods = array_column($visitsOverTimeData, 0);
                for($timeFrame=getTimeFromPeriod($firstTimeFrame); $timeFrame <= time(); $timeFrame=incrementTime($timeFrame) ){
                    $timeFrameKey = date('Y-m-d', $timeFrame);
                    if( array_search($timeFrameKey, $visitsOverTimePeriods) === false ) $visitsOverTimeData[]=[$timeFrameKey, 0];
                }
            }
        }
        else Logger::LogWarning("pageStats(visitsOverTime) : db error =".self::database()->lastError()->getMessage() );

        // Get pages over time
        // Todo: Add total and remove visitsOverTimeData, see https://stackoverflow.com/a/39374290/58565
        $pageVisitsOverTimeData=[];
        $pageVisitsOverTime = self::database()->query("SELECT `uid`, SUM(`hits`) AS `hits`, `monthyear` FROM `pagevisits` GROUP BY `UID`, `monthyear` ORDER BY `monthyear` ASC, `uid` ASC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);
        if($pageVisitsOverTime){
            $pageTimeframes=[];
            foreach($pageVisitsOverTime as $page){
                $pagevisitPeriod = intval($page->monthyear, 10);
                $name = $page->uid;

                // Need to create the first entry ?
                if(!array_key_exists($name, $pageVisitsOverTimeData)){
                    $pageVisitsOverTimeData[$name]=[
                        'name' => $name,
                        'data' => [],
                    ];
                }

                // Remember period
                if(array_search($pagevisitPeriod, $pageTimeframes)===false){
                    $pageTimeframes[]=$pagevisitPeriod;
                }
                // value
                $pageVisitsOverTimeData[$name]['data'][$pagevisitPeriod]=intval($page->hits);
            }

            // Process data
            $tmp=[];
            foreach($pageVisitsOverTimeData as $name => $data){

                // Add missing keys / zero values
                foreach($pageTimeframes as $month){
                    if(!array_key_exists($month, $pageVisitsOverTimeData[$name]['data'])){
                        $pageVisitsOverTimeData[$name]['data'][$month]=0;
                    }
                }

                // Convert monthyear to date string
                $pageVisitsOverTimeData[$name]['data2']=[];
                foreach($pageVisitsOverTimeData[$name]['data'] as $my => $hits){
                    $pageVisitsOverTimeData[$name]['data2'][getDateFromPeriod($my, SIMPLESTATS_TIMELINE_DATE_FORMAT)]=$hits;
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
                // There's no need for language stats on single lange kirby installs...
                //$queryLangs .= ', SUM(`hits_en`) AS `en`';
                //$kirbyLangs[] = 'en';
            }

            // Compute $languagesOverTime and $globalLanguagesData
            $languagesOverTimeQ = self::database()->query("SELECT `monthyear` ${queryLangs} FROM `pagevisits` GROUP BY `monthyear` ORDER BY `monthyear` ASC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);
            if($languagesOverTimeQ){
                $firstTimeFrame = 0;
                foreach($languagesOverTimeQ as $timeFrame){
                    $langsPeriod = getDateFromPeriod(intval($timeFrame->monthyear, 10), SIMPLESTATS_TIMELINE_DATE_FORMAT);
                    if($firstTimeFrame===0) $firstTimeFrame = intval($timeFrame->monthyear, 10); // remember for later

                    // Get hits for each lang on this period
                    foreach($kirbyLangs as $l){
                        // value
                        $languagesOverTimeData[$l]['data'][$langsPeriod] = intval($timeFrame->$l, 10);

                        // compute globals
                        $globalLanguagesData[$l][1] += $languagesOverTimeData[$l]['data'][$langsPeriod];
                    }
                }

                // Add missing timeframes from first date to now (happens when no data at all is recorded in a full period)
                if($firstTimeFrame !== 0) for($timeFrame=getTimeFromPeriod($firstTimeFrame); $timeFrame <= time(); $timeFrame=incrementTime($timeFrame) ){
                    $timeFrameKey = date( SIMPLESTATS_TIMELINE_DATE_FORMAT, $timeFrame);
                    foreach($kirbyLangs as $l){
                        if( array_key_exists($l, $languagesOverTimeData) && array_key_exists('data', $languagesOverTimeData[$l]) && array_key_exists($timeFrameKey, $languagesOverTimeData[$l]['data']) === false ){
                            $languagesOverTimeData[$l]['data'][$timeFrameKey]=0;
                        }
                    }
                    //var_dump($timeFrameKey);
                }


                // Remove empty rows in $languagesOverTimeData (unvisited languages are removed)
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
    public static function syncDayStats(int $time=null): bool {

        // Prevent syncing in some circonstances ?
        // Localhost protection #23 (not sure if needed here...)
//         if( true === option('daandelange.simplestats.tracking.ignore.localhost' , false) && in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) ){
//             return true;
//         }

        // init db
        //$db = self::database();

        // init return variabes
        //$sitePages = [];
        $newPageVisits = []; // --> $newPageVisits[period][pageid][attr]
        $newDevices = [];
        $newEngines = [];
        $newSystems = [];

        if(!$time) $time = time();

        // Get visitors older then 1 day
        $yesterday = $time - option('daandelange.simplestats.tracking.uniqueSeconds', 24*60*60);
        $visitors = self::database()->query("SELECT `userunique`, `visitedpages`, `osfamily`, `devicetype`, `browserengine`, `timeregistered` FROM `pagevisitors` WHERE `timeregistered` < ${yesterday} ORDER BY `timeregistered` ASC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);

        if($visitors){
            //echo 'RESULT='."SELECT `userunique`, `visitedpages`, `osfamily`, `devicetype`, `browserengine`, `timeregistered` FROM `pagevisitors` WHERE `timeregistered` < ${yesterday} ORDER BY `timeregistered` ASC LIMIT 0,1000;";
            //var_dump($visitors->toArray());

            // process each one
            foreach($visitors as $visitor){
                //var_dump($visitor);
                $sincePeriod = getPeriodFromTime(intval($visitor->timeregistered,10));

                // Compute visited pages
                if( $visitor->visitedpages && !empty( $visitor->visitedpages ) ){
                    // Create keys
                    if( !array_key_exists($sincePeriod, $newPageVisits) ) $newPageVisits[$sincePeriod]=[];

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
                        $key = array_search($page, array_column($newPageVisits[$sincePeriod], 'uid') );
                        if( $key === false ){
                            //echo 'Created $newPageVisits['.$sincePeriod.'][] --- as '.$page."\n";
                            $newPageVisits[$sincePeriod][]=[
                                'hits' => 1,//$pageInfo['hits'],
                                'uid'  => $page,
                                'timeframe' => $sincePeriod,
                                'langhits' => $pageInfo['langs'],
                            ];
                        }
                        // Increment existing page ?
                        else {
                            //echo 'Incrementing $newPageVisits['.$sincePeriod.']['.$key.'] '."\n";

                            // increment total hits
                            $newPageVisits[$sincePeriod][$key]['hits']++;

                            // Append lang visits
                            foreach( $newPageVisits[$sincePeriod][$key]['langhits'] as $lang => $hits ){
                                if( $hits > 0 ) $newPageVisits[$sincePeriod][$key]['langhits'][$lang]++;
                            }
                        }
                    }
                }

                // Compute Devices
                if( $visitor->devicetype && !empty( $visitor->devicetype ) ){
                    if(!array_key_exists($sincePeriod, $newDevices)) $newDevices[$sincePeriod] = [];

                    // Insert device ?
                    $key = array_search($visitor->devicetype, array_column($newDevices[$sincePeriod], 'device') );
                    if( $key === false ){
                        $newDevices[$sincePeriod][]=[
                            'hits' => 1,
                            'device'  => $visitor->devicetype,
                            'timeframe' => $sincePeriod,
                        ];
                    }
                    // Increment ?
                    else {
                        $newDevices[$sincePeriod][$key]['hits']++;
                    }
                }

                // Compute Systems
                if( $visitor->osfamily && !empty( $visitor->osfamily ) ){
                    if(!array_key_exists($sincePeriod, $newSystems)) $newSystems[$sincePeriod] = [];

                    // Insert system ?
                    $key = array_search($visitor->osfamily, array_column($newSystems[$sincePeriod], 'system') );
                    if( $key === false ){
                        $newSystems[$sincePeriod][]=[
                            'hits' => 1,
                            'system'  => $visitor->osfamily,
                            'timeframe' => $sincePeriod,
                        ];
                    }
                    // Increment ?
                    else {
                        $newSystems[$sincePeriod][$key]['hits']++;
                    }
                }

                // Compute Engines
                if( $visitor->browserengine && !empty( $visitor->browserengine ) ){
                    if(!array_key_exists($sincePeriod, $newEngines)) $newEngines[$sincePeriod] = [];

                    // Insert system ?
                    $key = array_search($visitor->browserengine, array_column($newEngines[$sincePeriod], 'engine') );
                    if( $key === false ){
                        $newEngines[$sincePeriod][]=[
                            'hits' => 1,
                            'engine'  => $visitor->browserengine,
                            'timeframe' => $sincePeriod,
                        ];
                    }
                    // Increment ?
                    else {
                        $newEngines[$sincePeriod][$key]['hits']++;
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
                foreach( $newPageVisits as $pagePeriod => $monthlyPageVisits ){

                    //echo 'Updating page visits for '.$pagePeriod."\n"; continue;
                    $existingPages = self::database()->query("SELECT `id`, `uid`, `hits`, ${queryLangs} FROM `pagevisits` WHERE `monthyear` = ${pagePeriod} LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);

                    // Dirty security for if languages make the request fail
/*
                    if( !$existingPages ){
                        // retry query without the language strings
                        $existingPages = self::database()->query("SELECT `id`, `uid`, `hits` FROM `pagevisits` WHERE `monthyear` = ${pagePeriod} LIMIT 0,1000;");
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
                                if(!self::database()->query("INSERT INTO `pagevisits` (`uid`, `hits`, `monthyear` ${langKeys} ) VALUES ('${uid}', ${newHits}, ${pagePeriod} ${langValues})")){
                                    Logger::LogWarning("Could not INSERT pagevisits while syncing. Error=".self::database()->lastError()->getMessage());
                                }
                            }
                            // Update existing entry
                            elseif($newHits>0) {
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

                // Loop Periods
                foreach( $newDevices as $devicesPeriod => $monthlyDevices ){
                    // Query existing db
                    $existingDevices = self::database()->query("SELECT `id`, `device`, `hits` FROM `devices` WHERE `monthyear` = '${devicesPeriod}' LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);

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
                                if(!self::database()->query("INSERT INTO `devices` (`device`, `hits`, `monthyear`) VALUES ('".$newDeviceInfo['device']."', ${newHits}, ${devicesPeriod})")){
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

                // Loop Periods
                foreach( $newSystems as $systemsPeriod => $monthlySystems ){
                    // Query existing db
                    $existingSystems = self::database()->query("SELECT `id`, `system`, `hits` FROM `systems` WHERE `monthyear` = '${systemsPeriod}' LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);

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
                                if(!self::database()->query("INSERT INTO `systems` (`system`, `hits`, `monthyear`) VALUES ('".$newSystemInfo['system']."', ${newHits}, ${systemsPeriod})")){
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

                // Loop Periods
                foreach( $newEngines as $enginesPeriod => $monthlyEngines ){
                    // Query existing db
                    $existingEngines = self::database()->query("SELECT `id`, `engine`, `hits` FROM `engines` WHERE `monthyear` = '${enginesPeriod}' LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);

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
                                if(!self::database()->query("INSERT INTO `engines` (`engine`, `hits`, `monthyear`) VALUES ('".$newEngineInfo['engine']."', ${newHits}, ${enginesPeriod})")){
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

    // Get stats details for one page object
    public static function onePageStats($page){
        // Get ID from $page
        if($page && $page instanceof \Kirby\Cms\Page) $page = $page->exists()?$page->uid():'';

        // Ensure we got a string
        if( !is_string($page) || empty($page)){
            throw new Exception("Only accepting non-empty string or an existing \Kirby\Cms\Page !");
        }

        // Return object
        $ret = [
            'title'             => '[unknown] (404)',
            'uid'               => $page,
            'languagesOverTime' => [],
            'visitsOverTime'    => [],
            //'totalStats'        => [
            'totalHits'         => false,
            'averageHits'       => false,
            'languageTotalHits' => [],
            'lastPeriodHits'    => false,
            'firstVisited'      => false, // (aka: tracking since)
            'lastVisited'       => false,
            'trackingPeriods'   => 0,
            //],
            //'languagesAreEnabled'=>false,
        ];

        // Fetch kirbyPage object to get some more information
        $kirbyPage = kirby()->page($page); // This is probably the slowest part, to be optimized some day. todo
        if($kirbyPage && $kirbyPage->exists()) $ret['title'] = $kirbyPage->title()->value();
        else $ret['title'] = $page.' (404)'; // Use id for tracked pages without ID (virtual pages...)

        // init languages stuff
        $kirbyLangs = [];
        if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ){
            //$ret['languagesAreEnabled'] = true;
            foreach( kirby()->languages() as $l ){
                $kirbyLangs[$l->code()] = $l->name();
            }
        }

        // Query page visits over time, with languages
        {
            // Init data
            $ret['totalHits'] = 0;
            $ret['lastPeriodHits']= 0;
                        $ret['visitsOverTime'][0]=[
                'name' => 'Total Visits',
                'data' => [], // holds pairs of [date,value]
            ];
            $ret['firstVisited'] = 0;
            $ret['lastVisited'] = 0;

            // Prepare language-dependent data
            //if(count($kirbyLangs)>0){}
            $langQuery = '';
            foreach($kirbyLangs as $l => $name){
                // For SQL query
                $langQuery .= ', `hits_'.$l.'` AS `'.$l.'`';

                // Create keys for each language
                $ret['languagesOverTime'][$l]=[
                    'name' => $name,
                    'data' => [], // holds pairs of [date,value]
                ];
                $ret['languageTotalHits'][$l]=[ // holds pairs of [label,value]
                    $name, 0
                ];
            }

            $pageVisitsOverTime = self::database()->query("SELECT `uid`, `monthyear`, `hits` ${langQuery} FROM `pagevisits` WHERE `uid` = '".$page."' ORDER BY `monthyear` DESC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT);
            //echo "SELECT `uid`, `monthyear`, `hits` ${langQuery} FROM `pagevisits` WHERE `uid` = '".$page."' ORDER BY `monthyear` DESC LIMIT 0,".SIMPLESTATS_DUMMY_DB_LIMIT;
            if($pageVisitsOverTime){
                // Loop periods
                $nowPeriod = getPeriodFromTime();
                //$ret['languagesOverTime'][$l]=['name'=>'',data=>[]];
                foreach($pageVisitsOverTime as $period){
                    //echo $period->monthyear.'='.$period->hits.' - '; continue;
                    // Compute total hits
                    $ret['totalHits'] += $period->hits;

                    // Remember Today period visits ?
                    if( $period->monthyear == $nowPeriod ){
                        $ret['lastPeriodHits'] = $period->hits;
                    }

                    // Get time and date str
                    $periodTime = getTimeFromPeriod(intval($period->monthyear, 10));
                    $periodDateStr = date(SIMPLESTATS_TIMELINE_DATE_FORMAT, $periodTime);

                    // Remember first and last dates
                    if($ret['firstVisited'] > $periodTime || $ret['firstVisited']==0) {
                        $ret['firstVisited'] = $periodTime;
                    }
                    if($ret['lastVisited'] < $periodTime || $ret['lastVisited']==0) {
                        $ret['lastVisited'] = $periodTime;
                    }

                    // Add period to visitsOverTime
                    $ret['visitsOverTime'][0]['data'][]=[$periodDateStr, $period->hits];

                    // Inject total per language
                    foreach( $kirbyLangs as $l => $n ){
                        $ret['languagesOverTime'][$l]['data'][]=[$periodDateStr, $period->$l];
                        $ret['languageTotalHits'][$l][1]+=$period->$l;
                    }
                }
            }

            // Rename keys to nums so that the charts accept the data
            foreach( $ret['languagesOverTime'] as $key => $data) {
                $ret['languagesOverTime'][]=$data;
                unset($ret['languagesOverTime'][$key]);
            };
            foreach( $ret['languageTotalHits'] as $key => $data) {
                $ret['languageTotalHits'][]=$data;
                unset($ret['languageTotalHits'][$key]);
            };

            // Compute averages
            $ret['trackingPeriods'] = getNumPeriodsFromDates($ret['firstVisited'], time());
            if($ret['trackingPeriods']>0) $ret['averageHits'] = $ret['totalHits'] / $ret['trackingPeriods'];
            $ret['timespanUnitName'] = getTimeFrameUtility()->getPeriodName(false);

            // Format dates
            if($ret['lastVisited']>0) $ret['lastVisited'] = date(SIMPLESTATS_TIMELINE_DATE_FORMAT, $ret['lastVisited']);
            if($ret['firstVisited']>0) $ret['firstVisited'] = date(SIMPLESTATS_TIMELINE_DATE_FORMAT, $ret['firstVisited']);

        }
        //var_dump($ret); exit;
        return $ret;

        //return false;
    }
}
