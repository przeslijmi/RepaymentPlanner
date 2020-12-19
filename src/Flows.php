<?php declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner;

use DateTime;
use DateInterval;

/**
 * Parent of Schedule object extending its methods with Flows Collection methods.
 */
abstract class Flows
{

    /**
     * Flows, ie. payments and repayments.
     *
     * @var Flow[]
     */
    protected $flows = [];

    public function getFlows() : array
    {

        return $this->flows;
    }

    /**
     * Sets repayment to be of balloon style.
     *
     * @return self
     */
    public function setRepaymentsBalloonStyle() : self
    {

        // Set.
        $this->repaymentsStyle = 'balloon';

        // Clear all repayments.
        $this->clearRepayments();

        // Add correction - which in fact will be a baloon payment at the end.
        $this->addRepaymentsCorrectionsForLastDay();

        return $this;
    }

    /**
     * Sets repayment to be of linear style.
     *
     * @return self
     */
    public function setRepaymentsLinearStyle() : self
    {

        // Set.
        $this->repaymentsStyle = 'linear';

        // Clear all repayments.
        $this->clearRepayments();

        // Lvd.
        $installments = $this->getInstallments()->length();

        // Sum all payments.
        foreach ($this->flows as $flow) {

            // Ignore nonpayment flows (don't calc repayments for flows without payment).
            if ($flow->getPayment() === 0.0) {
                continue;
            }

            // Get first date of repayment.
            if ($this->getFirstRepaymentDate() === null) {
                $firstRepaymentDate = $flow->getDate();
            } else {
                $firstRepaymentDate = max($flow->getDate(), $this->getFirstRepaymentDate());
            }

            // Calc sum.
            $firstRepaymentPeriod = $this->getInstallments()->getInstallmentForDate($firstRepaymentDate);
            $periodsLeft          = ( $installments - $firstRepaymentPeriod->getOrder() + 1 );
            $repaymentAmount      = round(( $flow->getPayment() / $periodsLeft ), 2);

            foreach ($this->getInstallments()->getAll() as $installment) {
                if ($installment->getPeriod()->getName() >= $firstRepaymentPeriod->getPeriod()->getName()) {
                    $this->addRepayment($installment->getPeriod()->getLastDay(), $repaymentAmount);
                }
            }
        }//end foreach

        // Add corrections at the end.
        $this->addRepaymentsCorrectionsForLastDay();

        return $this;
    }

    /**
     * Sets repayment to be of annuit style.
     *
     * @return self
     */
    public function setRepaymentsAnnuitStyle(int $decimalPlaces = 2) : self
    {

        // Set.
        $this->repaymentsStyle = 'annuit';

        // Clear all repayments.
        $this->clearRepayments();
        $this->calcEngagements();

        // Lvd.
        $installments  = $this->getInstallments()->length();
        $sumOfPayments = 0.0;
        $flowDates     = array_keys($this->flows);

        // Sum all payments.
        foreach ($flowDates as $noFlow => $date) {

            // Lvd.
            $nextFlowDate = ( $flowDates[(++$noFlow)] ?? null );
            $thisFlowDate = $date;
            $flow         = $this->flows[$thisFlowDate];
            $engagementAt = ( clone $flow->getDate() )->add(new DateInterval('P1D'));

            // Ignore nonpayment flows (don't calc repayments for flows without payment).
            if ($flow->getPayment() === 0.0) {
                continue;
            }

            // Get first date of repayment.
            if ($this->getFirstRepaymentDate() === null) {
                $firstRepaymentDate = $flow->getDate();
            } else {
                $firstRepaymentDate = max($flow->getDate(), $this->getFirstRepaymentDate());
            }

            // Calc sum.
            $paymentInstallment = $this->getInstallments()->getInstallmentForDate($firstRepaymentDate);
            $periodsLeft        = ( $installments - $paymentInstallment->getOrder() + 1 );
            $rate               = ( $this->getRateAt($flow->getDate()) / $this->getPeriodType()->getPeriodsInYear() );
            $annuitRate         = pow(( 1 + $rate ), $periodsLeft);
            $engagement         = $this->getCapitalEngagementAt($engagementAt);
            $repaymentAmount    = ( $engagement * ( ( $rate * $annuitRate ) / ( $annuitRate - 1 ) ) );
            $repaymentAmount    = ( ceil(( $repaymentAmount * 100 )) / 100 );
            $repaymentAmount    = round($repaymentAmount, $decimalPlaces);

            // Add this repayment amount to every period for this one in future until next payment occurs.
            foreach ($this->getInstallments()->getAll() as $installment) {

                // Do not add to earlier installments.
                if ($installment->getPeriod()->getName() < $paymentInstallment->getPeriod()->getName()) {
                    continue;
                }

                // Do not add to installments that will be served with next flow.
                if ($nextFlowDate !== null && $installment->getPeriod()->getFirstDay() >= new DateTime($nextFlowDate)) {
                    continue;
                }

                $period     = $installment->getPeriod();
                $date       = $period->getLastDay();
                $engagement = $this->getCapitalEngagementAt($date);
                $rate       = $this->getRateAt($date);


                $interests  = ( $engagement * $period->getPercOfYear() * $period->getPercOfLength() * $rate );
                $interests  = round($interests, 2);

                // var_dump($engagement);
                // var_dump($period->getPercOfYear());
                // var_dump($period->getPercOfLength());
                // var_dump($rate);
                // var_dump($interests);

                $capital    = ( $repaymentAmount - $interests );

                $this->addRepayment($date, $capital);
                $this->calcEngagements();
            }
        }//end foreach

        // Add corrections at the end.
        $this->addRepaymentsCorrectionsForLastDay();

        foreach ($this->flows as $flow) {
            // print_r($flow->getRepayment() . PHP_EOL);
        }

        return $this;
    }

    /**
     * Clears (sets to zero) all repayments.
     *
     * @return self
     */
    private function clearRepayments() : self
    {

        // Clear all payments.
        foreach ($this->flows as $flow) {
            $flow->setRepayment(0.0);
        }

        return $this;
    }

    /**
     * Adds needed correction of repayment capital to last installment.
     *
     * @return self
     */
    private function addRepaymentsCorrectionsForLastDay() : self
    {

        // Lvd.
        $sumOfAllPayments = $sumOfAllRepayments = 0;

        // Sum.
        foreach ($this->flows as $flow) {
            $sumOfAllPayments   += $flow->getPayment();
            $sumOfAllRepayments += $flow->getRepayment();
        }

        // Lvd.
        $neededCorrection = (float) round(( $sumOfAllPayments - $sumOfAllRepayments ), 2);

        if ($neededCorrection !== 0.0) {
            $this->addRepayment($this->getEnd(), $neededCorrection);
        }

        return $this;
    }
}
