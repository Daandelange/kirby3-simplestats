<?php
declare(strict_types=1);

namespace daandelange\SimpleStats;

//use Kirby\Database\Database;
//use Kirby\Toolkit\Collection;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Obj;

// Simple interface for logging
class Logger extends SimpleStatsDb {

    public static function log($message): bool {
        if(option('daandelange.simplestats.log', false)===false) return true;

        $file = option('daandelange.simplestats.log.file', kirby()->root('config') . '/../logs/simplestats_errors.txt');
        if(!$file) return false;

        return @error_log(date('[Y-m-d H:i] ').$message."\n", 3, $file);
    }

    public static function logWarning($message): bool {
        if(option('daandelange.simplestats.log.warning')===false) return true;
        return self::log('[Warning ] '.$message);
    }
    public static function logTracking($message): bool {
        if(option('daandelange.simplestats.log.tracking')===false) return true;
        return self::log('[Tracking] '.$message);
    }
    public static function logVerbose($message): bool {
        if(option('daandelange.simplestats.log.verbose')===false) return true;
        return self::log('[Verbose ] '.$message);
    }

}
