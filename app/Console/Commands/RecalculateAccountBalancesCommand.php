<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\LoanPayment;
use App\Models\Transaction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('accounts:recalculate {--user= : Limit to a single user id} {--dry-run : Report drift without writing}')]
#[Description('Recompute account balances from transactions and loan payments, repairing any drift')]
class RecalculateAccountBalancesCommand extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $accounts = Account::query()
            ->when($this->option('user'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->orderBy('user_id')
            ->orderBy('name')
            ->get();

        $driftCount = 0;

        foreach ($accounts as $account) {
            $computed = $this->computeBalance($account);
            $stored = (float) $account->balance;
            $delta = round($computed - $stored, 2);

            if (abs($delta) < 0.005) {
                continue;
            }

            $driftCount++;
            $sign = $delta > 0 ? '+' : '';
            $this->warn(sprintf(
                '#%d %s: %.2f → %.2f (%s%.2f)',
                $account->id,
                $account->name,
                $stored,
                $computed,
                $sign,
                $delta,
            ));

            if (! $dryRun) {
                $account->update(['balance' => $computed]);
            }
        }

        if ($driftCount === 0) {
            $this->info('All account balances are correct. No drift found.');
        } elseif ($dryRun) {
            $this->info(sprintf('%d account(s) drifted. Re-run without --dry-run to repair.', $driftCount));
        } else {
            $this->info(sprintf('Repaired %d account(s).', $driftCount));
        }

        return self::SUCCESS;
    }

    /**
     * Authoritative balance recomputed from zero (no opening-balance column exists).
     */
    private function computeBalance(Account $account): float
    {
        $income = (float) Transaction::query()
            ->where('account_id', $account->id)
            ->where('type', 'income')
            ->sum('amount');

        $expense = (float) Transaction::query()
            ->where('account_id', $account->id)
            ->where('type', 'expense')
            ->sum('amount');

        $transferOut = (float) Transaction::query()
            ->where('account_id', $account->id)
            ->where('type', 'transfer')
            ->sum('amount');

        $transferIn = (float) Transaction::query()
            ->where('to_account_id', $account->id)
            ->where('type', 'transfer')
            ->sum('amount');

        // borrowed → paying back → money leaves the account
        // lent → receiving back → money enters the account
        $loanOut = (float) LoanPayment::query()
            ->where('account_id', $account->id)
            ->whereHas('loan', fn ($query) => $query->where('direction', 'borrowed'))
            ->sum('amount');

        $loanIn = (float) LoanPayment::query()
            ->where('account_id', $account->id)
            ->whereHas('loan', fn ($query) => $query->where('direction', 'lent'))
            ->sum('amount');

        return round($income - $expense - $transferOut + $transferIn - $loanOut + $loanIn, 2);
    }
}
