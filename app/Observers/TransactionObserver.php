<?php

namespace App\Observers;

use App\Models\Transaction;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        match ($transaction->type) {
            'income' => $transaction->account->increment('balance', $transaction->amount),
            'expense' => $transaction->account->decrement('balance', $transaction->amount),
            default => null,
        };
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        match ($transaction->type) {
            'income' => $transaction->account->decrement('balance', $transaction->amount),
            'expense' => $transaction->account->increment('balance', $transaction->amount),
            default => null,
        };
    }
}
