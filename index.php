<?php

namespace daandelange\SimpleStats;

use Kirby\Cms\App;

@include_once __DIR__ . '/vendor/autoload.php';

//$stats = Stats()::list();

App::plugin('daandelange/simplestats', [
    'options'      => require 'src/config/options.php',
    'api'          => require 'src/config/api.php',
    'hooks'        => require 'src/config/hooks.php',
    'translations' => require 'src/config/translations.php',
    'pageMethods'  => require 'src/config/pagemethods.php',
    'routes'       => require 'src/config/routes.php',
]);
