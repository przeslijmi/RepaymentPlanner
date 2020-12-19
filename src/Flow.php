<?php declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner;

use DateTime;

/**
 * One day of Flow (payment or repayment).
 */
class Flow
{

    /**
     * Date of flow.
     *
     * @var DateTime
     */
    private $date;

    /**
     * Amount of payment for this flow at this date.
     *
     * @var float
     */
    private $payment = 0.0;

    /**
     * Amount of payment for this flow at this date.
     *
     * @var float
     */
    private $repayment = 0.0;

    /**
     * Constructor.
     *
     * @param DateTime $date Date of flow.
     */
    public function __construct(DateTime $date)
    {

        // Save.
        $this->date = $date;
    }

    /**
     * Getter for date of flow.
     *
     * @return DateTime
     */
    public function getDate() : DateTime
    {

        return $this->date;
    }

    /**
     * Setter for payment amount.
     *
     * @param float $amount Amount of payment.
     *
     * @return self
     */
    public function setPayment(float $amount) : self
    {

        $this->payment = $amount;

        return $this;
    }

    /**
     * Adds payment amount to flow.
     *
     * @param float $amount Amount to be added to payment.
     *
     * @return self
     */
    public function addPayment(float $amount) : self
    {

        $this->payment += $amount;

        return $this;
    }

    /**
     * Getter for payment amount.
     *
     * @return float
     */
    public function getPayment() : float
    {

        return $this->payment;
    }

    /**
     * Setter for repayment amount.
     *
     * @param float $amount Amount of repayment.
     *
     * @return self
     */
    public function setRepayment(float $amount) : self
    {

        $this->repayment = $amount;

        return $this;
    }

    /**
     * Adds repayment amount to flow.
     *
     * @param float $amount Amount to be added to repayment.
     *
     * @return self
     */
    public function addRepayment(float $amount) : self
    {

        $this->repayment += $amount;

        return $this;
    }

    /**
     * Getter for repayment amount.
     *
     * @return float
     */
    public function getRepayment() : float
    {

        return $this->repayment;
    }

    /**
     * Getter for daily balance.
     *
     * @return float
     */
    public function getBalance() : float
    {

        return ( $this->payment - $this->repayment );
    }
}
