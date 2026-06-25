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
            'transfer' => $this->applyTransfer($transaction),
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
            'transfer' => $this->reverseTransfer($transaction),
        };
    }

    private function applyTransfer(Transaction $transaction): void
    {
        $transaction->account->decrement('balance', $transaction->amount);
        $transaction->toAccount->increment('balance', $transaction->amount);
    }

    private function reverseTransfer(Transaction $transaction): void
    {
        $transaction->account->increment('balance', $transaction->amount);
        $transaction->toAccount->decrement('balance', $transaction->amount);
    }
}
