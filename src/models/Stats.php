<?php
declare(strict_types=1); // Dont mix types

namespace daandelange\SimpleStats;

use Kirby\Database\Database;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Obj;
use Kirby\Toolkit\Str;
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
        $dbvQ = self::database()->query('SELECT `version`, `migrationdate` FROM `simplestats` ORDER BY `migrationdate` DESC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
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
            if( stripos($error, 'no such table: simplestats') !== false ){
                $dbVersion = '1 ('.t('simplestats.info.db.versionless','versionless').')';
            }
            else {
                $dbVersion = t('simplestats.info.db.versionerror', 'Unable to query...');//.$error;
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

        // Todo: Read some stats from the db such as timespan, etc.
        return [
            'softwareDbVersion' => self::engineDbVersion,
            'dbVersion'         => $dbVersion,
            'dbHistoryLabels'   => [
                'version'  => ['label'=>t('simplestats.info.db.table.dbversion'), 'type'=>'number',   'sortable'=>true, 'search'=>false,  'width'=>'34%'],
                'date'     => ['label'=>t('simplestats.info.db.table.usedsince'), 'type'=>'date',     'sortable'=>true, 'search'=>false,  'width'=>'66%'],
            ],
            'dbHistory'         => $dbArray,
            'upgradeRequired'   => self::engineDbVersion != $dbVersion,
            'databaseLocation'  => $dbFile ?? '[undefined]',
            'databaseSize'      => $dbSize,
        ];
    }

    // Returns the timespan of all mixed tables in the db
    public static function getDbTimeSpan(string $returnFormat = null, string $pageUid = null) : array | null {
        $monthyearTables = $pageUid ? ['pagevisits']:['pagevisits','systems', 'engines', 'referers', 'devices'];
        $queryStr = 'SELECT MIN(`monthyear`) as `start`, MAX(`monthyear`) as `end` FROM ( ';
        foreach($monthyearTables as $i => $my) {
            $queryStr.= 'SELECT `monthyear` FROM `'.$my.'` '.(($pageUid)?' WHERE `uid` = "'.$pageUid.'"':'').(($i===(count($monthyearTables)-1))?') ':'UNION ALL ');
        }
        $queryStr .= ' LIMIT 0, '.SIMPLESTATS_DUMMY_DB_LIMIT;
        $result = self::database()->query($queryStr);
        
        if($result && $result->get(0, false)){
            $data = $result->get(0)->toArray();
            if(array_key_exists('start', $data) && array_key_exists('end', $data)) {
                // No results ? (empty db) --> Use today
                if(!$data['start'] || !$data['end']){
                    $data['start'] = $data['end'] = getPeriodFromTime();
                    return $data;
                }

                // Parse to int and date
                foreach($data as &$d){
                    $d = intval($d, 10);
                    if($returnFormat){
                        $d = date($returnFormat, getTimeFromPeriod($d));
                    }
                }
                return $data;
            }
        }
        return null;
    }

    public static function getTimeSpanFromUrl(string $urlParamFrom = 'dateFrom', string $urlParamTo = 'dateTo') : array {
        $request = kirby()->request();
        $timeFrame = [
            $request->get('dateFrom', null),
            $request->get('dateTo', null),
        ];
        // Parse Y-d-m to period
        foreach([0,1] as $i){
            if($timeFrame[$i] && strlen($timeFrame[$i])>0){
                $split = explode('-', trim($timeFrame[$i]));
                if($split && count($split)===3){
                    foreach($split as &$s) $s = intval($s, 10);
                    $timeFrame[$i] = getPeriodFromTime( mktime(0,0,0, $split[1], $split[2], $split[0]) );
                }
            }
        }
        return $timeFrame;
    }

    // Returns all periods between them
    public static function fillPeriod(int $startPeriod, int $endPeriod, string $dateFormat=null) : array {
        $periods = [];
        for($period=$startPeriod; $period <= $endPeriod; $period=incrementPeriod($period) ){
            if(!$dateFormat){
                $periods[] = $period;
            }
            else {
                $periods[] = date($dateFormat, getTimeFromPeriod($period));
            }
        }
        return $periods;
    }

    public static function listvisitors(): array {
        $keys = [
            'visitedpages'   => [ 'label' => t('simplestats.table.visitedpages',    'Visited Pages'),   'type' => 'text', 'sortable' => false,  'width' => '50%', 'search'=>true ],
            'osfamily'       => [ 'label' => t('simplestats.table.osfamily',        'OS Family'),       'type' => 'text', 'sortable' => true,   'width' => '15%', 'search'=>true ],
            'devicetype'     => [ 'label' => t('simplestats.table.devicetype',      'Device Type'),     'type' => 'text', 'sortable' => true,   'width' => '10%', 'search'=>true ],
            'browserengine'  => [ 'label' => t('simplestats.table.browserengine',   'Browser Engine'),  'type' => 'text', 'sortable' => true,   'width' => '10%', 'search'=>true ],
            'timeregistered' => [ 'label' => t('simplestats.table.timeregistered',  'Time Registered'), 'type' => 'date', 'sortable' => true,   'width' => '15%', 'search'=>false],//, 'dateInputFormat'=>'yyyy-MM-dd HH:mm', 'dateOutputFormat'=>'d MMMM yyyy HH:mm'],
        ];
        $rows = [];
        $result = self::database()->query('SELECT `visitedpages`, `osfamily`, `devicetype`, `browserengine`, `timeregistered` FROM `pagevisitors` LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($result){
            // Format rows
            $rows = $result->toArray();
            foreach($rows as $key => $value){
                $rows[$key] = array_merge(['id'=>$key, 'text'=>'text', 'title'=>'title', 'dragText'=>'dragText', 'info'=>'info!!'], $value->toArray());

                // convert date format
                $rows[$key]['timeregistered'] = date( SIMPLESTATS_PRECISE_DATE_FORMAT, intval($rows[$key]['timeregistered']) );
            }
        }
        return [
            'columns'   => $keys,
            'rows'      => $rows,
        ];
    }

    public static function deviceStats(int $fromPeriod = null, int $toPeriod = null) {
        
        //self::syncDayStats();

        // Format period
        $timeSpan = static::constrainPeriodsToDbSpan($fromPeriod, $toPeriod);

        // Array holding all possible periods
        $selectedPeriods = array_combine(
            static::fillPeriod( $timeSpan['start'], $timeSpan['end'] ),
            static::fillPeriod( $timeSpan['start'], $timeSpan['end'], 'Y-m-d' ),
        );

        // Global Where query part
        $whereQuery = ' WHERE `monthyear` BETWEEN '.$timeSpan['start'].' AND '.$timeSpan['end'];

        // Get devices
        $hideBotsQueryPart = '';
        if( true === option('daandelange.simplestats.panel.hideBots', false) ) 
            $hideBotsQueryPart = ' AND `device` != "server"';
        $allDevices = [
            'label' => 'All Device Types',
            'data' => [],
        ];
        $allDevicesLabels = [];
        $allDevicesResult = self::database()->query('SELECT `device`, SUM(`hits`) AS `hits` FROM `devices`'.$whereQuery.$hideBotsQueryPart.' GROUP BY `device` ORDER BY `device` DESC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($allDevicesResult){
            // parse sql result, line by line
            foreach($allDevicesResult as $device){
                $key = $device->device;
                if(!array_key_exists($key, $allDevices['data'])){
                    $allDevices['data'][$key] = intval($device->hits, 10);
                }
                $allDevicesLabels[$key] = static::translateDeviceType($key);
            }
            // Remove keys
            $allDevices['data'] = array_values($allDevices['data']);
        }

        // Get Systems
        $allSystems = [
            'label' => 'All Operating Systems',
            'data' => [],
        ];
        $allSystemsLabels = [];
        if( true === option('daandelange.simplestats.panel.hideBots', false) )
            $hideBotsQueryPart = ' AND `system` != "bot"';
        $allSystemsResult = self::database()->query('SELECT `system`, SUM(`hits`) AS `hits` FROM `systems`'.$whereQuery.$hideBotsQueryPart.' GROUP BY `system` ORDER BY `system` DESC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($allSystemsResult){
            // parse sql result, line by line
            foreach($allSystemsResult as $system){
                $key = $system->system;
                if(!array_key_exists($key, $allSystems['data'])){
                    $allSystems['data'][$key] = intval($system->hits, 10);
                }
                $allSystemsLabels[$key] = $key;
            }
            // Remove keys
            $allSystems['data'] = array_values($allSystems['data']);
        }

        // Get Engines
        $allEngines = [
            'label' => 'All Browser Engines',
            'data' => [],
        ];
        $allEnginesLabels = [];
        if( true === option('daandelange.simplestats.panel.hideBots', false) )
            $hideBotsQueryPart = ' AND `engine` != "bot"';
        $allEnginesResult = self::database()->query('SELECT `engine`, SUM(`hits`) AS `hits` FROM `engines`'.$whereQuery.$hideBotsQueryPart.' GROUP BY `engine` ORDER BY `engine` DESC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($allEnginesResult){
            // parse sql result, line by line
            foreach($allEnginesResult as $engine){
                $key = $engine->engine;
                if(!array_key_exists($key, $allEngines['data'])){
                    $allEngines['data'][$key] = intval($engine->hits, 10);
                }
                $allEnginesLabels[$key] = $key;
            }
            // Remove keys
            $allEngines['data'] = array_values($allEngines['data']);
        }

        // Get Devices over time
        $devicesOverTimeData=[];
        $chartPeriodLabels = array_values($selectedPeriods);
        if( true === option('daandelange.simplestats.panel.hideBots', false) )
            $hideBotsQueryPart = ' AND `device` != "server"';
        $devicesOverTime = self::database()->query('SELECT `device`, SUM(`hits`) AS `hits`, `monthyear` FROM `devices`'.$whereQuery.$hideBotsQueryPart.' GROUP BY `device`, `monthyear` ORDER BY `monthyear` ASC, `device` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($devicesOverTime){
            $devicePeriods=[];
            foreach($devicesOverTime as $device){
                $devicePeriod = intval($device->monthyear, 10);
                $name = $device->device;

                // Need to create the first entry ?
                if(!array_key_exists($name, $devicesOverTimeData)){
                    $devicesOverTimeData[$name]=[
                        'label' => self::translateDeviceType($name),
                        'data' => array_fill_keys(array_keys($selectedPeriods), 0),
                    ];
                }
                
                // Increment values
                if(array_key_exists($devicePeriod, $devicesOverTimeData[$name]['data'])) $devicesOverTimeData[$name]['data'][$devicePeriod] += intval($device->hits);
            }

            // Unset keys for JS
            foreach($devicesOverTimeData as &$dataset){
                $dataset['data']=array_values($dataset['data']);
            }
        }

        return [
            'deviceslabels'     => array_values($allDevicesLabels),
            'devicesdata'       => [$allDevices],
            'systemslabels'     => array_values($allSystemsLabels),
            'systemsdata'       => [$allSystems],
            'engineslabels'     => array_values($allEnginesLabels),
            'enginesdata'       => [$allEngines],
            'devicesovertime'   => array_values($devicesOverTimeData),
            'chartperiodlabels' => $chartPeriodLabels,
        ];
    }

    public static function translateNamespaced( string $namespace, string $key ) : string {
        if($translation = t($namespace.'.'.$key)){
            return $translation;
        }
        return $key;
    }
    public static function translateDeviceType( string $key ) : string {
        return static::translateNamespaced('simplestats.devices.names', $key);
    }
    public static function translateMedium( string $key ) : string {
        return static::translateNamespaced('simplestats.referers.mediums', $key);
    }

    public static function refererStats(int $fromPeriod = null, int $toPeriod = null): ?array {
        
        // Format period
        $timeSpan = static::constrainPeriodsToDbSpan($fromPeriod, $toPeriod);

         // Array holding all possible periods
        $selectedPeriods = array_combine(
            static::fillPeriod( $timeSpan['start'], $timeSpan['end'] ),
            static::fillPeriod( $timeSpan['start'], $timeSpan['end'], 'Y-m-d' ),
        );

        // Global Where query part
        $whereQuery = ' WHERE `monthyear` BETWEEN '.$timeSpan['start'].' AND '.$timeSpan['end'].'';

        $chartPeriodLabels = array_values($selectedPeriods);

        // Parse referers by domain
        $referersByDomainData = [
            'label' => 'Global Referrer Domains',
            'data' => [],
        ];
        // $referersByDomainData = [];
        $referersByDomainLabels=[];
        $globalStats = self::database()->query('SELECT `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, MIN(`monthyear`) AS `firstseen`, MAX(`monthyear`) AS `lastseen`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers`'.$whereQuery.' )'.$whereQuery.' GROUP BY `domain` ORDER BY `domain` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($globalStats){
            foreach($globalStats as $referer){
                $key = Str::slug($referer->domain);//intval($referer->monthyear,10);
                if(!array_key_exists($key, $referersByDomainData['data'])){
                    $referersByDomainData['data'][$key] = intval($referer->hits, 10);
                }
                $referersByDomainLabels[$key] = $referer->domain;
            }
            // Remove keys
            $referersByDomainData['data'] = array_values($referersByDomainData['data']);
        }
        else {
            Logger::LogWarning("refererStats(globalStats) : db error =".self::database()->lastError()->getMessage() );
        }
        

        // Grab referrers by medium
        $referersByMediumLabels = [];
        $referersByMediumData=[
            'label' => 'Global Referrer Mediums',
            'data' => [],
        ];
        // Todo: this query is almost the same as above, do we need to run it twice ?
        $mediumStats = self::database()->query('SELECT `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, MIN(`monthyear`) AS `firstseen`, MAX(`monthyear`) AS `lastseen`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers` '.$whereQuery.') '.$whereQuery.' GROUP BY `medium` ORDER BY `medium` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($mediumStats){
            foreach($mediumStats as $medium){
                $key = Str::slug($medium->domain);
                if(!array_key_exists($key, $referersByMediumData['data'])){
                    $referersByMediumData['data'][$key] = intval($medium->hits,10);
                }
                $referersByMediumLabels[$key] = static::translateMedium($medium->medium);
            }
            // Remove keys
            $referersByMediumData['data'] = array_values($referersByMediumData['data']);
        }
        else {
            Logger::LogWarning("refererStats(mediumStats) : db error =".self::database()->lastError()->getMessage() );
            //echo 'DBERROR=';var_dump(self::database()->lastError()->getMessage() );
        }
        // dump($referersByMediumData);

        // Mediums over time
        $referersByMediumOverTimeData=[];
        //dump('SELECT  `domain`, `medium`, SUM(`hits`) AS `hits`, `monthyear` FROM `referers`'.$whereQuery.' GROUP BY `medium`, `monthyear` ORDER BY `monthyear` ASC, `medium` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);die();
        $mediumStatsOverTime = self::database()->query('SELECT  `domain`, `medium`, SUM(`hits`) AS `hits`, `monthyear` FROM `referers`'.$whereQuery.' GROUP BY `medium`, `monthyear` ORDER BY `medium` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($mediumStatsOverTime){

            foreach($mediumStatsOverTime as $medium){
                $mediumPeriod = intval($medium->monthyear, 10);
                $key = Str::slug($medium->medium);
                // Need to create the first entry ?
                if(!array_key_exists($key, $referersByMediumOverTimeData)){
                    $referersByMediumOverTimeData[$key]=[
                        'label' => static::translateMedium($medium->medium),
                        'data' => array_fill_keys(array_keys($selectedPeriods), 0),
                    ];
                }
                
                // Increment values
                if(array_key_exists($mediumPeriod, $referersByMediumOverTimeData[$key]['data'])){
                    $referersByMediumOverTimeData[$key]['data'][$mediumPeriod] += intval($medium->hits);
                }
            }

            // Unset keys for JS
            foreach($referersByMediumOverTimeData as &$dataset){
                $dataset['data']=array_values($dataset['data']);
            }
        }
        else {
            Logger::LogWarning("refererStats(mediumStatsOverTime) : db error =".self::database()->lastError()->getMessage() );
        }

        // Recent stats
        //$referersByDomainRecentData=[];
        // $todayPeriod = getPeriodFromTime();
        // $domainRecentStats = self::database()->query('SELECT `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, `monthyear`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers` WHERE `monthyear`='.$todayPeriod.' ) WHERE `monthyear` = '.$todayPeriod.' GROUP BY `domain` ORDER BY `medium` ASC, `domain` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        // if($domainRecentStats){
        //     foreach($domainRecentStats as $referer){
        //         $referersByDomainRecentData[]   = [$referer->domain, $referer->hits];
        //     }
        // }
        // else{
        //     Logger::LogWarning("refererStats(domainRecentStats) : db error =".self::database()->lastError()->getMessage() );
        // }

        // Set column names
        $referersTableLabels = [
            'url'         => [ 'label'=>t('simplestats.table.url',          'URL'       ), 'type'=>'text',       'sortable'=>true,  'search'=>true,  'width'=>'30%' ],
            'domain'      => [ 'label'=>t('simplestats.table.domain',       'Domain'    ), 'type'=>'text',       'sortable'=>true,  'search'=>true,  'width'=>'20%' ],
            'medium'      => [ 'label'=>t('simplestats.table.medium',       'Medium'    ), 'type'=>'text',       'sortable'=>true,  'search'=>true,  'width'=>'10%' ],
            'hits'        => [ 'label'=>t('simplestats.table.hits',         'Hits'      ), 'type'=>'number',     'sortable'=>true,  'search'=>false, 'width'=>'10%' ],
            'hitspercent' => [ 'label'=>t('simplestats.table.popularity',   'Popularity'), 'type'=>'percentage', 'sortable'=>true,  'search'=>false, 'width'=>'15%' ],
            'timefrom'    => [ 'label'=>t('simplestats.table.firstseen',    'First seen'), 'type'=>'date',       'sortable'=>true,  'search'=>false, 'width'=>'15%' ], //, 'dateInputFormat'=>'yyyy-MM-dd', 'dateOutputFormat'=>'MMM yyyy'], // todo: Date display should be customized to custom timespans
        ];
        $referersTableData = [];
        $AllDomainStats = self::database()->query('SELECT `id`, `referer`, `domain`, `medium`, SUM(`hits`) AS `hits`, MIN(`monthyear`) AS `timefrom`, `totalHits` FROM `referers` JOIN ( SELECT SUM(`hits`) AS `totalHits` FROM `referers` '.$whereQuery.' ) '.$whereQuery.' GROUP BY `domain` ORDER BY `medium` ASC, `domain` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($AllDomainStats){

            // Get max for calc
            $allHits = $AllDomainStats->pluck('hits');
            $max = count($allHits)>0?max($allHits):0;

            // Set rows
            foreach($AllDomainStats as $referer){
                $referersTableData[] = [
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
            'referersbydomaindata'          => [$referersByDomainData],
            'referersbydomainlabels'        => array_values($referersByDomainLabels),

            'referersbymediumdata'          => [$referersByMediumData],
            'referersbymediumlabels'        => array_values($referersByMediumLabels),

            'referersbymediumovertimedata'  => array_values($referersByMediumOverTimeData),
            'chartperiodlabels'             => $chartPeriodLabels,

            // 'referersbydomainrecentdata'    => $referersByDomainRecentData,

            'refererstabledata'             => $referersTableData,
            'refererstablelabels'           => $referersTableLabels,
        ];
    }

    // Constrains 2 dates to the available dates in the db, if needed for a specific page.
    public static function constrainPeriodsToDbSpan(int $fromPeriod = null, int $toPeriod = null, string $pageUid=null) : array | null {
        $maxTimespan = $timeSpan = static::getDbTimeSpan(null, $pageUid);
        if(!$maxTimespan){
            return null;
        }
        $timeSpan = $maxTimespan;
        if($fromPeriod && $fromPeriod > $maxTimespan['start']){
            $timeSpan['start'] = $fromPeriod;
        }
        else {
            $timeSpan['start'] = $maxTimespan['start'];
        }
        if($toPeriod && $toPeriod < $maxTimespan['end']){
            $timeSpan['end'] = $toPeriod;
        }
        else {
            $timeSpan['end'] = $maxTimespan['end'];
        }
        return $timeSpan;
    }

    public static function pageStats(int $fromPeriod = null, int $toPeriod = null): ?array {
        
        // Format period
        $timeSpan = static::constrainPeriodsToDbSpan($fromPeriod, $toPeriod);

        // Array holding all possible periods
        $selectedPeriods = array_combine(
            static::fillPeriod( $timeSpan['start'], $timeSpan['end'] ),
            static::fillPeriod( $timeSpan['start'], $timeSpan['end'], 'Y-m-d' ),
        );

        // Global Where query part
        $whereQuery = ' WHERE `monthyear` BETWEEN '.$timeSpan['start'].' AND '.$timeSpan['end'].'';
        
        // per-x-axis label of each entry
        $chartPeriodLabels = array_values($selectedPeriods);

        // Start with all period keys holding 0
        $visitsOverTimeData    = array_fill_keys(array_keys($selectedPeriods), 0);
        $pageVisitsOverTimeData = [];

        // SYNC inline (todo: allow syncing in multiple ways [callback, crontab, inline..] )
        self::syncDayStats(); // tmp

        //$db = selfdatabase();
        $langQuery = '';
        if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ){
            foreach( kirby()->languages() as $l ){
                $langQuery .= ', SUM(`hits_'.$l->code().'`) AS `hits_'.$l->code().'`';
            }
        }

        // Data for the table
        $pageStatsData = [];
        $pageStatsLabels = [
            //['label'=>'UID',            'field'=>'uid',             'type'=>'text',     'sort'=>true,  'search'=>true,    'class'=>'', 'width'=>'1fr'],
            'flag'          => ['label'=>' ',                                                  'type'=>'flag',      'sortable'=>false, 'search'=>false,  'mobile' => false,  'width'=>'var(--table-row-height)'  ],
            'icon'          => ['label'=>' ',                                                  'type'=>'image',     'sortable'=>false, 'search'=>false,  'mobile' => false,  'width'=>'var(--table-row-height)' ],
            'url'           => ['label'=>'URL',                                                'type'=>'text',      'sortable'=>true,  'search'=>true,   'mobile' => false,  'width'=>'0%',  'hidden'=>'false'],
            'uid'           => ['label'=>t('simplestats.table.uid','UID'),                     'type'=>'slug',      'sortable'=>true,  'search'=>true,   'mobile' => true,   'width'=>'25%', 'hidden'=>'true'], // todo : add 'tooltip'
            'depth'         => ['label'=>'Depth',                                              'type'=>'number',    'sortable'=>false, 'search'=>false,  'mobile' => false,  'width'=>'0%',  'hidden'=>'true' ],
            'title'         => ['label'=>t('simplestats.table.pagetitle', 'Title'),            'type'=>'url',       'sortable'=>true,  'search'=>true,   'mobile' => true,   'width'=>'20%'],
            'average'       => ['label'=>t('simplestats.table.average','Average'),             'type'=>'number',    'sortable'=>true,  'search'=>false,  'mobile' => true,   'width'=>'5%'],
            'hits'          => ['label'=>t('simplestats.table.hits','Hits'),                   'type'=>'number',    'sortable'=>true,  'search'=>false,  'mobile' => true,   'width'=>'5%'],
            'hitspercent'   => ['label'=>t('simplestats.table.popularity','Popularity'),       'type'=>'percentage','sortable'=>true,  'search'=>false,  'mobile' => true,   'width'=>'10%', 'align'=>'left'],
            'firstvisited'  => ['label'=>t('simplestats.table.firstvisited','First Visited'),  'type'=>'date',      'sortable'=>true,  'search'=>false,  'mobile' => false,  'width'=>'10%', 'dateInputFormat'=>'yyyy-MM-dd', 'dateOutputFormat'=>getPanelPeriodFormat()], // todo: Date display should be customized to custom timespans
            'lastvisited'   => ['label'=>t('simplestats.table.lastvisited','Last Visited'),    'type'=>'date',      'sortable'=>true,  'search'=>false,  'mobile' => false,  'width'=>'10%', 'dateInputFormat'=>'yyyy-MM-dd', 'dateOutputFormat'=>getPanelPeriodFormat()],
            
        ];
        // Add language columns
        if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ){
            foreach( kirby()->languages() as $l ){
                $pageStatsLabels['hits_'.$l->code()] = ['label'=>$l->name(), 'field'=>'hits_'.$l->code(), 'type'=>'number', 'sortable'=>true,   'search'=>false, 'width'=>'5%'];
            }
        }

        $visitedPages = self::database()->query('SELECT `uid`, MIN(`monthyear`) AS `firstvisited`, MAX(`monthyear`) AS `lastvisited`, SUM(`hits`) AS `hits` '.$langQuery.' FROM `pagevisits`'.$whereQuery.' GROUP BY `uid` ORDER BY `uid` ASC, `monthyear` DESC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($visitedPages){

            // Get max for calc
            $allHits = $visitedPages->pluck('hits');
            $max = count($allHits)>0?max($allHits):0;

            $numPeriods = count($selectedPeriods);
            if($numPeriods<1) $numPeriods = 1; // Avoid division by zero

            // Loop query results
            foreach($visitedPages as $page){
                $kirbyPage = kirby()->page($page->uid); // This is probably the slowest part to be optimized some day ? use bnomei/kirby-boost ?

                // Default data not based on the $page object
                $pageStatsData[] = [
                    'url'           => $page->uid,
                    'uid'           => $page->uid,
                    'title'         => ['href'=>false,'text'=>$page->uid . ' (404)'],
                    'average'       => round(intval($page->hits, 10)/$numPeriods),
                    'hits'          => intval($page->hits, 10),
                    'hitspercent'   => round(($page->hits/$max),2),
                    'firstvisited'  => getDateFromPeriod(intval($page->firstvisited, 10), SIMPLESTATS_TABLE_DATE_FORMAT),
                    'lastvisited'   => getDateFromPeriod(intval($page->lastvisited, 10), SIMPLESTATS_TABLE_DATE_FORMAT),
                    'depth'         => -1,
                    'icon'          => ['icon'=>'page', 'color'=>'gray-700','back'=>'gray-200','ratio'=>'1/1'],
                    'flag'        => ['disabled'=>true,'status'=>'disabled'],
                ];

                // Augmented data by page object
                $lastEntry = count($pageStatsData)-1;
                if($kirbyPage) {
                    $pageStatsData[$lastEntry] = array_merge($pageStatsData[$lastEntry], [
                        'url'           => $kirbyPage->url(),
                        'title'         => ['href'=>$kirbyPage->panel()->url(), 'text'=>$kirbyPage->title()->value()],
                        'depth'         => $kirbyPage->depth()-1,
                        'icon'          => ['icon'=>$kirbyPage->blueprint()->icon()??'page', 'color'=>'gray-700','back'=>'gray-200','ratio'=>'1/1'],
                        // 'icon'    => $kirbyPage->panel()->image(
                        //     ['icon'=>'page', 'color'=>'gray-500','back'=>'black','ratio'=>'1/1'],
                        //     'table'
                        // ),
                        'flag'        => ['disabled'=>false,'status'=>$kirbyPage->status()],
                    ]);
                }

                // Inject language data
                if( kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages') === true ){
                    foreach( kirby()->languages() as $l ){
                        $langStr = 'hits_'.$l->code();
                        $pageStatsData[$lastEntry][$langStr] = intval($page->$langStr, 10);
                    }
                }
            }
        }
        else Logger::LogWarning("pageStats(visitedPages) : db error =".self::database()->lastError()->getMessage() );

        // Compute visits over time (monthly)
        $visitsOverTime = self::database()->query('SELECT `monthyear`, SUM(`hits`) AS `hits` FROM `pagevisits`'.$whereQuery.' GROUP BY `monthyear` ORDER BY `monthyear` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($visitsOverTime){
            foreach($visitsOverTime as $timeFrame){
                $key = intval($timeFrame->monthyear,10);
                // Only keep value if key exists in array (respect timespan)
                if(array_key_exists($key, $visitsOverTimeData)) $visitsOverTimeData[$key] = $timeFrame->hits;
            }
        }
        else Logger::LogWarning("pageStats(visitsOverTime) : db error =".self::database()->lastError()->getMessage() );

        // Get pages over time
        // Todo: Add total and remove visitsOverTimeData, see https://stackoverflow.com/a/39374290/58565
        $pageVisitsOverTimeData=[];
        $pageVisitsOverTime = self::database()->query('SELECT `uid`, SUM(`hits`) AS `hits`, `monthyear` FROM `pagevisits`'.$whereQuery.' GROUP BY `UID`, `monthyear` ORDER BY `monthyear` ASC, `uid` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
        if($pageVisitsOverTime){
            //$pageTimeframes=[];
            foreach($pageVisitsOverTime as $page){
                $pagevisitPeriod = intval($page->monthyear, 10);
                $uid = $page->uid;

                // Need to create the first dataset for this page ?
                if( !array_key_exists($uid, $pageVisitsOverTimeData) ){
                    // Convert uid to title
                    $title = $uid;
                    if( $kirbyPage = kirby()->page($uid) ){
                        if($kirbyPage->title()->isNotEmpty()){
                            $title = (string) $kirbyPage->title()->value();
                        }
                    }
                    $pageVisitsOverTimeData[$uid] = [
                        'label' => $title,
                        'ss_uid' => $uid, // used by colorize in panel
                        'data'  => array_fill_keys(array_keys($selectedPeriods), 0),
                        //'fill' => true,//'origin',
                        // 'borderColor' => '#f00',
                        //'backgroundColor' => 'hsl('.round($colorTreshold*360).',100%,50%)',
                    ];
                }

                // Keep value
                if( array_key_exists($pagevisitPeriod, $pageVisitsOverTimeData[$uid]['data']) ){
                    $pageVisitsOverTimeData[$uid]['data'][$pagevisitPeriod] = intval($page->hits, 10);
                }
            }

            // Unset keys for JS
            foreach($pageVisitsOverTimeData as &$dataset){
                $dataset['data']=array_values($dataset['data']);
            }
        }
        else Logger::LogWarning("pageStats(pageVisitsOverTime) : db error =".self::database()->lastError()->getMessage() );

        // Compute Global languages data
        $globalLanguagesData  = [
            'label' => 'Global Visits per language',//$language->name(),
            'data' => array_fill_keys(kirby()->languages()->keys(), 0),
        ];
        $chartLanguagesLabels = kirby()->languages()->pluck('name');

        $languagesOverTimeData = [];
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
                        'label' => $language->name(),
                        'data' => array_fill_keys(array_keys($selectedPeriods), 0),
                    ];
                }
            }

            // Compute $languagesOverTime and $globalLanguagesData
            $languagesOverTimeQ = self::database()->query('SELECT `monthyear` '.$queryLangs.' FROM `pagevisits`'.$whereQuery.' GROUP BY `monthyear` ORDER BY `monthyear` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
            if($languagesOverTimeQ){
                foreach($languagesOverTimeQ as $timeFrame){
                    $langsPeriod = intval($timeFrame->monthyear, 10);

                    // Get hits for each lang on this period
                    foreach($kirbyLangs as $l){
                        // Keep value
                        if( array_key_exists($langsPeriod, $languagesOverTimeData[$l]['data']) ){
                            $languagesOverTimeData[$l]['data'][$langsPeriod] = intval($timeFrame->$l, 10);

                            // compute globals
                            $globalLanguagesData['data'][$l] += $languagesOverTimeData[$l]['data'][$langsPeriod];
                        }
                    }
                }

                // Unset keys for JS
                foreach($languagesOverTimeData as &$dataset){
                    $dataset['data']=array_values($dataset['data']);
                }
                $globalLanguagesData['data']=array_values($globalLanguagesData['data']);
            }
            else Logger::LogWarning("pageStats(languagesOverTime) : db error =".self::database()->lastError()->getMessage() );
        }

        // Flush all data
        return [
            'pagestatsdata'         => $pageStatsData,
            'pagestatslabels'       => $pageStatsLabels,

            'chartperiodlabels'     => $chartPeriodLabels,
            'visitsovertimedata'    => [
                [
                    'label' => 'Total Visits',
                    'data'  => array_values($visitsOverTimeData), // ['labels'=>[], 'datasets'=>['label'=>'Total Visits', 'data'=>[]]]
                ],
            ],
            'pagevisitsovertimedata'=> array_values($pageVisitsOverTimeData),

            'globallanguagesdata'   => [$globalLanguagesData],
            'chartlanguageslabels'   => array_values($chartLanguagesLabels), // Pie chart labels
            'languagesovertimedata' => array_values($languagesOverTimeData),
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

        // init return variabes
        $newPageVisits = []; // --> $newPageVisits[period][pageid][attr]
        $newDevices = [];
        $newEngines = [];
        $newSystems = [];

        if(!$time) $time = time();

        // Get visitors older then 1 day
        $yesterday = $time - option('daandelange.simplestats.tracking.uniqueSeconds', 24*60*60);
        $visitors = self::database()->query('SELECT `userunique`, `visitedpages`, `osfamily`, `devicetype`, `browserengine`, `timeregistered` FROM `pagevisitors` WHERE `timeregistered` <= '.$yesterday.' ORDER BY `timeregistered` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);

        // Todo: this code could be refactored to use $timeFrame
        if($visitors){
            // process each one
            foreach($visitors as $visitor){
                $sincePeriod = getPeriodFromTime(intval($visitor->timeregistered));

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
                            $newPageVisits[$sincePeriod][]=[
                                'hits' => 1,//$pageInfo['hits'],
                                'uid'  => $page,
                                'timeframe' => $sincePeriod,
                                'langhits' => $pageInfo['langs'],
                            ];
                        }
                        // Increment existing page ?
                        else {

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
                if( $visitor->userunique && !self::database()->query('DELETE FROM `pagevisitors` WHERE `userunique`="'.$visitor->userunique.'"; ') ){
                    Logger::LogWarning('DBFAIL. Error on syncing stats. On delete visitor. Error='.self::database()->lastError()->getMessage() );
                }
            }

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

                    $existingPages = self::database()->query('SELECT `id`, `uid`, `hits`, '.$queryLangs.' FROM `pagevisits` WHERE `monthyear` = '.$pagePeriod.' LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);

                    // Query ok ?
                    if($existingPages){

                        $monthPages = $existingPages->toArray();

                        // Loop newly visited pages (existing)
                        foreach( $monthlyPageVisits as $newPageInfo ){
                            $newHits = $newPageInfo['hits'];

                            $key = array_search( $newPageInfo['uid'], array_column($monthPages, 'uid') );
                            // Needs new entry this month ?
                            if( $key === false ){
                                $uid = $newPageInfo['uid'];

                                // Ignore non-existent pages
                                if( !kirby()->page($uid) ){
                                    Logger::LogVerbose('Error syncing new visits : Kirby could not find the registered page ('.$uid .'). Has it been deleted ?');
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
                                if(!self::database()->query('INSERT INTO `pagevisits` (`uid`, `hits`, `monthyear` '.$langKeys .' ) VALUES ("'.$uid .'", '.$newHits .', '.$pagePeriod .' '.$langValues .')')){
                                    Logger::LogWarning("Could not INSERT pagevisits while syncing. Error=".self::database()->lastError()->getMessage());
                                }
                            }
                            // Update existing entry
                            elseif($newHits>0) {
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

                                if(!self::database()->query('UPDATE `pagevisits` SET `hits`=`hits` + '.$newHits .' '.$langEdits .' WHERE `id`='.$id.'') ){
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
                    $existingDevices = self::database()->query('SELECT `id`, `device`, `hits` FROM `devices` WHERE `monthyear` = '.$devicesPeriod .' LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);

                    if($existingDevices){
                        $existingDevicesA = $existingDevices->toArray();

                        // Loop visited devices (existing)
                        foreach( $monthlyDevices as $newDeviceInfo ){
                            $newHits = $newDeviceInfo['hits'];

                            $key = array_search( $newDeviceInfo['device'], array_column($existingDevicesA, 'device') );
                            // Needs new entry ?
                            if( $key === false ){
                                // Todo : verify validity of data ?
                                // Save
                                if(!self::database()->query('INSERT INTO `devices` (`device`, `hits`, `monthyear`) VALUES ("'.$newDeviceInfo['device'].'", '.$newHits .', '.$devicesPeriod .')')){
                                    Logger::LogWarning("Could not INSERT new device while syncing. Error=".self::database()->lastError()->getMessage());
                                }
                            }
                            // Update existing entry
                            elseif($newHits>0) {
                                $id = $existingDevicesA[$key]->id;
                                if(!self::database()->query('UPDATE `devices` SET `hits`=`hits` + '.$newHits .' WHERE `id`='.$id .';') ){
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
                    $existingSystems = self::database()->query('SELECT `id`, `system`, `hits` FROM `systems` WHERE `monthyear` = '.$systemsPeriod .' LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);

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
                                if(!self::database()->query('INSERT INTO `systems` (`system`, `hits`, `monthyear`) VALUES ("'.$newSystemInfo['system'].'", '.$newHits .', '.$systemsPeriod .')')){
                                    //echo 'DBFAIL [insert new system]'."\n";
                                    Logger::LogWarning("Could not INSERT systems while syncing. Error=".self::database()->lastError()->getMessage());
                                }
                            }
                            // Update existing entry
                            elseif($newHits>0) {
                                $id = $existingSystemsA[$key]->id;
                                if(!self::database()->query('UPDATE `systems` SET `hits`=`hits` + '.$newHits .' WHERE `id`="'.$id .'"') ){
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
                    $existingEngines = self::database()->query('SELECT `id`, `engine`, `hits` FROM `engines` WHERE `monthyear` = '.$enginesPeriod .' LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);

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
                                if(!self::database()->query('INSERT INTO `engines` (`engine`, `hits`, `monthyear`) VALUES ("'.$newEngineInfo['engine'].'", '.$newHits .', '.$enginesPeriod .')')){
                                    Logger::LogWarning("Could not INSERT engines while syncing stats. Error=".self::database()->lastError()->getMessage());
                                }
                            }
                            // Update existing entry
                            elseif($newHits>0) {
                                $id = $existingEnginesA[$key]->id;
                                if(!self::database()->query('UPDATE `engines` SET `hits`=`hits` + '.$newHits .' WHERE `id`='.$id .';') ){
                                    Logger::LogWarning("Could not UPDATE engine hits while syncing stats. Error=".self::database()->lastError()->getMessage());
                                }
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
    public static function onePageStats($page, int $fromPeriod = null, int $toPeriod = null){
        // Get ID from $page
        if($page && $page instanceof \Kirby\Cms\Page) $page = $page->exists()?$page->uid():''; // todo: provide fallback (virtual pages don't exist?)

        // Ensure we got a string
        if( !is_string($page) || empty($page)){
            throw new Exception("Only accepting non-empty string or an existing \Kirby\Cms\Page !");
        }

        // Return object
        $ret = [
            'title'             => '[unknown] (404)',
            'uid'               => $page,
            'languagesOverTime' => [],
            //'visitsOverTime'    => [],
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

            // Below are from ::pageStats()
            //'pagestatsdata'         => $pageStatsData,
            //'pagestatslabels'       => $pageStatsLabels,

            'chartperiodlabels'     => [],//$chartPeriodLabels,
            'chartlanguageslabels'  => [],
            'visitsovertimedata'    => [
                [
                    'label' => 'Total Visits',
                    'data'  => [],//array_values($visitsOverTimeData), // ['labels'=>[], 'datasets'=>['label'=>'Total Visits', 'data'=>[]]]
                ],
            ],
//            'pagevisitsovertimedata'=> array_values($pageVisitsOverTimeData),
//
//            'globallanguagesdata'   => [$globalLanguagesData],
//            'chartlanguageslabels'   => array_values($chartLanguagesLabels), // Pie chart labels
//            'languagesovertimedata' => array_values($languagesOverTimeData),
//            'languagesAreEnabled'   => (option('daandelange.simplestats.tracking.enableVisitLanguages') === true) && kirby()->multilang(),
        ];

        // Fetch kirbyPage object to get some more information
        $kirbyPage = kirby()->page($page); // This is probably the slowest part, to be optimized some day. todo
        if($kirbyPage && $kirbyPage->exists()) $ret['title'] = $kirbyPage->title()->value();
        else $ret['title'] = $page.' (404)'; // Use id for tracked pages without ID (virtual pages...)

        // init languages stuff
        $kirbyLangs = [];
        if( option('daandelange.simplestats.tracking.enableVisitLanguages') === true ){
            //$ret['languagesAreEnabled'] = true;
            if(kirby()->multilang()){
                foreach( kirby()->languages() as $l ){
                    $kirbyLangs[$l->code()] = $l->name();
                }
            }
            else {
                $kirbyLangs['en'] = 'Default Language';
            }
        }
        $ret['chartlanguageslabels'] = array_values($kirbyLangs);

        // Grab timespan, if provided, or use whole
        $nowPeriod = getPeriodFromTime();
        // Format period
        $timeSpan = static::constrainPeriodsToDbSpan($fromPeriod, $toPeriod, $page);
        if(!$toPeriod) $timeSpan['end'] = $nowPeriod; // todo: Set to period today so untracked days are shown 0 by default.

        // Array holding all possible periods
        $selectedPeriods = [];
        
        // Global Where query part
        $whereQuery = '';

        if($timeSpan){
            $selectedPeriods = array_combine(
                static::fillPeriod( $timeSpan['start'], $timeSpan['end'] ),
                static::fillPeriod( $timeSpan['start'], $timeSpan['end'], 'Y-m-d' ),
            );
            $whereQuery = ' AND `monthyear` BETWEEN '.$timeSpan['start'].' AND '.$timeSpan['end'];
        }
        

        // Query page visits over time, with languages
        {
            // Init data
            $ret['totalHits'] = 0;
            $ret['lastPeriodHits']= 0;

            $ret['firstVisited'] = 0;
            $ret['lastVisited'] = 0;

            // Prepare language-dependent data
            $langQuery = '';
            foreach($kirbyLangs as $l => $name){
                // For SQL query
                $langQuery .= ', `hits_'.$l.'` AS `'.$l.'`';

                // Create keys for each language
                $ret['languagesOverTime'][$l]=[
                    'label' => $name,
                    'data' => array_fill_keys(array_keys($selectedPeriods), 0),// Populate all periods with empty values
                ];
            }

            // Prepare totals hits per language
            $ret['languageTotalHits'][0]=[
                'label' => 'Page visits per language',
                'data'  => array_fill_keys(array_keys($kirbyLangs), 0),
            ];
            

            $pageVisitsOverTime = self::database()->query('SELECT `uid`, `monthyear`, `hits` '.$langQuery.' FROM `pagevisits` WHERE `uid` = "'.$page.'"'.$whereQuery.' ORDER BY `monthyear` ASC LIMIT 0,'.SIMPLESTATS_DUMMY_DB_LIMIT);
            if($pageVisitsOverTime){
                // Loop periods
                $prevPeriod = null;
                foreach($pageVisitsOverTime as $period){
                    // Compute total hits
                    $ret['totalHits'] += $period->hits;

                    // Remember Today period visits ?
                    if( $period->monthyear == $nowPeriod ){
                        $ret['lastPeriodHits'] = $period->hits;
                    }

                    // Get time and date str
                    $periodTime = getTimeFromPeriod(intval($period->monthyear, 10));
                    //$periodDateStr = date(SIMPLESTATS_TIMELINE_DATE_FORMAT, $periodTime);

                    // Remember first and last dates
                    if($ret['firstVisited'] > $periodTime || $ret['firstVisited']==0) {
                        $ret['firstVisited'] = $periodTime;
                    }
                    if($ret['lastVisited'] < $periodTime || $ret['lastVisited']==0) {
                        $ret['lastVisited'] = $periodTime;
                    }

                    // Inject total per language
                    foreach( $kirbyLangs as $l => $n ){
                        // $ret['languagesOverTime'][$l]['data'][]=[$periodDateStr, $period->$l];
                        // $ret['languagesOverTime'][$l]['data'][$periodDateStr]=$period->$l;
                        $ret['languagesOverTime'][$l]['data'][intval($period->monthyear, 10)]+=intval($period->$l, 10);
                        $ret['languageTotalHits'][0]['data'][$l]+=$period->$l;
                    }
                }
            }

            $ret['chartperiodlabels'] = array_values($selectedPeriods);

            // Rename keys to nums so that the charts accept the data
            $ret['languagesOverTime'] = array_values($ret['languagesOverTime']);
            
            // Rm data (period) keys
            foreach( $ret['languagesOverTime'] as $l => $n ){
                $ret['languagesOverTime'][$l]['data'] = array_values($ret['languagesOverTime'][$l]['data']);
            }
            $ret['languageTotalHits'][0]['data'] = array_values($ret['languageTotalHits'][0]['data']);

            // Compute averages
            $ret['trackingPeriods'] = getNumPeriodsFromDates($ret['firstVisited'], time());
            if($ret['trackingPeriods']>0) $ret['averageHits'] = $ret['totalHits'] / $ret['trackingPeriods'];
            $ret['timespanUnitName'] = getTimeFrameUtility()->getPeriodName(false);

            // Format dates
            if($ret['lastVisited']>0) $ret['lastVisited'] = date(SIMPLESTATS_TIMELINE_DATE_FORMAT, $ret['lastVisited']);
            if($ret['firstVisited']>0) $ret['firstVisited'] = date(SIMPLESTATS_TIMELINE_DATE_FORMAT, $ret['firstVisited']);

        }

        return $ret;
    }
}
