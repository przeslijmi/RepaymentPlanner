<?php declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner;

use DateTime;
use Exception;
use DateInterval;

/**
 * One Installment of Schedule.
 */
class Installment
{

    /**
     * Parent object, Collection of installments.
     *
     * @var Installments
     */
    private $collection;

    /**
     * Period of this Installment.
     *
     * @var Period
     */
    private $period;

    /**
     * Order number of this Installment in Schedule.
     *
     * @var integer
     */
    private $order = 0;

    /**
     * Amount of interests in Installment.
     *
     * @var float
     */
    private $interests = 0.0;

    /**
     * Amount of capital in Installment.
     *
     * @var float
     */
    private $capital = 0.0;

    /**
     * Constructor.
     *
     * @param Installments $collection Parent object, Collection of installments.
     * @param DateTime     $someDate   Any date inside this Installment period.
     */
    public function __construct(Installments $collection, DateTime $someDate)
    {

        // Lvd.
        $this->collection = $collection;
        $this->period     = new Period($this->collection->getSchedule(), $someDate);
    }

    /**
     * Setter for order number of this Installment in Schedule.
     *
     * @param integer $order Order to be set.
     *
     * @return self
     */
    public function setOrder(int $order) : self
    {

        $this->order = $order;

        return $this;
    }

    /**
     * Getter of order number of this Installment in Schedule.
     *
     * @return integer
     */
    public function getOrder() : int
    {

        return $this->order;
    }

    /**
     * Getter for Period of this Installment.
     *
     * @return Period
     */
    public function getPeriod() : Period
    {

        return $this->period;
    }

    /**
     * Getter for amount of interests in Installment.
     *
     * @return float
     */
    public function getInterests() : float
    {

        return (float) round($this->interests, 2);
    }

    public function isThisTheLastOne() : bool
    {

        // Lvd.
        $thisEndDate     = $this->period->getLastDay()->format('Y-m-d');
        $scheduleEndDate = $this->collection->getSchedule()->getEnd()->format('Y-m-d');

        return ($thisEndDate === $scheduleEndDate);
    }

    public function calc() : self
    {

        // Lvd.
        $schedule   = $this->collection->getSchedule();
        $period     = $this->getPeriod();
        $this->setInterests(0);

        // Divide installment into ticks in which there was a change of engagement or rates.
        $ticks = $schedule->getRatesAndEngagementsBetween(
            $this->getPeriod()->getFirstDay(),
            $this->getPeriod()->getLastDay()
        );

        // var_dump($ticks);
        // die;

        foreach ($ticks as $tick) {


            if ($schedule->getRepaymentsStyle() === 'annuit' && $schedule->getIsCalcDaily() === true) {

                // Get non-daily interests.
                $rate     = ( $tick['annualRate'] * $period->getPercOfYear() * $tick['percentage'] );
                $nonDaily = round(( $tick['engagement'] * $rate ), 2);

                // Get daily interests.
                $rate  = (( $tick['annualRate'] / $period->getDaysInYear() ) * $period->getLength() * $tick['percentage'] );
                $daily = round(( $tick['engagement'] * $rate ), 2);

                $diffOnInterests = ( $nonDaily - $daily );

                $this->collection->addToGlobalDiff($diffOnInterests);

                $this->addToInterests($daily);

                if ($period->getLastDay()->format('Y-m-d') > $schedule->getFirstRepaymentDate()->format('Y-m-d')) {

                    $firstCapitalPossible = $this->collection->clearFirstCapitalPossible();

                    $this->addToCapital(( $diffOnInterests + $firstCapitalPossible ));
                    $this->addToInterests(-$firstCapitalPossible);

                } else {
                    $this->collection->addToFirstCapitalPossible($diffOnInterests);
                }

                // var_dump($diffOnInterests);
                // die;

            } else {

                // Get rate.
                if ($schedule->getIsCalcDaily() === true) {
                    $rate = (( $tick['annualRate'] / $period->getDaysInYear() ) * $period->getLength() * $tick['percentage'] );
                } else {
                    $rate = ( $tick['annualRate'] * $period->getPercOfYear() * $tick['percentage'] );
                }

                // Calc interests.
                $interests = ( $tick['engagement'] * $rate );
                $this->addToInterests($interests);
            }
        }

        // Add capitals.
        foreach ($schedule->getFlows() as $flow) {
            if ($flow->getRepayment() > 0
                && $flow->getDate() >= $period->getFirstDay()
                && $flow->getDate() <= $period->getLastDay()
            ) {
                $this->addToCapital($flow->getRepayment());
            }
        }

        if ($this->isThisTheLastOne() === true && (float) $this->collection->getGlobalDiff() !== (float) 0) {
            $this->addToCapital(( -1 * $this->collection->getGlobalDiff() ));
        }

        return $this;
    }

    /**
     * Adder to amount of interests in Installment.
     *
     * @param float $amount Amount of interests in Installment to be added.
     *
     * @return self
     */
    public function addToInterests(float $amount) : self
    {

        $this->interests += $amount;

        return $this;
    }

    /**
     * Setter of amount of interests in Installment.
     *
     * @param float $amount Amount of interests in Installment.
     *
     * @return self
     */
    public function setInterests(float $amount) : self
    {

        $this->interests = $amount;

        return $this;
    }

    /**
     * Getter for amount of capital in Installment.
     *
     * @return float
     */
    public function getCapital() : float
    {

        return (float) round($this->capital, 2);
    }

    /**
     * Adder to amount of capital in Installment.
     *
     * @param float $amount Amount of capital in Installment to be added.
     *
     * @return self
     */
    public function addToCapital(float $amount) : self
    {

        $this->capital += $amount;

        return $this;
    }

    /**
     * Setter of amount of capital in Installment.
     *
     * @param float $amount Amount of capital in Installment.
     *
     * @return self
     */
    public function setCapital(float $amount) : self
    {

        $this->capital = $amount;

        return $this;
    }

    /**
     * Getter for whole Installment amount (interests and capitals combined).
     *
     * @return float
     */
    public function getWhole() : float
    {

        return (float) ( round($this->interests, 2) + round($this->capital, 2) );
    }
}
