<?php declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner;

use DateTime;
use Exception;
use DateInterval;
use Przeslijmi\RepaymentPlanner\Installment;

/**
 * Collection of Installment objects - part of Schedule.
 */
class Installments
{

    /**
     * Parent Schedule object.
     *
     * @var Schedule
     */
    private $schedule;

    /**
     * Array with Installments.
     *
     * @var Installment[]
     */
    private $installments = [];

    private $globalDiff = 0.0;
    private $firstCapitalPossible = 0.0;

    /**
     * Constructor.
     *
     * @param Schedule $schedule Parent schedule object.
     */
    public function __construct(Schedule $schedule)
    {

        // Save.
        $this->schedule = $schedule;

        // Create empty installments.
        $this->reset();
    }

    /**
     * Getter for Parent Schedule object.
     *
     * @return Schedule
     */
    public function getSchedule() : Schedule
    {

        return $this->schedule;
    }

    /**
     * Getter for all Installments.
     *
     * @return Installment[]
     */
    public function getAll() : array
    {

        return $this->installments;
    }

    /**
     * Return Installment for given date.
     *
     * @param DateTime $date Date to analize.
     *
     * @throws Exception When no Installment found for this date.
     * @return Installment
     */
    public function getInstallmentForDate(DateTime $date) : Installment
    {

        // Search.
        foreach ($this->installments as $installment) {

            if ($date >= $installment->getPeriod()->getFirstDay()
                && $date <= $installment->getPeriod()->getLastDay()
            ) {
                return $installment;
            }
        }

        throw new Exception('InstallmentDonoexException');
    }

    /**
     * Return Installment for given period name.
     *
     * @param string $periodName Name of period, eg `2020M01`.
     *
     * @return Installment
     */
    public function getInstallmentForPeriodName(string $periodName) : Installment
    {

        return $this->installments[$periodName];
    }

    /**
     * Gets period object.
     *
     * @return string
     */
    public function getPeriod() : string
    {

        return $this->period;
    }

    /**
     * Return length of Installment array.
     *
     * @return integer
     */
    public function length() : int
    {

        return count($this->installments);
    }

    /**
     * Get sum of all interests payed.
     *
     * @return float
     */
    public function getSumOfInterests() : float
    {

        // Lvd.
        $sum = 0.0;

        // Sum.
        foreach ($this->installments as $installment) {
            $sum += $installment->getInterests();
        }

        return $sum;
    }

    /**
     * Get sum of all capitals payed.
     *
     * @return float
     */
    public function getSumOfCapital() : float
    {

        // Lvd.
        $sum = 0.0;

        // Sum.
        foreach ($this->installments as $installment) {
            $sum += $installment->getCapital();
        }

        return $sum;
    }

    /**
     * Recreate all empty Installments.
     *
     * @return self
     */
    private function reset() : self
    {

        // Clear all installments.
        $this->installments = [];

        // Add first installment.
        $this->addInstallment(new Installment($this, $this->schedule->getStart()));

        // Clone date.
        $date = clone $this->schedule->getStart();

        // Go through the rest.
        do {

            // Move date.
            $date->add($this->schedule->getPeriodType()->getInterval());

            // Add next installments until schedule deadline is exceeded.
            try {
                $this->addInstallment(new Installment($this, $date));
            } catch (Exception $exc) {
                break;
            }
        } while (true);

        return $this;
    }

    /**
     * Adds one Intallment.
     *
     * @param Installment $installment Installment to be added.
     *
     * @todo   Check for overwriting.
     * @return self
     */
    private function addInstallment(Installment $installment) : self
    {

        // Lvd.
        $name = $installment->getPeriod()->getName();

        // Save.
        $this->installments[$name] = $installment;

        // Set order.
        $installment->setOrder(count($this->installments));

        return $this;
    }

    public function addToGlobalDiff(float $amount) : self
    {

        $this->globalDiff += $amount;

        return $this;
    }

    public function getGlobalDiff() : float
    {

        return $this->globalDiff;
    }

    public function addToFirstCapitalPossible(float $amount) : self
    {

        $this->firstCapitalPossible += $amount;

        return $this;
    }

    public function clearFirstCapitalPossible() : float
    {

        $result = $this->firstCapitalPossible;

        $this->firstCapitalPossible = 0.0;

        return $result;
    }
}
