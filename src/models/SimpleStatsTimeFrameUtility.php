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

// todo: Date display should be customized to custom timespans

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

    // For displaying period-names in tables mostly (first seen, last seen, ...)
    // Date format is in date-fns format : https://date-fns.org/v2.17.0/docs/parse
    public function getPanelPeriodFormat() : string {
        return 'dd MMM yyyy'; // 26 Dec 2021
    }

    // Parse version date
    final public function getTimeFromVersionDate(int $monthyearday) : int {
        $stringPeriod = ''.$monthyearday;
        $year=intval(substr($stringPeriod, 0,4), 10);
        $month=intval(substr($stringPeriod, 4,2), 10);
        $day=intval(substr($stringPeriod, 6,2), 10);
        return mktime(0,0,0,$month,$day,$year);
    }

    // Returns the amount of timespans in-between two dates
    final public function getNumPeriodsFromDates(int $from, int $to) : int {
        if($to <= $from) return 0;
        $cntr = 0;
        for($cur = $from; $cur <= $to; $cur=incrementTime($cur)){
            $cntr++;
        }
        return $cntr;
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
        return $plural?t('simplestats.timeframe.week.plural','months'):t('simplestats.timeframe.week.singular','month');
    }
    public function getPeriodAdjective() : string {
        return t('simplestats.timeframe.week.name', 'Monthly');
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
    public function incrementTime($time, $steps=1) : int {
        // Monthly version
        //return $time + ((24*60*60) * $steps); // Quick method (unaware of dates)
        // Slow but accurate method.
        $month = date('m', $time)+$steps;
        $year = intval( date('Y', $time) + floor( ($month-1) / 12 ), 10);
        $month = intval((($month-1+abs($steps)*12)%12))+1; //
        return mktime(0,0,0,$month,1,$year);
    }
    public function getPanelPeriodFormat() : string{
        return 'MMM yyyy'; // Oct 2021
    }
}

// Weekly timespan handler
class SimpleStatsTimeFrameUtilityWeekly extends SimpleStatsTimeFrameUtility {
    public function getPeriodName(bool $plural=false) : string {
        return $plural?t('simplestats.timeframe.week.plural', 'weeks'):t('simplestats.timeframe.week.singular', 'week');
    }
    public function getPeriodAdjective() : string {
        return t('simplestats.timeframe.week.name','Weekly');
    }
    public function getTimeFromPeriod(int $period) : int {
        $year = substr(''.$period, 0,4);
        $week = substr(''.$period, 4,2);
        $time = false;
        if($week && $year){
            $time = strtotime($year.'W'.$week);
            return $time?$time:0;
        }
        return 0; // dangerous !
    }

    public function getPeriodFromTime( int $time = -1 ) : int {
        if($time < 0) $time = time();
        return intval(date('oW', $time ), 10);
    }
    public function incrementTime($time, $steps=1) : int {
        //$week = intval(date('W', $time),10)+$steps;
        //$year = intval( date('o', $time) + floor( ($week-1) / 53 ), 10);
        return strtotime(($steps<0?'-':'+').$steps.' week',$time);
        //return getTimeFromPeriod(getPeriodFromTime($newTime+7*24*3600)); // In some rare cases (26-10-2021) this causes an infinite loop : // time = 1635112800 = 2021-43 while 1635112800+1week = 2021-43 . Note : end of week is dayligt saving
        //return getTimeFromPeriod(getPeriodFromTime($time)+$steps));
    }
    public function getPanelPeriodFormat() : string{
        //return 'dd MMM yyyy'; // 26 Dec 2021
        return 'yyyy-w (MMM)'; // 2021-51 (Dec)
    }
}
