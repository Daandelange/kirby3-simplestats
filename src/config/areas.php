<?php

namespace daandelange\SimpleStats;

return [
    'simplestats' => function ($kirby) {
        $user = $kirby->user();

        if (!$user || !$user->hasSimpleStatsPanelAccess()) {
          return [];
        }

        $tabs = [
            'pagevisits'     => 'layers',
            'visitordevices' => 'users',
            'referers'       => 'chart',
            'information'    => 'map',
        ];

        // Advanced admin tab
        if(!$user->hasSimpleStatsPanelAccess(true)){
            unset($tabs['information']);
        }

        foreach ($tabs as $name => $icon) {
            $tabs[$name] = [
                'name'  => $name,
                'label' => t("simplestats.view.$name"),
                'icon'  => $icon,
            ];
        }

        return [
            'label' => t('simplestats.view'),
            'icon'  => 'chart',
            'menu'  => true,
            'link'  => 'simplestats',
            'views' => [[
                'pattern' => 'simplestats',
                'action'  => function () use ($kirby, $tabs) {
                    $tabKey = $kirby->request()->get('tab', 'pagevisits');
                    $tab    = $tabs[$tabKey] ?? $tabs['pagevisits'];

                    $timeSpan   = Stats::getDbTimeSpan();
                    $timeFrames = Stats::fillPeriod($timeSpan['start'], $timeSpan['end'], 'Y-m-d');
                    $timePeriod = getTimeFrameUtility()->getPeriodAdjective();

                    return [
                        'component' => 'k-simplestats-view',
                        'title'     => t('simplestats.view'),
                        'breadcrumb' => [[
                            'label' => $tab['label']
                        ]],
                        'props' => [
                            'initialtab'           => $tab['name'],
                            'tabs'                 => array_values($tabs),
                            'timeframes'           => $timeFrames,
                            'time-period'          => $timePeriod,
                            'initial-view-periods' => option('daandelange.simplestats.panel.defaultTimeSpan', -1),
                            'dismiss-disclaimer'   => option('daandelange.simplestats.panel.dismissDisclaimer', false),
                        ],
                    ];
                }
            ]],
        ];
    },
];
