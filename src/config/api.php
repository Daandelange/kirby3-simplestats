<?php

namespace daandelange\SimpleStats;

use Kirby\Exception\PermissionException;
use Kirby\Exception\Exception;
use Throwable;
use I18n;

return [

    // Routes for the stats api in the panel
    'routes' => function($kirby){ return [
        [
            'pattern' => 'simplestats/listvisitors',
            'method'  => 'GET',
            'action'  => function () {
                if( $this->user()->hasSimpleStatsPanelAccess() ){
                    return Stats::listvisitors();
                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
            }
        ],
        [
            'pattern' => 'simplestats/devicestats',
            'method'  => 'GET',
            'action'  => function () {
                if( $this->user()->hasSimpleStatsPanelAccess() ){
                    return Stats::deviceStats();
                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
            },
        ],
        [
            'pattern' => 'simplestats/refererstats',
            'method'  => 'GET',
            'action'  => function () {
                if( $this->user()->hasSimpleStatsPanelAccess() ){
                    return Stats::refererStats();
                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
            },
        ],
        [
            'pattern' => 'simplestats/pagestats',
            'method'  => 'GET',
            'action'  => function () {
                if( $this->user()->hasSimpleStatsPanelAccess() ){
                    return Stats::pageStats();
                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
            },
        ],
        [
            'pattern' => 'simplestats/listdbinfo',
            'method'  => 'GET',
            'action'  => function () {
                if( $this->user()->hasSimpleStatsPanelAccess() ){
                    try {
                        $stats = Stats::listDbInfo();
                        return $stats;//Stats::listDbInfo();
                    } catch (Throwable $e) {
                        Logger::logTracking('Could not fetch db info and requirements... Error='.$e->getMessage().'(file: '.$e->getFile().'#L'.$e->getLine().')');
                        throw new Exception($e->getMessage());
                    }

                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
                //return null;//['message'=>'Test'];
            },
        ],
        [
            'pattern' => 'simplestats/configinfo',
            'method'  => 'GET',
            'action'  => function () {
                if( $this->user()->hasSimpleStatsPanelAccess() ){
                    // Precompute some data
                    $salt = option('daandelange.simplestats.tracking.salt', '');
                    $logLevels = [];
                    if( option('daandelange.simplestats.log.tracking',false) ) $logLevels[] = I18n::translate('simplestats.info.config.log.level.tracking', 'Tracking');
                    if( option('daandelange.simplestats.log.warnings',false) ) $logLevels[] = I18n::translate('simplestats.info.config.log.level.warnings', 'Warnings');
                    if( option('daandelange.simplestats.log.verbose' ,false) ) $logLevels[] = I18n::translate('simplestats.info.config.log.level.verbose', 'Verbose');

//                     $dbFile = option('daandelange.simplestats.tracking.database');
//                     $dbSize = '?? Kb';
//                     if($dbFile){
//                         try {
//                             if( file_exists($dbFile) && $fileSize = filesize($dbFile) ){
//                                 $dbSize = $fileSize.' Kb';
//                             }
//                         } catch (Exception $e){
//                             // ignore
//                         }
//                         // Use short path for display
//                         $dbFile = str_replace( kirby()->root(),'', $dbFile);
//                     }

                    return [
                        'saltIsSet'             => ( is_string($salt) && !empty($salt) && $salt!=='CHANGEME'),
                        'trackingPeriodName'    => getTimeFrameUtility()->getPeriodAdjective(),
                        'uniqueSeconds'         => option('daandelange.simplestats.tracking.uniqueSeconds', -1),
                        //'databaseLocation'      => $dbFile ?? '[undefined]',
                        //'databaseSize'          => $dbSize,
                        'enableReferers'        => option('daandelange.simplestats.tracking.enableReferers', false),
                        'enableDevices'         => option('daandelange.simplestats.tracking.enableDevices', false),
                        'enableVisits'          => option('daandelange.simplestats.tracking.enableVisits', false),
                        'enableVisitLanguages'  => kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages', false),
                        'ignoredRoles'          => option('daandelange.simplestats.tracking.ignore.roles',[]),
                        'ignoredPages'          => option('daandelange.simplestats.tracking.ignore.pages',[]),
                        'ignoredTemplates'      => option('daandelange.simplestats.tracking.ignore.templates',[]),
                        'logFile'               => str_replace( kirby()->root(),'', option('daandelange.simplestats.log.file',[]) ),
                        'logLevels'             => $logLevels,
                        'trackingSince'         => 'todo', // todo
                    ];
//                     try {
//                         $stats = Stats::listDbInfo();
//                     } catch (Throwable $e) {
//                         Logger::logTracking('Could not fetch db info and requirements... Error='.$e->getMessage().'(file: '.$e->getFile().'#L'.$e->getLine().')');
//                         throw new Exception($e->getMessage());
//                     }
                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
                //return null;//['message'=>'Test'];
            },
        ],
        [
            'pattern' => 'simplestats/trackingtester',
            'method'  => 'GET',
            'action'  => function () {
                if( $this->user()->hasSimpleStatsPanelAccess() ){
                    $device = SimpleStats::detectSystemFromUA();
                    // Translate device
                    if(isset($device['device'])) $device['device'] = Stats::translateDeviceType($device['device']);
                    return [
                        //'referrer' => SimpleStats::getRefererInfo(),
                        'currentUserAgent'  => substr($_SERVER['HTTP_USER_AGENT'], 0, 256),
                        'currentDeviceInfo' => $device,
                    ];
                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
            }
        ],
        [
            'pattern' => 'simplestats/trackingtester/referrer',
            'method'  => 'GET',
            'action'  => function () use ($kirby) {
                if( $this->user()->hasSimpleStatsPanelAccess() ){
                    $str = @$kirby->request()->query()->data()['referrer']??substr(@$_SERVER['HTTP_REFERRER'], 0, 256);
                    //var_dump($str, SimpleStats::getRefererInfo($str));exit;//$kirby->request()->query());
                    //$refererInfo = SimpleStats::getRefererInfo($str);
                    return [
                        'referrerInfo' => SimpleStats::getRefererInfo($str)??'Invalid referrer !',
                    ];
                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
            }
        ],
        [
            'pattern' => 'simplestats/trackingtester/ua',
            'method'  => 'GET',
            'action'  => function () use ($kirby) {
                if( $this->user()->hasSimpleStatsPanelAccess() ){
                    $str = @$kirby->request()->query()->data()['ua']??'';//??substr($_SERVER['HTTP_USER_AGENT'], 0, 256);
                    //if(empty($str)) $str='';
                    //var_dump($str);
                    $uainfo = SimpleStats::detectSystemFromUA($str);
                    if($uainfo && isset($uainfo['device'])) $uainfo['device'] = Stats::translateDeviceType($uainfo['device']);
                    return $uainfo??'Invalid referrer url!';
                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
            }
        ],
        [
            'pattern' => 'simplestats/trackingtester/generatestats',
            'method'  => 'GET',
            'action'  => function () use ($kirby) {
                if( $this->user()->hasSimpleStatsPanelAccess() ){

                    // Get time range info
                    $from = @$kirby->request()->query()->data()['from'];
                    $to = @$kirby->request()->query()->data()['to'];
                    if($from && $to){
                        // Parse range as date ? dd-mm-yyyy
                        if( strpos($from, '-')===2 && strpos($to, '-')===2 ){
                            $day=intval(substr($from, 0,2), 10);
                            $month=intval(substr($from, 3,2), 10);
                            $year=intval(substr($from, 6,4), 10);
                            $from = mktime(0,0,0,$month,$day,$year);
                            $day=intval(substr($to, 0,2), 10);
                            $month=intval(substr($to, 3,2), 10);
                            $year=intval(substr($to, 6,4), 10);
                            $to = mktime(0,0,0,$month,$day,$year);
                        }
                        // Parse as timestamp
                        else {
                            $from = intval($from, 10);
                            $to = intval($to, 10);
                        }

                        // Parse mode
                        $mode = @$kirby->request()->query()->data()['mode']??null;

                        // Confirm ?
                        $proceed = @$kirby->request()->query()->data()['proceed']??'';
                        if($proceed !== 'yes'){
                            return ['status'=>false, 'error'=>'Please confirm that the date ranges from '.date('d-M-Y', $from).' to '.date('d-M-Y', $to).'. (check that box!)'];//, adding &proceed=yes to the query param.'];
                        }

                        // go !
                        return StatsGenerator::GenerateVisits($from, $to, $mode);

                        //$uainfo = SimpleStats::detectSystemFromUA($str);
                        //    if($uainfo && isset($uainfo['device'])) $uainfo['device'] = Stats::translateDeviceType($uainfo['device']);
                        //    return $uainfo??'Invalid referrer url!';

                        //return ['status'=>false,'message'=>'ok ?'];
                    }

                    return ['status'=>false,'error'=>'No range !'];
                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
            }
        ],
        [
            'pattern' => 'simplestats/checkrequirements',
            'method'  => 'GET',
            'action'  => function () {
                if( $this->user()->hasSimpleStatsPanelAccess() ){
                    try {
                        $reqs = [
                            'php' => kirby()->system()->php(),
                            'kirby' => (intval(str_replace('.','',substr(kirby()->version(), 0, 3) ), 10) >= 35),
                            'sqlite3' => (class_exists('SQLite3') && in_array('pdo_sqlite', get_loaded_extensions()) && in_array('sqlite3', get_loaded_extensions())),
                        ];
                        // Check requirements

                        $dbRequirements = "PHP=".($reqs['php']?'OK':'ERROR').', ';
                        $dbRequirements .= "SQLite3=".($reqs['sqlite3']?'OK':'ERROR').', ';
                        $dbRequirements .= "Kirby=".($reqs['kirby']?'OK':'ERROR').' --- --- --- ';
                        // Tmp: display lots of data, try to detect errors
                        $dbRequirements .= 'PHP Extensions='.implode(', ', get_loaded_extensions());
//                         $dbRequirements .= " --- PHP=".($reqs['php']?'OK':'ERROR');
//                         $dbRequirements .= " --- SQLite3=".($reqs['sqlite3']?'OK':'ERROR');
//                         try{
//                             $sql = new \SQLite3('');
//                             $sql->close();
//                             $dbRequirements .= " --- SQLite3.try=OK";
//                         } catch(Throwable $e){
//                             $dbRequirements .= " --- SQLite3.try=ERROR ".$e->getMessage();
//                         }
//                         try{
//                             $db=new \Kirby\Database\Database(['type'=>'sqlite','database'=>'']);
//                             $dbRequirements .= " --- CreateDB()=".(($db)?'OK':'FAIL');
//                         } catch(Throwable $e){
//                             $dbRequirements .= " --- CreateDB()=ERROR:".$e->getMessage();
//                         }
//                         $dbRequirements .= " --- pdo_sqlite=".( in_array('pdo_sqlite', get_loaded_extensions())?'OK':'ERROR');
//                         $dbRequirements .= " --- sqlite3=".( in_array('sqlite3', get_loaded_extensions())?'OK':'ERROR');

                        return [
                            'dbRequirements'       => $dbRequirements,
                            'dbRequirementsPassed' => ($reqs['php'] && $reqs['kirby'] && $reqs['sqlite3']),
                        ];
                    } catch (Throwable $e) {
                        Logger::logTracking('Could not fetch requirements... Error='.$e->getMessage().'(file: '.$e->getFile().'#L'.$e->getLine().')');
                        throw new Exception($e->getMessage());
                    }

                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
                //return null;//['message'=>'Test'];
            },
        ],
        [
            'pattern' => 'simplestats/dbupgrade',
            'method'  => 'GET',
            'action'  => function () {
                // Only allow admins explicitly for upgrading the db
                if( $this->user()->hasSimpleStatsPanelAccess(true) ){
                    $result = Stats::checkUpgradeDatabase(false);
                    return [
                        'status'    => $result,
                        'message'   => ($result?'Success !':'Error!').' Check your logs file for more details.',
                    ];
                }
                else {
                    throw new PermissionException('You are not authorised to upgrade the db file.');
                }
            },
        ],
        [
            'pattern' => 'simplestats/mainview',
            'method'  => 'GET',
            'action'  => function () {
                if( $this->user()->hasSimpleStatsPanelAccess() ){
                    return [
                        'dismissDisclaimer' => option('daandelange.simplestats.panel.dismissDisclaimer', false),
                        'translations' => [
                            'tabs' => [
                                'pagevisits'        => t('simplestats.tabs.pagevisits',     'Page Visits'),
                                'visitordevices'    => t('simplestats.tabs.visitordevices', 'Visitor Devices'),
                                'referers'          => t('simplestats.tabs.referers',       'Referers'),
                                'information'       => t('simplestats.tabs.information',    'Information'),
                            ],
                        ],
                    ];
                }
                else {
                    throw new PermissionException('You are not authorised to upgrade the db file.');
                }
            },
        ],
    ];},

];
