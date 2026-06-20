<?php

use App\Models\Loan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function totalBalance(): float
    {
        return (float) auth()->user()->accounts()->sum('balance');
    }

    #[Computed]
    public function monthIncome(): float
    {
        return (float) auth()->user()->transactions()
            ->where('type', 'income')
            ->whereMonth('transacted_at', now()->month)
            ->whereYear('transacted_at', now()->year)
            ->sum('amount');
    }

    #[Computed]
    public function monthExpense(): float
    {
        return (float) auth()->user()->transactions()
            ->where('type', 'expense')
            ->whereMonth('transacted_at', now()->month)
            ->whereYear('transacted_at', now()->year)
            ->sum('amount');
    }

    #[Computed]
    public function netThisMonth(): float
    {
        return $this->monthIncome - $this->monthExpense;
    }

    /**
     * @return Collection<int, Loan>
     */
    #[Computed]
    public function activeLoans(): Collection
    {
        return auth()->user()->loans()
            ->where('status', 'active')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * @return BaseCollection<int, \App\Models\Budget>
     */
    #[Computed]
    public function budgetAlerts(): BaseCollection
    {
        return auth()->user()->budgets()
            ->with('category')
            ->get()
            ->filter(fn ($budget) => $budget->progress_percent >= 80);
    }

    #[Computed]
    public function recentTransactions(): Collection
    {
        return auth()->user()->transactions()
            ->with(['category', 'account'])
            ->orderByDesc('transacted_at')
            ->limit(10)
            ->get();
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl" level="1">{{ __('Dashboard') }}</flux:heading>

    {{-- Summary row --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card>
            <flux:text class="text-xs uppercase">{{ __('Total balance') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->totalBalance, 2) }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text class="text-xs uppercase">{{ __('Month income') }}</flux:text>
            <flux:heading size="lg" class="text-income">{{ number_format($this->monthIncome, 2) }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text class="text-xs uppercase">{{ __('Month expense') }}</flux:text>
            <flux:heading size="lg" class="text-expense">{{ number_format($this->monthExpense, 2) }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text class="text-xs uppercase">{{ __('Net this month') }}</flux:text>
            <flux:heading size="lg" class="{{ $this->netThisMonth >= 0 ? 'text-income' : 'text-expense' }}">
                {{ number_format($this->netThisMonth, 2) }}
            </flux:heading>
        </flux:card>
    </div>

    {{-- Active loans strip --}}
    @if ($this->activeLoans->isNotEmpty())
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('Active loans') }}</flux:heading>
            <div class="flex gap-3 overflow-x-auto pb-2">
                @foreach ($this->activeLoans as $loan)
                    <flux:card
                        wire:key="loan-{{ $loan->id }}"
                        class="min-w-[220px] shrink-0 {{ $loan->isOverdue() ? 'border-orange-300 bg-orange-50 dark:border-orange-700 dark:bg-orange-950/30' : '' }}"
                    >
                        <div class="flex items-center justify-between">
                            <flux:text class="font-medium">{{ $loan->contact_name }}</flux:text>
                            <flux:badge size="sm" :color="$loan->direction === 'lent' ? 'blue' : 'amber'">
                                {{ __(ucfirst($loan->direction)) }}
                            </flux:badge>
                        </div>
                        <flux:heading size="lg">{{ number_format($loan->remaining, 2) }}</flux:heading>
                        @if ($loan->due_date)
                            <flux:text class="text-xs {{ $loan->isOverdue() ? 'text-orange-600 dark:text-orange-400' : '' }}">
                                {{ __('Due :date', ['date' => $loan->due_date->format('d M Y')]) }}
                            </flux:text>
                        @endif
                    </flux:card>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Budget alerts --}}
    @if ($this->budgetAlerts->isNotEmpty())
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('Budget alerts') }}</flux:heading>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($this->budgetAlerts as $budget)
                    <flux:callout
                        wire:key="budget-alert-{{ $budget->id }}"
                        :variant="$budget->progress_percent >= 100 ? 'danger' : 'warning'"
                        icon="exclamation-triangle"
                        :heading="$budget->name"
                    >
                        {{ __(':percent% of :amount used', ['percent' => $budget->progress_percent, 'amount' => number_format($budget->amount, 2)]) }}
                    </flux:callout>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recent transactions --}}
    <div class="space-y-2">
        <flux:heading size="lg">{{ __('Recent transactions') }}</flux:heading>
        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @forelse ($this->recentTransactions as $transaction)
                <div wire:key="recent-tx-{{ $transaction->id }}" class="flex items-center justify-between p-4">
                    <div>
                        <flux:text class="font-medium">{{ $transaction->note ?? $transaction->category?->name ?? __('Transaction') }}</flux:text>
                        <flux:text class="text-xs">
                            {{ $transaction->account->name }} &middot; {{ $transaction->transacted_at->format('d M Y') }}
                        </flux:text>
                    </div>
                    <flux:badge :color="match ($transaction->type) {
                        'income' => 'green',
                        'expense' => 'red',
                        default => 'blue',
                    }">
                        {{ number_format($transaction->amount, 2) }}
                    </flux:badge>
                </div>
            @empty
                <div class="p-8 text-center">
                    <flux:text>{{ __('No transactions yet') }}</flux:text>
                </div>
            @endforelse
        </div>
    </div>
</div>
