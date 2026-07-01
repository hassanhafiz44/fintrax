<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Loan;

class LoanObserver
{
    public function created(Loan $loan): void
    {
        if ($loan->account_id) {
            $loan->account?->increment('balance', $this->disbursementDelta($loan->direction, (float) $loan->amount));
        }
    }

    public function updated(Loan $loan): void
    {
        if (! $loan->wasChanged(['account_id', 'direction', 'amount'])) {
            return;
        }

        // Reverse the original disbursement on the original account.
        $originalAccountId = $loan->getOriginal('account_id');

        if ($originalAccountId) {
            Account::find((int) $originalAccountId)?->decrement('balance', $this->disbursementDelta(
                (string) $loan->getOriginal('direction'),
                (float) $loan->getOriginal('amount'),
            ));
        }

        // Apply the new disbursement on the current account.
        if ($loan->account_id) {
            Account::find((int) $loan->account_id)?->increment('balance', $this->disbursementDelta($loan->direction, (float) $loan->amount));
        }
    }

    public function deleted(Loan $loan): void
    {
        if ($loan->account_id) {
            $loan->account?->decrement('balance', $this->disbursementDelta($loan->direction, (float) $loan->amount));
        }
    }

    /**
     * Signed amount a fresh disbursement adds to the account balance.
     *
     * borrowed → money received (increases balance); lent → money given (decreases balance).
     */
    private function disbursementDelta(string $direction, float $amount): float
    {
        return match ($direction) {
            'borrowed' => $amount,
            'lent' => -$amount,
            default => 0.0,
        };
    }
}
