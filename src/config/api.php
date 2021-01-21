<?php

namespace daandelange\SimpleStats;

use Kirby\Exception\PermissionException;

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
                    return Stats::listDbInfo();
                }
                else {
                    throw new PermissionException('You are not authorised to view statistics.');
                }
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
