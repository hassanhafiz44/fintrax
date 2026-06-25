<?php

namespace App\Observers;

use App\Models\LoanPayment;

class LoanPaymentObserver
{
    public function created(LoanPayment $loanPayment): void
    {
        $loan = $loanPayment->loan;
        $loan->decrement('remaining', $loanPayment->amount);

        if ($loan->fresh()?->remaining <= 0) {
            $loan->update(['status' => 'settled']);
        }

        if ($loanPayment->account_id) {
            // borrowed → paying back → money leaves my account
            // lent → receiving back → money enters my account
            match ($loan->direction) {
                'borrowed' => $loanPayment->account->decrement('balance', $loanPayment->amount),
                'lent' => $loanPayment->account->increment('balance', $loanPayment->amount),
            };
        }
    }

    public function deleted(LoanPayment $loanPayment): void
    {
        if (! $loanPayment->account_id) {
            return;
        }

        $loan = $loanPayment->loan;

        match ($loan->direction) {
            'borrowed' => $loanPayment->account->increment('balance', $loanPayment->amount),
            'lent' => $loanPayment->account->decrement('balance', $loanPayment->amount),
        };
    }
}
