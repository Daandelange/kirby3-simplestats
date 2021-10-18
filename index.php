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
    'userMethods'  => require 'src/config/usermethods.php',

    // New K3.6 method
    'areas' => [
        'simplestats' => function ($kirby) {
            return [
                // label for the menu and the breadcrumb
                'label' => 'Simple Stats',

                // icon for the menu and breadcrumb
                'icon' => 'chart',

                // optional replacement for the breadcrumb label
                'breadcrumbLabel' => function () {
                  return 'SimppleStats - All your data are belong to us !';
                },

                // show / hide from the menu
                'menu' => true,

                //'link' => 'simplestats',
                //'disabled' => false,

                // views
                'views' => [
                  [
                    // the Panel patterns must not start with 'panel/',
                    // the `panel` slug is automatically prepended.
                    'pattern' => 'simplestats',
                    'action'  => function () use ($kirby)  {

                      // view routes return a simple array,
                      // which will be injected into our Vue app;
                      // the array can control the loaded Vue component,
                      // props for the component and settings for the current view
                      // (like breadcrumb, title, active search type etc.)

                      return [
                        // the Vue component can be defined in the
                        // `index.js` of your plugin
                        'component' => 'simplestats',//k-plausible-view',//k-simplestats-view',

                        // the document title for the current view
                        'title' => 'Simple Stats',

                        // the breadcrumb can be just an array or a callback
                        // function for more complex breadcrumb logic
//                         'breadcrumb' => function () {
//                           // each item in the breadcrumb array
//                           // has a label and a link attribute
//                           return [
//                             [
//                               'label' => 'Buy some milk',
//                               'link'  => '/todos/123'
//                             ]
//                           ];
//                         },

                        // props will be directly available in the
                        // Vue component. It's a super convenient way
                        // to send backend data to the Panel
//                         'props' => [
//                           'todos' => Array
//                         ],

                        // we can preset the search type with the
                        // search attribute
                        'search' => 'pages'
                      ];
                    }
                  ]
                ]
            ];
        },
    ],
]);
