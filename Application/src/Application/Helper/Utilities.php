<?php
namespace Application\Helper;

class Utilities {
    public static function getYesterdayInterval()
    {
        return array('start'=>date("Y-m-d", strtotime("-1 day")),'end'=>date("Y-m-d"));
    }
    
    public static function getLastWeekInterval()
    {
        return array('start'=>date('N') == 1 ? date('Y-m-d',strtotime('last monday')) : date('Y-m-d',strtotime('last monday -7 days')),'end'=> date('Y-m-d',strtotime('last sunday')) );
    }
    
    public static function getLastMonthInterval()
    {
        return array('start'=>date("Y-m-d", mktime(0, 0, 0, date("m")-1, 1, date("Y"))),'end'=>date('Y-m-d', strtotime('last day of previous month')));
    }
    
    public static function getLastQuarterInterval()
    {
        return array('start'=>date("Y-m-d", mktime(0, 0, 0, date("m")-4, 1, date("Y"))),'end'=>date('Y-m-d', strtotime('last day of previous month')));
    }
    
    public static function getWeekToDateInterval()
    {
        return array('start'=>date('N') == 1 ? date('Y-m-d') : date('Y-m-d',strtotime('last monday')),'end'=>date("Y-m-d"));
    
    }
    
    public static function getMonthToDateInterval()
    {
        return array('start'=>date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y"))),'end'=>date("Y-m-d"));
    }
    
    public static function getLast7DaysInterval()
    {
        return array('start'=>date("Y-m-d", strtotime('6 days ago')),'end'=>date('Y-m-d'));
    }
    
    public static function getIntraweekInterval()
    {
        return array(
            'start1'=>date("Y-m-d", strtotime('13 days ago')),
            'end1'=>date('Y-m-d', strtotime('7 days ago')),
            'start2'=>date("Y-m-d", strtotime('6 days ago')),
            'end2'=>date('Y-m-d')
        );
    }

    
}