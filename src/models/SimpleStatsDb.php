<?php

declare(strict_types=1);

namespace daandelange\SimpleStats;

use SQLite3;
//use ErrorException;
use Throwable;

use Kirby\Database\Database;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Obj;
use Kirby\Cms\Dir;

// todo : make it exception safe
// $db->query() can throw errors !

// This class is inspired from bnomei/pageviewcounter/classes/PageViewCounterSQLite.php
class SimpleStatsDb
{
    private static $database = null; // Db singleton

    // Db version needed for this script
    const engineDbVersion = 2;
    // 1    From releasedate -> jan 2021     Initial version
    // 2    From jan 2021    -> jan 2021     Added language tracking, renamed some columns.

    public function __construct(){
        // Initialize db for later usage
        //if( $this->database == null ){}
    }

    protected static function database(): Database {
        if(self::$database == null){
            self::createDBInstance();
        }
        return self::$database;
    }

    private static function createDBInstance() : bool {
        $target = self::getConfigPath();

        // Initially create the db, if it doesn't exist yet.
        if (!F::exists($target)) {

            // Ensure the folder exists
            $dir = dirname($target);
            if (is_dir($dir) === false) {
                Dir::make($dir);
            }

            // Create db file
            try {
                $db = new SQLite3($target);
            } catch (Throwable $e) {
                Logger::logWarning('Error creating SQLite3 instance. Error='.$e->getMessage());
                return false;
            }

            // pre-compute languages string
            $langKeys = '';
            if( !kirby()->multilang() ){
                $langKeys = '`hits_en` INTEGER';
            }
            else {
                foreach( kirby()->languages() as $language ){
                    if( strlen($langKeys) > 0 ) $langKeys .= ', ';
                    $langKeys .= '`hits_'.$language->code().'` INTEGER';
                }
            }

            // Create db structure
            // todo: rename *.monthyear         --> periodid (except pagevisitors)
            $db->exec("CREATE TABLE IF NOT EXISTS `pagevisitors` (`userunique` TEXT primary key unique, `timeregistered` INTEGER, `osfamily` TEXT, `devicetype` TEXT, `browserengine` TEXT, `visitedpages` TEXT)");
            //$db->exec("CREATE TABLE IF NOT EXISTS `languagevisits` (`id` INTEGER primary key unique, ${$langKeys}");
            $db->exec("CREATE TABLE IF NOT EXISTS `referers` (`id` INTEGER primary key unique, `referer` TEXT, `domain` TEXT, `medium` TEXT, `monthyear` INTEGER, `hits` INTEGER)");
            $db->exec("CREATE TABLE IF NOT EXISTS `pagevisits` (`id` INTEGER primary key unique, `uid` TEXT, `monthyear` INTEGER, `hits` INTEGER, ${langKeys})");
            $db->exec("CREATE TABLE IF NOT EXISTS `devices` (`id` INTEGER primary key unique, `device` TEXT, `monthyear` INTEGER, `hits` INTEGER)");
            $db->exec("CREATE TABLE IF NOT EXISTS `engines` (`id` INTEGER primary key unique, `engine` TEXT, `monthyear` INTEGER, `hits` INTEGER)");
            $db->exec("CREATE TABLE IF NOT EXISTS `systems` (`id` INTEGER primary key unique, `system` TEXT, `monthyear` INTEGER, `hits` INTEGER)");
            $db->exec("CREATE TABLE IF NOT EXISTS `simplestats` (`id` INTEGER primary key unique, `version` INTEGER, `migrationdate` INTEGER)");
            $db->exec("INSERT INTO `simplestats` (`id`, `version`, `migrationdate`) VALUES (NULL, ".self::engineDbVersion.", ".date('Ymd').")");
            $db->close();


            // Double check
            if(!F::exists($target)){
                Logger::LogWarning("Error creating database @ ${target} ! SimpleStats will not work.");
                return false;
            }
            else {
                Logger::LogNotice("Successfully created a new SimpleStats database v_".self::engineDbVersion." @ ${target} !");
            }
        }

        // Initialize db for later usage
        if( self::$database == null ){
            try {
                self::$database = new Database([
                    'type' => 'sqlite',
                    'database' => $target,
                ]);
            } catch (Throwable $e) {
                Logger::logWarning('Error creating the DataBase singleton. Error='.$e->getMessage());
                //self::$database=null;
            }

            if(self::$database===null){
                Logger::LogWarning("Error loading database @ ${target} ! SimpleStats will not work.");
                return false;
            }
        }

        // Tmp here, to be ran from the panel
        //self::checkUpgradeDatabase(false);

        return true;
    }

    public static function getConfigPath() : String {
        // Get DB file
        $target = option('daandelange.simplestats.tracking.database', false);

        // Override the setting if it aint an .sqlite file
        if( !$target || F::extension($target)!='sqlite'){
            // Todo: make this use root('logs')
            $target = kirby()->root('config').'/../logs/simplestats.sqlite';
            Logger::LogVerbose("Config --> db file replaced by default = ${target}.");
        }
        return $target;
    }

    public static function checkUpgradeDatabase( bool $dryRun = true ) : bool {
        $ret = true;
        // Update old databases ?
        if( $db = self::database() ){

            // Compare db version with software version, update if needed
            $dbVersionQ = $db->query("SELECT * FROM `simplestats` ORDER BY `version` DESC LIMIT 10");
            if(!$dbVersionQ){
                // v1 didn't have the simplestats table (only way to detect)
                if( stripos($db->lastError()->getMessage(), 'no such table:') !== false && stripos($db->lastError()->getMessage(), 'simplestats') !== false ){

                    if($dryRun){
                        // todo
                    }
                    // Upgrade
                    else {
                        $target = self::getConfigPath();

                        // SQL
                        $dbsql3 = new SQLite3($target);

                        // Create simplestats version table (since v2)
                        if(
                            !$dbsql3->exec("CREATE TABLE IF NOT EXISTS `simplestats` (`id` INTEGER primary key unique, `version` INTEGER, `migrationdate` INTEGER)") ||
                            !$dbsql3->exec("INSERT INTO `simplestats` (`id`, `version`, `migrationdate`) VALUES (NULL, ".self::engineDbVersion.", ".date('Ymd').")")
                        ){
                            Logger::LogWarning("UPGRADE from db v1 to v2+ FAILED creating the simplestats table. Error=".$dbsql3->lastErrorMsg() );
                            $dbsql3->close();
                            return false;
                        }
                        else {
                            Logger::LogNotice("UPGRADE from db v1 to v2+ COMPLETE !");

                            // Languages were also added in v2, but the global lang check will fix that
                        }

                        $dbsql3->close();

                        // todo: check if $db needs to reload the modified $dbsql3 ?
                        // re-query db to continue
                        $dbVersionQ = $db->query("SELECT `version` FROM `simplestats` ORDER BY `version` DESC LIMIT 1");
                    }
                }
            }
            // Still failed ?
            if(!$dbVersionQ){
                if($dryRun){
                    // todo
                }
                else {
                    Logger::LogWarning("Could not verify existing db version. Error=".$db->lastError()->getMessage());
                }
                $ret = false;
                //return false;// Don't return, global checks cans still proceed
            }
            // From here we have a version number, at least v2
            else{

                if($dbVersionQ->isNotEmpty()){
                    $dbVersion = intval($dbVersionQ->first()->version, 10);
                    // Compare version
                    if( self::engineDbVersion !== $dbVersion ){
                        Logger::LogVerbose("Upgrade : Detected 2 different db versions ! [".self::engineDbVersion."/".$dbVersion."] Starting...");

                        // Upgrade processes
                        //if( $dbVersion->version < 3){$ret=true;}
                        $ret=false;
                    }
                    else {
                        Logger::LogVerbose("Upgrade : Versions are identical :)");
                    }
                }
                else {
                    Logger::LogWarning("Could not verify existing db version : Error parsing the version number.");
                    $ret=false;
                }
            }

            // General checks, version independant

            // check if all current website languages have their columns in the pagevisits table
            // (Used when the amount of website languages changes)
            if( $langsQ = $db->query("SELECT * FROM `pagevisits` LIMIT 1") ){ // or use "PRAGMA table_info(pagevisits);" ?

                if( $langsQ->isNotEmpty() ){
                    $langs = $langsQ->first()->toArray();
                    $missingLangs = [];

                    // Compose missing langs
                    if( !kirby()->multilang() ){
                        if( array_key_exists('hits_en', $langs) ){
                            $missingLangs[]='en';
                        }
                    }
                    else {
                        foreach( kirby()->languages() as $language ){
                            if( !array_key_exists('hits_'.$language->code(), $langs) ){
                                $missingLangs[]=$language->code();
                            }
                        }
                    }

                    // Add any missing
                    if(count($missingLangs)>0){

                        if($dryRun){
                            // todo
                        }
                        // Upgrade langs
                        else {
                            $target = self::getConfigPath();
                            Logger::LogVerbose('UPGRADE db, adding LANGUAGES '.implode(', ', $missingLangs).' to pagevisits.');

                            // SQL
                            $dbsql3 = new SQLite3($target);

                            foreach($missingLangs as $l){
                                // Note : ALTER TABLE cannot add several columns in 1 command.
                                if( !$dbsql3->exec('ALTER TABLE `pagevisits` ADD COLUMN `hits_'.$l.'` INTEGER') ){
                                    Logger::LogWarning("UPGRADE db, adding LANGUAGES FAILED creating columns for ${l}. Error=".$dbsql3->lastError()->getMessage());
                                    $dbsql3->close();
                                    //return true;
                                    $ret = false;
                                }
                                else {
                                    Logger::LogNotice("UPGRADE db ADDED LANGUAGE = ${l}.");
                                }
                            }
                            $dbsql3->close();
                        }
                    }

                }
                else{
                    Logger::LogWarning("No pagevisits entries yet, cannot update db !");
                    $ret=false;
                }
            }
            else {
                Logger::LogWarning("Db Upgrade, could not check languages! Error=".$dbsql3->lastError()->getMessage());
                $ret = false;
            }
        }
        return $ret;
    }

}
