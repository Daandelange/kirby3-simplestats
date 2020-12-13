<?php

declare(strict_types=1);

namespace daandelange\SimpleStats;

use Kirby\Database\Database;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Obj;

// This class is inspired from bnomei/pageviewcounter/classes/PageViewCounterSQLite.php
class SimpleStatsDb
{
    private $database = null;

    public function __construct(){
        // Get DB file
        $target = option('daandelange.simplestats.tracking.database', false);

        // Override the setting if it aint an .sqlite file
        if( !$target || F::extension($target)!='sqlite'){//!F::is($target, 'sqlite') ){
            $target = kirby()->root('config').'/../logs/simplestats.sqlite';
            Logger::LogVerbose("Config --> db file replaced by default = ${target}.");
        }

        // Initially create the db, if it doesn't exist yet.
        if (!F::exists($target)) {
            $db = new \SQLite3($target);

            $db->exec("CREATE TABLE IF NOT EXISTS `pagevisitors` (userunique TEXT primary key unique, timeregistered INTEGER, osfamily TEXT, devicetype TEXT, browserengine TEXT, visitedpages TEXT)");
            $db->exec("CREATE TABLE IF NOT EXISTS `referers` (id INTEGER primary key unique, referer TEXT, domain TEXT, monthyear INTEGER, hits INTEGER)");
            $db->exec("CREATE TABLE IF NOT EXISTS `pagevisits` (id INTEGER primary key unique, uid TEXT, monthyear INTEGER, hits INTEGER)");
            $db->exec("CREATE TABLE IF NOT EXISTS `devices` (`id` INTEGER primary key unique, `device` TEXT, `monthyear` INTEGER, `hits` INTEGER)");
            $db->exec("CREATE TABLE IF NOT EXISTS `engines` (`id` INTEGER primary key unique, `engine` TEXT, `monthyear` INTEGER, `hits` INTEGER)");
            $db->exec("CREATE TABLE IF NOT EXISTS `systems` (`id` INTEGER primary key unique, `system` TEXT, `monthyear` INTEGER, `hits` INTEGER)");
            $db->close();

            // Double check
            if(!F::exists($target)){
                Logger::LogWarning("Error creating database @ ${target} ! SimpleStats will not work.");
            }
        }

        // Initialize db for later usage
        if( $this->database == null ){
            $this->database = new Database([
                'type' => 'sqlite',
                'database' => $target,
            ]);
            if($this->database===null){
                Logger::LogWarning("Error loading database @ ${target} ! SimpleStats will not work.");
            }
        }
    }

    public function database(): Database {
        return $this->database;
    }

    private static $singleton;

    public static function singleton(array $options = []){
        if (!self::$singleton) {
            self::$singleton = new self($options);
        }

        return self::$singleton;
    }
}
