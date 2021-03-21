<?php
declare(strict_types=1);

namespace daandelange\SimpleStats;

use Kirby\Cms\App;

// Time / Period conversions

// Time refers to timestamp int values
// Periods refer to custom timespans, also having int values
// Note: Periods have to be convertible to ints so math operations can be done on them.
// For example, ( future > now > past ) must always be true.

// Changing these has not yet been tested
define('SIMPLESTATS_VERSION_DATE_FORMAT', 'Ymd'); // For "simplestats" versionning table
define('SIMPLESTATS_TABLE_DATE_FORMAT', 'Y-m-d');
define('SIMPLESTATS_PRECISE_DATE_FORMAT', 'Y-m-d H:i');
define('SIMPLESTATS_TIMELINE_DATE_FORMAT', 'Y-m-d');

// The base class that handles the precision of time frames
abstract class SimpleStatsTimeFrameUtility {
    // Abstract functions
    abstract public function getPeriodName(bool $plural=false) : string;
    abstract public function getPeriodAdjective() : string;
    abstract public function getTimeFromPeriod(int $period) : int;
    abstract public function getPeriodFromTime( int $time = 0 ) : int;
    abstract public function incrementTime($time, $steps=1) : int;

    // You don't have to override these, but you can make it faster by doing so.
    public function incrementPeriod($period, $steps=1) : int {
        return getPeriodFromTime( incrementTime( getTimeFromPeriod($period), $steps=1 ) );
    }
    public function getDateFromPeriod(int $period, string $dateformat='Y-m-d') : string {
        return date( $dateformat, getTimeFromPeriod($period) );
    }

    // Parse version date
    final public function getTimeFromVersionDate(int $monthyearday) : int {
        $stringPeriod = ''.$monthyearday;
        $year=intval(substr($stringPeriod, 0,4), 10);
        $month=intval(substr($stringPeriod, 4,2), 10);
        $day=intval(substr($stringPeriod, 6,2), 10);
        return mktime(0,0,0,$month,$day,$year);
    }
}

// Singleton & default option handler
function getTimeFrameUtility() : SimpleStatsTimeFrameUtility {
    static $utility = null;// = App::instance()->option('xxxx');
    if($utility){
        return $utility;
    }
    // Create singleton
    $utilityOption = App::instance()->option('daandelange.simplestats.tracking.timeFrameUtility');
    if( $utilityOption !== null ){
        // Load class directly from option
        if($utilityOption instanceof SimpleStatsTimeFrameUtility ){
            return $utility = $utilityOption;
        }
        // Allow to use a string in the config file
        elseif( is_string($utilityOption) ){
            if( $utilityOption == 'monthly' ){
                return $utility = new SimpleStatsTimeFrameUtilityMonthly();
            }
            elseif( $utilityOption == 'weekly' ){
                return $utility = new SimpleStatsTimeFrameUtilityWeekly();
            }
            // Unrecognized string
            else {
                $utilityOption = null;
            }
        }
        // Unrecognized setting
        else {
            $utilityOption = null;
        }
    }
    // Fallback
    // Log when the option is set, but not valid
    if($utilityOption !== null ) Logger::logWarning('Your timeframe utility setting is not valid. Falling back to the default monthly-precision.');
    // Return default
    return $utility = new SimpleStatsTimeFrameUtilityMonthly();
}

// Monthly timespan handler
class SimpleStatsTimeFrameUtilityMonthly extends SimpleStatsTimeFrameUtility {
    public function getPeriodName(bool $plural=false) : string {
        return $plural?'months':'month';
    }
    public function getPeriodAdjective() : string {
        return 'monthly';
    }
    public function getTimeFromPeriod(int $period) : int {
        // Monthly version
        $year=intval(substr(''.$period, 0,4));
        $month=intval(substr(''.$period, 4,2));
        return mktime(0,0,0,$month,1,$year);
    }
    public function getPeriodFromTime( int $time = -1 ) : int {
        if($time < 0) $time = time();

        // Monthly version
        return intval(date('Ym', $time), 10);
    }
    function incrementTime($time, $steps=1) : int {
        // Monthly version
        //return $time + ((24*60*60) * $steps); // Quick method (unaware of dates)
        // Slow but accurate method.
        $month = date('m', $time)+$steps;
        $year = intval( date('Y', $time) + floor( ($month-1) / 12 ), 10);
        $month = intval((($month-1+abs($steps)*12)%12))+1; //
        return mktime(0,0,0,$month,1,$year);
    }
}

// Weekly timespan handler
class SimpleStatsTimeFrameUtilityWeekly extends SimpleStatsTimeFrameUtility {
    public function getPeriodName(bool $plural=false) : string {
        return $plural?'week':'weeks';
    }
    public function getPeriodAdjective() : string {
        return 'weekly';
    }
    public function getTimeFromPeriod(int $period) : int {
        $year = substr(''.$period, 0,4);
        $week = substr(''.$period, 4,2);
        return strtotime($year.'W'.$week);
    }

    public function getPeriodFromTime( int $time = -1 ) : int {
        if($time < 0) $time = time();
        return intval(date('oW', $time ), 10);
    }
    function incrementTime($time, $steps=1) : int {
        //$week = intval(date('W', $time),10)+$steps;
        //$year = intval( date('o', $time) + floor( ($week-1) / 53 ), 10);
        return getTimeFromPeriod(getPeriodFromTime($time+(7*24*3600)*$steps));
    }
}
