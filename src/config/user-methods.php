<?php

return [

    /**
     * Check if the current user has access to the SimpleStats panel.
     *
     * @param bool $requireAdmin Whether to require full admin rights
     * @return bool
     */
    'hasSimpleStatsPanelAccess' => function (bool $requireAdmin = false): bool {

        $user = kirby()->user();

        // Panel must be enabled
        if (!option('daandelange.simplestats.panel.enable', false)) {
            return false;
        }

        // User must be logged in
        if (!$user?->isLoggedIn()) {
            return false;
        }

        // User role must be authorized
        // $authorizedRoles = option('daandelange.simplestats.panel.authorizedRoles', ['admin']);
        // if (!in_array($user->role()->id(), $authorizedRoles)) {
        //     return false;
        // }

        // // If special admin access is required, user must be admin
        // if ($requireAdmin && !$user->isAdmin()) {
        //     return false;
        // }
        // return true;

        // New : use Kirby permissions
        return $user->role()->permissions()->for('daandelange.simplestats', $requireAdmin?'configure':'access');
    }   

];
