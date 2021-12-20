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

            if(!$kirby->user() || !$kirby->user()->hasSimpleStatsPanelAccess()) return null;

            return [
                // label for the menu and the breadcrumb
                'label' => 'Simple Stats',

                // icon for the menu and breadcrumb
                'icon' => 'chart',

                // optional replacement for the breadcrumb label
                'breadcrumbLabel' => function () {
                  return option('daandelange.simplestats.panel.breadcrumbLabel', 'SimpleStats - All your data are belong to us !');
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

                        // the breadcrumb
                        'breadcrumb' => function () {
                            $tabLabel = t('simplestats.tabs.pagevisits');
                            $tabID = 'simplestats-tabs-visitedpages';
                            switch(get('tab')){
                                case 'simplestats-tabs-visitordevices' :
                                    $tabLabel = t('simplestats.tabs.visitordevices');
                                    $tabID = 'simplestats-tabs-visitordevices';
                                    break;
                                case 'simplestats-tabs-referers' :
                                    $tabLabel = t('simplestats.tabs.referers');
                                    $tabID = 'simplestats-tabs-referers';
                                    break;
                                case 'simplestats-tabs-info' :
                                    $tabLabel = t('simplestats.tabs.information');
                                    $tabID = 'simplestats-tabs-info';
                                    break;
                                default:
                                    break;
                            }
                            return [[
                              'label' => $tabLabel,
                              'link'  => '/simplestats?tab='.$tabID,
                            ]];
                        },

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

    // One page stats detail section
    'sections'  => [
        'pagestats' => [
            // Data that comes from blueprint
            'props' => [
                'headline' => function (string $headline = 'Page Stats') {
                    return $headline;
                },
                'showFullInfo' => function (bool $showFullInfo = false) {
                    return $showFullInfo;
                },
                'showTimeline' => function (bool $showTimeline = true) {
                    return $showTimeline;
                },
                'showLanguages' => function (bool $showLanguages = true) {
                    return $showLanguages;
                },
                'showTotals' => function (bool $showTotals = true) {
                    return $showTotals;
                },
                'size'  => function(string $size = 'medium') {
                    return $size;
                },
            ],
            'computed' => [
                'statsdata' => function () {
                    return $this->model()->getPageStats();
                }
            ]
        ],
    ],

]);
