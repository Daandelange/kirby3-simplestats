<?php

namespace daandelange\SimpleStats;

use Kirby\Exception\PermissionException;
use Kirby\Exception\Exception;
use Throwable;

return [

    // Routes for the stats api in the panel
    'routes' => [
        [
            'pattern' => 'simplestats/listvisitors',
            'method'  => 'GET',
            'action'  => function () {
                if( option('daandelange.simplestats.panel.enable', false)===true && $this->user()->isLoggedIn() && in_array( $this->user()->role()->id(), option('daandelange.simplestats.panel.authorizedRoles', ['admin']) ) ){
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
                if( option('daandelange.simplestats.panel.enable', false)===true && $this->user()->isLoggedIn() && in_array( $this->user()->role()->id(), option('daandelange.simplestats.panel.authorizedRoles', ['admin']) ) ){
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
                if( option('daandelange.simplestats.panel.enable', false)===true && $this->user()->isLoggedIn() && in_array( $this->user()->role()->id(), option('daandelange.simplestats.panel.authorizedRoles', ['admin']) ) ){
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
                if( option('daandelange.simplestats.panel.enable', false)===true && $this->user()->isLoggedIn() && in_array( $this->user()->role()->id(), option('daandelange.simplestats.panel.authorizedRoles', ['admin']) ) ){
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
                if( option('daandelange.simplestats.panel.enable', false)===true && $this->user()->isLoggedIn() && in_array( $this->user()->role()->id(), option('daandelange.simplestats.panel.authorizedRoles', ['admin']) ) ){
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
            'pattern' => 'simplestats/checkrequirements',
            'method'  => 'GET',
            'action'  => function () {
                if( option('daandelange.simplestats.panel.enable', false)===true && $this->user()->isLoggedIn() && in_array( $this->user()->role()->id(), option('daandelange.simplestats.panel.authorizedRoles', ['admin']) ) ){
                    try {
                        // Check requirements
                        // Tmp: display lots of data, try to detect errors
                        $dbRequirements = 'PHP Extensions='.implode(', ', get_loaded_extensions());
                        $dbRequirements .= " --- PHP=".(kirby()->system()->php()?'OK':'ERROR');
                        $dbRequirements .= " --- SQLite3=".(class_exists('SQLite3')?'OK':'ERROR');
                        try{
                            $sql = new \SQLite3('');
                            $sql->close();
                            $dbRequirements .= " --- SQLite3.try=OK";
                        } catch(Throwable $e){
                            $dbRequirements .= " --- SQLite3.try=ERROR ".$e->getMessage();
                        }
                        try{
                            $db=new \Kirby\Database\Database(['type'=>'sqlite','database'=>'']);
                            $dbRequirements .= " --- CreateDB()=".(($db)?'OK':'FAIL');
                        } catch(Throwable $e){
                            $dbRequirements .= " --- CreateDB()=ERROR:".$e->getMessage();
                        }
                        $dbRequirements .= " --- pdo_sqlite=".( in_array('pdo_sqlite', get_loaded_extensions())?'OK':'ERROR');
                        $dbRequirements .= " --- sqlite3=".( in_array('sqlite3', get_loaded_extensions())?'OK':'ERROR');

                        return [
                            'dbRequirements'    => $dbRequirements,
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
                // Only allow admins
                if( option('daandelange.simplestats.panel.enable', false)===true && $this->user()->isLoggedIn() && in_array( $this->user()->role()->id(), ['admin'] ) ){
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
    ],

];
