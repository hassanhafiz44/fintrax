<?php

namespace App\Observers;

use App\Models\LoanPayment;

class LoanPaymentObserver
{
    /**
     * Handle the LoanPayment "created" event.
     */
    public function created(LoanPayment $loanPayment): void
    {
        $loan = $loanPayment->loan;
        $loan->decrement('remaining', $loanPayment->amount);

        if ($loan->fresh()->remaining <= 0) {
            $loan->update(['status' => 'settled']);
        }
    }
}
