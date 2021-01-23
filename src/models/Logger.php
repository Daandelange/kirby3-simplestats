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

        $file = option('daandelange.simplestats.log.file', self::getLogsPath('simplestats_errors.txt'));
        if(!$file) return false;

        // tmp, debug backtrace
        $traceInfo="";
        if(false){
            $bt = debug_backtrace(0,5);
            for($i=0; $i < 10 && $i < sizeof($bt); $i++ ){
                if( isset($bt[$i]['file']) && strpos($bt[$i]['file'], __FILE__)!==false ) continue; // exclude this file
                $traceInfo.="in :";
                if(isset($bt[$i]['file'])) $traceInfo .= basename($bt[$i]['file']);
                if(isset($bt[$i]['line'])) $traceInfo .= ' (line '.$bt[$i]['line'].')';
                if(isset($bt[$i]['function'])) $traceInfo .= ' (function '.$bt[$i]['function'].')';
                if(isset($bt[$i]['class'])) $traceInfo .= ' (class '.$bt[$i]['class'].')';
                if(isset($bt[$i]['object'])) $traceInfo .= ' (object '.$bt[$i]['class'].')';
                $traceInfo.="\n";
                break; // Only show the first stack trace
            }
            $traceInfo.="\n";
        }

        return @error_log(date('[Y-m-d H:i] ').$message."\n".$traceInfo, 3, $file);
    }

    public static function logWarning($message): bool {
        if(option('daandelange.simplestats.log.warning')===false) return true;
        return self::log('[Warning] '.$message);
    }
    public static function logTracking($message): bool {
        if(option('daandelange.simplestats.log.tracking')===false) return true;
        return self::log('[Tracking] '.$message);
    }
    public static function logNotice($message): bool {
        if(option('daandelange.simplestats.log.verbose')===false) return true;
        return self::log('[Notice] '.$message);
    }
    public static function logVerbose($message): bool {
        if(option('daandelange.simplestats.log.verbose')===false) return true;
        return self::log('[Verbose] '.$message);
    }

}
