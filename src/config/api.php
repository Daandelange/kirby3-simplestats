<?php

namespace daandelange\SimpleStats;

use Kirby\Exception\PermissionException;
use Kirby\Exception\Exception;
use Kirby\Toolkit\I18n;
use Throwable;

return [
    'routes' => function ($kirby): array {

        // Wrapper: normal panel access + logging
        $wrapAction = function (callable $callback, bool $requireAdmin = false): callable {
            return function (...$args) use ($callback, $requireAdmin): mixed {
                if (!$this->user()->hasSimpleStatsPanelAccess($requireAdmin)) {
                    throw new PermissionException(
                        $requireAdmin
                            ? 'You are not authorised to perform this action.'
                            : 'You are not authorised to view statistics.'
                    );
                }

                try {
                    return $callback(...$args);
                    //return call_user_func_array($callback, $args); // php 7 alternative if needed ?
                } catch (Throwable $e) {
                    Logger::logTracking('Error: ' . $e->getMessage() . ' (file: ' . $e->getFile() . '#L' . $e->getLine() . ')');
                    throw $e;
                }
            };
        };

        // Helper: safely get query parameter
        $getQueryParam = function (string $key, mixed $default = null) use ($kirby): mixed {
            $query = $kirby->request()->query();
            $data  = is_callable([$query, 'data']) ? $query->data() : [];
            return $data[$key] ?? $default;
        };

        // Helper: parse date strings or timestamps
        $parseDateRange = function (string|int $value): int {
            if (is_int($value)) return $value;
            if (strpos($value, '-') === 2) {
                $day = (int) substr($value, 0, 2);
                $month = (int) substr($value, 3, 2);
                $year = (int) substr($value, 6, 4);
                return mktime(0, 0, 0, $month, $day, $year);
            }
            return (int) $value;
        };

        // API Routes
        $apiRoutes = [
            [
                'pattern' => 'simplestats/pagestats',
                'method'  => 'GET',
                'action'  => $wrapAction(function (): array {
                    [$from, $to] = Stats::getTimeSpanFromUrl();
                    return Stats::pageStats($from, $to);
                })
            ],

            [
                'pattern' => 'simplestats/devicestats',
                'method'  => 'GET',
                'action'  => $wrapAction(function (): array {
                    [$from, $to] = Stats::getTimeSpanFromUrl();
                    return Stats::deviceStats($from, $to);
                })
            ],

            [
                'pattern' => 'simplestats/refererstats',
                'method'  => 'GET',
                'action'  => $wrapAction(function (): array {
                    [$from, $to] = Stats::getTimeSpanFromUrl();
                    return Stats::refererStats($from, $to);
                })
            ],

            [
                'pattern' => 'simplestats/visitors',
                'method'  => 'GET',
                'action'  => $wrapAction(fn(): array => Stats::getVisitors())
            ],

            [
                'pattern' => 'simplestats/onepagestats/(:all)',
                'method'  => 'GET',
                'action'  => $wrapAction(function ($any): array {
                    [$from, $to] = Stats::getTimeSpanFromUrl();

                    $page = page($any);
                    if(!$page) throw new Exception("The page doesn't exist !");

                    return [
                        'statsdata' => Stats::onePageStats($any, $from, $to),

                        // To replicate the section response, for compatibility
                        'label'         => t('simplestats.info.config.tracking.visits', "Page visits"),
                        'showFullInfo'  => true,
                        'showTotals'    => true,
                        'showTimeline'  => true,
                        'showLanguages' => true,
                        'size'          => 'huge',
                    ];
                })
            ],
        ];

        // Extra "admin" permissions for the "info" tab
        $user = $kirby->user();
        if($user && $user->hasSimpleStatsPanelAccess(true)){
            $apiRoutes[] = [
                'pattern' => 'simplestats/database/info',
                'method'  => 'GET',
                'action'  => $wrapAction(fn(): array => Stats::getDatabaseInfo())
            ];

            $apiRoutes[] = [
                'pattern' => 'simplestats/database/requirements',
                'method'  => 'GET',
                'action'  => $wrapAction(function (): array {
                    $versionArray = explode('.', kirby()->version());
                    $reqs = [
                        'php'    => kirby()->system()->php(),
                        'kirby' => ((int)$versionArray[0] === 4) || ((int)$versionArray[0] === 5),
                        'sqlite3'=> class_exists('SQLite3') && in_array('pdo_sqlite', get_loaded_extensions()) && in_array('sqlite3', get_loaded_extensions()),
                    ];

                    $dbRequirements = "PHP=" . ($reqs['php'] ? 'OK' : 'ERROR') . ', ';
                    $dbRequirements .= "SQLite3=" . ($reqs['sqlite3'] ? 'OK' : 'ERROR') . ', ';
                    $dbRequirements .= "Kirby=" . ($reqs['kirby'] ? 'OK' : 'ERROR') . ' --- --- --- ';
                    $dbRequirements .= 'PHP Extensions=' . implode(', ', get_loaded_extensions());

                    return [
                        'dbRequirements'       => $dbRequirements,
                        'dbRequirementsPassed' => $reqs['php'] && $reqs['kirby'] && $reqs['sqlite3'],
                    ];
                })
            ];

            $apiRoutes[] = [
                'pattern' => 'simplestats/database/upgrade',
                'method'  => 'GET',
                'action'  => $wrapAction(function (): array {
                    $result = Stats::checkUpgradeDatabase(false);
                    return [
                        'status'  => $result,
                        'message' => ($result ? 'Success! ' : 'Error! ') . I18n::translate('simplestats.info.database.upgrade.result'),
                    ];
                }, true), // admin only
            ];

            $apiRoutes[] = [
                'pattern' => 'simplestats/configinfo',
                'method'  => 'GET',
                'action'  => $wrapAction(function (): array {
                    $salt = option('daandelange.simplestats.tracking.salt', '');

                    return [
                        'period' => getTimeFrameUtility()->getPeriodAdjective(),
                        'since'  => 'todo', // todo
                        'unique' => option('daandelange.simplestats.tracking.uniqueSeconds', -1),
                        'salted' => is_string($salt) && !empty($salt) && $salt !== 'CHANGEME',

                        'features' => [
                            'referers'  => option('daandelange.simplestats.tracking.enableReferers', false),
                            'devices'   => option('daandelange.simplestats.tracking.enableDevices', false),
                            'visits'    => option('daandelange.simplestats.tracking.enableVisits', false),
                            'languages' => kirby()->multilang() && option('daandelange.simplestats.tracking.enableVisitLanguages', false),
                        ],

                        'ignored' => [
                            'roles'     => option('daandelange.simplestats.tracking.ignore.roles', []),
                            'pages'     => option('daandelange.simplestats.tracking.ignore.pages', []),
                            'templates' => option('daandelange.simplestats.tracking.ignore.templates', []),
                        ],

                        'logFile' => option('daandelange.simplestats.log.file', []),
                        'logLevels' => [
                            'tracking' => option('daandelange.simplestats.log.tracking', false),
                            'warnings' => option('daandelange.simplestats.log.warnings', false),
                            'verbose'  => option('daandelange.simplestats.log.verbose', false),
                        ]
                    ];
                })
            ];

            $apiRoutes[] = [
                'pattern' => 'simplestats/testers/user-agent',
                'method'  => 'GET',
                'action'  => $wrapAction(function () use ($getQueryParam): array {
                    $userAgent  = $getQueryParam('ua', $_SERVER['HTTP_USER_AGENT']);
                    $deviceInfo = SimpleStats::getDeviceInfo(['User-Agent' => $userAgent]);

                    if ($deviceInfo) {
                        $deviceKeys = ['engine', 'device', 'system'];
                        foreach ($deviceKeys as $key) {
                            if (isset($deviceInfo[$key])) {
                                $deviceInfo[$key] = Stats::translateDeviceKey($deviceInfo[$key]);
                            }
                        }
                    }

                    return ['userAgent'  => $userAgent, 'deviceInfo' => $deviceInfo];
                })
            ];

            $apiRoutes[] = [
                'pattern' => 'simplestats/testers/referer',
                'method'  => 'GET',
                'action'  => $wrapAction(function () use ($getQueryParam): array {
                    $referer     = $getQueryParam('url', $_SERVER['HTTP_REFERER']);
                    $refererInfo = SimpleStats::getRefererInfo(['Referer' => $referer]);

                    if (!$refererInfo) {
                        return ['error' => I18n::translate('simplestats.info.testers.referer.error')];
                    }

                    return $refererInfo;
                })
            ];

            $apiRoutes[] = [
                'pattern' => 'simplestats/testers/generatestats',
                'method'  => 'GET',
                'action'  => $wrapAction(function () use ($getQueryParam, $parseDateRange): array {
                    $mode = $getQueryParam('mode');
                    $from = $parseDateRange($getQueryParam('from'));
                    $to   = $parseDateRange($getQueryParam('to'));

                    if (!$mode) {
                        return ['error' => I18n::translate('simplestats.info.testers.generator.mode.error')];
                    }
                    if (!$from || !$to) {
                        return ['error' => I18n::translate('simplestats.info.testers.generator.date.error')];
                    }

                    return StatsGenerator::GenerateVisits($from, $to, $mode);
                }, true) // admin only
            ];
        }

        return $apiRoutes;
    }
];
