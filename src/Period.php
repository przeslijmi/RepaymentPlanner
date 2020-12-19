<?php declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner;

use DateTime;
use Exception;
use DateInterval;
use Przeslijmi\RepaymentPlanner\Schedule;

/**
 * One real period of Schedule.
 */
class Period
{

    /**
     * Parent Schedule object.
     *
     * @var Schedule
     */
    private $schedule;

    /**
     * Name of period (eg. 2020M02).
     *
     * @var string
     */
    private $name;

    /**
     * First day of period (not earlier than first day of schedule).
     *
     * @var DateTime
     */
    private $firstDay;

    /**
     * Last day of period (not later than first day of schedule).
     *
     * @var DateTime
     */
    private $lastDay;

    /**
     * Next day after last day of period.
     *
     * @var DateTime
     */
    private $lastDayNext;

    /**
     * Number of days in period.
     *
     * @var integer
     */
    private $length;

    /**
     * Percentage of schedule period in whole, calendar period.
     *
     * @var float
     */
    private $percOfLength;

    /**
     * Percentage of whole, calendar period in year.
     *
     * @var float
     */
    private $percOfYear;

    /**
     * Constructor.
     *
     * @param Schedule $schedule Parent Schedule object.
     * @param DateTime $anyDate  Any date inside period.
     *
     * @throws Exception When period is inproper.
     * @return self
     */
    public function __construct(Schedule $schedule, DateTime $anyDate)
    {

        // Save.
        $this->schedule = $schedule;

        // Lvd.
        $date    = explode('-', $anyDate->format('Y-m-d'));
        $date[0] = (int) $date[0];
        $date[1] = (int) $date[1];
        $date[2] = (int) $date[2];
        $period  = $this->schedule->getPeriodType();

        // Real start and stop.
        $mktimeFirstStart = (int) $this->schedule->getStart()->format('U');
        $mktimeLastEnd    = (int) $this->schedule->getEnd()->format('U');

        if ($period->getType() === 'month') {

            // Get time.
            $mktimeWholeStart = mktime(0, 0, 0, $date[1], 1, $date[0]);
            $mktimeWholeEnd   = mktime(0, 0, 0, ( $date[1] + 1 ), 0, $date[0]);
            $mktimeStart      = max($mktimeFirstStart, $mktimeWholeStart);
            $mktimeEnd        = min($mktimeLastEnd, $mktimeWholeEnd);
            $percOfYear       = ( 1 / 12 );

            // Get name.
            $name = date('Y', $mktimeStart) . 'M' . date('m', $mktimeStart);

        } elseif ($period->getType() === 'quarter') {

            // Get time.
            $firstMonth       = (int) ( ( ( floor(( $date[1] - 1 ) / 3) ) * 3 ) + 1 );
            $mktimeWholeStart = mktime(0, 0, 0, $firstMonth, 1, $date[0]);
            $mktimeWholeEnd   = mktime(0, 0, 0, ( $firstMonth + 3 ), 0, $date[0]);
            $mktimeStart      = max($mktimeFirstStart, $mktimeWholeStart);
            $mktimeEnd        = min($mktimeLastEnd, $mktimeWholeEnd);
            $percOfYear       = ( 1 / 4 );

            // Get name.
            $name = date('Y', $mktimeStart) . 'Q' . ( floor(( $firstMonth / 3 )) + 1 );

        } elseif ($period->getType() === 'year') {

            // Get time.
            $mktimeWholeStart = mktime(0, 0, 0, 1, 1, $date[0]);
            $mktimeWholeEnd   = mktime(0, 0, 0, 12, 31, $date[0]);
            $mktimeStart      = max($mktimeFirstStart, $mktimeWholeStart);
            $mktimeEnd        = min($mktimeLastEnd, $mktimeWholeEnd);
            $percOfYear       = 1;

            // Get name.
            $name = date('Y', $mktimeStart) . 'Y';

        } else {
            throw new Exception('UnknownPeriodTypeException');
        }//end if

        // Save.
        $this->name         = $name;
        $this->firstDay     = new DateTime(date('Y-m-d', $mktimeStart));
        $this->lastDay      = new DateTime(date('Y-m-d', $mktimeEnd));
        $this->lastDayNext  = new DateTime(date('Y-m-d', ( $mktimeEnd + ( 60 * 60 * 24 ) )));
        $this->length       = (int) ( round(( ( $mktimeEnd - $mktimeStart ) / ( 60 * 60 * 24 ) )) + 1 );
        $this->percOfLength = (float) ( ( $mktimeEnd - $mktimeStart ) / ( $mktimeWholeEnd - $mktimeWholeStart ) );
        $this->percOfYear   = (float) $percOfYear;

        if ($this->firstDay > $this->lastDay) {
            throw new Exception('PeriodIsBeyondPlan');
        }
    }

    /**
     * Getter for name of period (eg. 2020M02).
     *
     * @return string
     */
    public function getName() : string
    {

        return $this->name;
    }

    /**
     * Getter for first day of period (not earlier than first day of schedule).
     *
     * @return DateTime
     */
    public function getFirstDay() : DateTime
    {

        return $this->firstDay;
    }

    /**
     * Getter for last day of period (not later than first day of schedule).
     *
     * @return DateTime
     */
    public function getLastDay() : DateTime
    {

        return $this->lastDay;
    }

    /**
     * Getter for next day after last day of period.
     *
     * @return DateTime
     */
    public function getLastDayNext() : DateTime
    {

        return $this->lastDayNext;
    }

    /**
     * Getter for number of days in period.
     *
     * @return integer
     */
    public function getLength() : int
    {

        return $this->length;
    }

    /**
     * Getter for percentage of schedule period in whole, calendar period.
     *
     * @return float
     */
    public function getPercOfLength() : float
    {

        return $this->percOfLength;
    }

    /**
     * Getter for percentage of whole, calendar period in year.
     *
     * @return float
     */
    public function getPercOfYear() : float
    {

        return $this->percOfYear;
    }

    public function getDaysInYear() : int
    {

        return ( 337 + date('t', mktime(0, 0, 0, 2, 1, (int) $this->firstDay->format('Y'))) );
    }
}
