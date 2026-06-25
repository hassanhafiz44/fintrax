<?php

use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Transactions')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public string $type = '';

    public string $account_id = '';

    public string $category_id = '';

    public string $date_from = '';

    public string $date_to = '';

    public int $deletingId = 0;

    public bool $confirmingDelete = false;

    public function updated($name): void
    {
        if (in_array($name, ['search', 'type', 'account_id', 'category_id', 'date_from', 'date_to'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function accounts()
    {
        return auth()->user()->accounts()->orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return auth()->user()->categories()->orderBy('name')->get();
    }

    /**
     * @return LengthAwarePaginator<int, Transaction>
     */
    #[Computed]
    public function transactions(): LengthAwarePaginator
    {
        return auth()->user()->transactions()
            ->with(['category', 'account', 'toAccount'])
            ->when($this->search, fn ($q) => $q->where('note', 'like', "%{$this->search}%"))
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->account_id, fn ($q) => $q->where('account_id', $this->account_id))
            ->when($this->category_id, fn ($q) => $q->where('category_id', $this->category_id))
            ->when($this->date_from, fn ($q) => $q->where('transacted_at', '>=', $this->date_from))
            ->when($this->date_to, fn ($q) => $q->where('transacted_at', '<=', $this->date_to))
            ->orderByDesc('transacted_at')
            ->paginate(15);
    }

    public function confirmDelete(int $transactionId): void
    {
        $this->deletingId = $transactionId;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $transaction = auth()->user()->transactions()->findOrFail($this->deletingId);

        $this->authorize('delete', $transaction);

        $transaction->delete();

        $this->deletingId = 0;
        $this->confirmingDelete = false;
    }

    #[On('transaction-saved')]
    public function refreshList(): void
    {
        unset($this->transactions);
    }
}; ?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl" level="1">{{ __('Transactions') }}</flux:heading>

        <flux:button variant="primary" icon="plus" wire:click="$dispatch('open-transaction-form')">
            {{ __('Add transaction') }}
        </flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search note…') }}" icon="magnifying-glass" />

        <flux:select wire:model.live="type" placeholder="{{ __('Type') }}">
            <flux:select.option value="">{{ __('All types') }}</flux:select.option>
            <flux:select.option value="income">{{ __('Income') }}</flux:select.option>
            <flux:select.option value="expense">{{ __('Expense') }}</flux:select.option>
            <flux:select.option value="transfer">{{ __('Transfer') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="account_id" placeholder="{{ __('Account') }}">
            <flux:select.option value="">{{ __('All accounts') }}</flux:select.option>
            @foreach ($this->accounts as $account)
                <flux:select.option :value="(string) $account->id">{{ $account->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="category_id" placeholder="{{ __('Category') }}">
            <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
            @foreach ($this->categories as $category)
                <flux:select.option :value="(string) $category->id">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex gap-2">
            <flux:input type="date" wire:model.live="date_from" />
            <flux:input type="date" wire:model.live="date_to" />
        </div>
    </div>

    <flux:table :paginate="$this->transactions">
        <flux:table.columns>
            <flux:table.column>{{ __('Description') }}</flux:table.column>
            <flux:table.column>{{ __('Account') }}</flux:table.column>
            <flux:table.column>{{ __('Date') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Amount') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->transactions as $transaction)
                <flux:table.row :key="'tx-'.$transaction->id">
                    <flux:table.cell>
                        {{ $transaction->note ?? $transaction->category?->name ?? __('Transaction') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $transaction->account->name }}
                        @if ($transaction->type === 'transfer' && $transaction->toAccount)
                            &rarr; {{ $transaction->toAccount->name }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $transaction->transacted_at->format('d M Y') }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:badge :color="match ($transaction->type) {
                            'income' => 'green',
                            'expense' => 'red',
                            default => 'blue',
                        }">
                            {{ number_format($transaction->amount, 2) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="pencil"
                                wire:click="$dispatch('open-transaction-form', { transactionId: {{ $transaction->id }} })"
                            />

                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                class="text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50"
                                wire:click="confirmDelete({{ $transaction->id }})"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">
                        <flux:text>{{ __('No transactions yet') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::transactions.form />

    <flux:modal name="confirm-transaction-delete" wire:model.self="confirmingDelete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete transaction?') }}</flux:heading>
            <flux:text>{{ __('This will reverse its effect on the account balance.') }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button variant="outline" wire:click="$set('confirmingDelete', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
