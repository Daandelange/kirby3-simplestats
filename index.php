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

            if(!$kirby->user() || !$kirby->user()->hasSimpleStatsPanelAccess()) return [];

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

                'link' => 'simplestats',

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

                        $tabs = [];
                        foreach([
                            'pagevisits'        => ['label'=>'Page visits',     'icon'=>'layers'],
                            'visitordevices'    => ['label'=>'Visitor Devices', 'icon'=>'users' ],
                            'referers'          => ['label'=>'Referers',        'icon'=>'chart' ],
                            'information'       => ['label'=>'Information',     'icon'=>'map'   ],
                        ] as $key=>$tabData){
                            $tabs[$key] = [
                                'name' => $key,
                                'label' => t('simplestats.tabs.'.$key, $tabData['label']),
                                'icon' => $tabData['icon'],
                                'columns' => [],// Needed for the panel not to crash
                                // 'link' => 'simplestats?tab='.$key,
                                //'link' => 'javascript:alert("OK")',
                            ];
                        };
                        $timeSpan = Stats::getDbTimeSpan();
                        $timeFrames = Stats::fillPeriod($timeSpan['start'], $timeSpan['end'], 'Y-m-d');
                        // $timeFrames = [];
                        // $tfu = getTimeFrameUtility();
                        // for($period=min($timeSpan[0]); $period <= getPeriodFromTime(); $period=incrementPeriod($period) ){
                        //     $timeFrames[] = 
                        // } 

                        return [
                            // the Vue component can be defined in the
                            // `index.js` of your plugin
                            'component' => 'k-simplestats-view',

                            // the document title for the current view
                            'title' => 'Simple Stats',

                            // the breadcrumb
                            'breadcrumb' => function () use($kirby, $tabs) {
                                $tabID = $kirby->request()->get('tab') ?? 'pagevisits';
                                
                                return [[
                                'label' => array_keys($tabs, $tabID) ? $tabs[$tabID]['label'] : t('simplestats.tabs.pagevisits'),
                                //'link'  => '/simplestats?tab='.$tabID,
                                ]];
                            },

                            // props will be directly available in the
                            // Vue component. It's a super convenient way
                            // to send backend data to the Panel
                            'props' => [
                                'initialtab' => $kirby->request()->get('tab') ?? $tabs['pagevisits']['name'],
                                'tabs' => array_values($tabs),
                                'globaltimespan' => [
                                    date('Y-m-d', getTimeFromPeriod($timeSpan['start'])),
                                    date('Y-m-d', getTimeFromPeriod($timeSpan['end'])),
                                ],
                                'timeframes' => $timeFrames,
                            ],

                            // we can preset the search type with the
                            // search attribute
                            //'search' => 'pages'
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
