<?php

namespace daandelange\SimpleStats;

return [
    'hasSimpleStatsPanelAccess' => function (bool $forSpecialAdminAccess = false) : bool {
        $user = kirby()->user();
        return
            // Panel is active
            ( option('daandelange.simplestats.panel.enable', false)===true)
            &&
            // User is logged in
            $user->isLoggedIn()
            &&
            // user is authorized to view statistics
            in_array( $user->role()->id(), option('daandelange.simplestats.panel.authorizedRoles', ['admin']) )
            &&
            ( !$forSpecialAdminAccess || $user->isAdmin() ); //  && in_array( $this->user()->role()->id(), ['admin'] )
    }
];
