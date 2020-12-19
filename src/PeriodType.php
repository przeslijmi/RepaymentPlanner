<?php declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner;

use DateTime;
use Exception;
use DateInterval;

/**
 * Period type (month/quarter/year) to use for calculations.
 */
class PeriodType
{

    /**
     * Type of period to use in installments.
     *
     * @var string
     */
    private $type;

    /**
     * Period interval for period type calculations.
     *
     * @var DateInterval
     */
    private $interval;

    /**
     * Constructor that creates period type (yearly, quarterly, monthly).
     *
     * @param string $type Period type to use (yearly, quarterly, monthly).
     *
     * @throws Exception When period type is inproper.
     * @return self
     */
    public function __construct(string $type)
    {

        // Throw.
        if (in_array($type, [ 'yearly',  'quarterly', 'monthly' ]) === false) {
            throw new Exception('PeriodOtosetException');
        }

        // Save type.
        if ($type === 'yearly') {
            $this->type = 'year';
        } elseif ($type === 'quarterly') {
            $this->type = 'quarter';
        } elseif ($type === 'monthly') {
            $this->type = 'month';
        }

        // Save interval.
        if ($this->type === 'year') {
            $this->interval = new DateInterval('P1Y');
        } elseif ($this->type === 'quarter') {
            $this->interval = new DateInterval('P3M');
        } elseif ($this->type === 'month') {
            $this->interval = new DateInterval('P1M');
        }

        return $this;
    }

    /**
     * Getter for interval.
     *
     * @return DateInterval
     */
    public function getInterval() : DateInterval
    {

        return $this->interval;
    }

    /**
     * Return number of possible periods in one year.
     *
     * @return integer
     */
    public function getPeriodsInYear() : int
    {

        if ($this->getType() === 'month') {
            return 12;
        } elseif ($this->getType() === 'quarter') {
            return 4;
        }

        return 1;
    }

    /**
     * Getter for type.
     *
     * @return string
     */
    public function getType() : string
    {

        return $this->type;
    }

    /**
     * Getter for period name of given date.
     *
     * @param string|DateTyime $date Date to analize.
     *
     * @return string
     */
    public function getNameForDate($date) : string
    {

        // Convert to string.
        if (is_a($date, DateTime::class) === true) {
            $date = $date->format('Y-m-d');
        }

        // Return name depending on type.
        if ($this->getType() === 'month') {
            return substr($date, 0, 4) . 'M' . substr($date, 5, 2);
        } elseif ($this->getType() === 'quarter') {
            return substr($date, 0, 4) . 'Q' . ceil(( substr($date, 5, 2) / 3 ));
        }

        return substr($date, 0, 4) . 'Y';
    }
}
