<?php

use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public bool $showModal = false;

    public ?int $transactionId = null;

    public string $type = 'expense';

    public string $amount = '';

    public string $account_id = '';

    public string $category_id = '';

    public string $note = '';

    public string $transacted_at = '';

    #[On('open-transaction-form')]
    public function open(?int $transactionId = null): void
    {
        $this->resetValidation();
        $this->transactionId = $transactionId;

        if ($transactionId) {
            $transaction = auth()->user()->transactions()->findOrFail($transactionId);

            $this->authorize('update', $transaction);

            $this->type = $transaction->type;
            $this->amount = (string) $transaction->amount;
            $this->account_id = (string) $transaction->account_id;
            $this->category_id = $transaction->category_id ? (string) $transaction->category_id : '';
            $this->note = (string) $transaction->note;
            $this->transacted_at = $transaction->transacted_at->format('Y-m-d');
        } else {
            $this->type = 'expense';
            $this->amount = '';
            $this->account_id = '';
            $this->category_id = '';
            $this->note = '';
            $this->transacted_at = now()->format('Y-m-d');
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'note' => ['nullable', 'string', 'max:255'],
            'transacted_at' => ['required', 'date'],
        ]);

        $account = auth()->user()->accounts()->findOrFail($validated['account_id']);
        $this->authorize('update', $account);

        if ($this->transactionId) {
            $transaction = auth()->user()->transactions()->findOrFail($this->transactionId);
            $this->authorize('update', $transaction);

            // Reverse the old balance effect, then let the create() below re-apply via observer.
            // Simplest correct approach: delete + recreate so TransactionObserver stays the single source of truth.
            $transaction->delete();
        }

        auth()->user()->transactions()->create([
            ...$validated,
            'category_id' => $validated['category_id'] ?: null,
        ]);

        $this->showModal = false;
        $this->dispatch('transaction-saved');

        Flux::toast(variant: 'success', text: __('Transaction saved.'));
    }

    #[Computed]
    public function accounts()
    {
        return auth()->user()->accounts()->orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return auth()->user()->categories()
            ->when($this->type !== 'transfer', fn ($q) => $q->where('type', $this->type))
            ->orderBy('name')
            ->get();
    }
}; ?>

<flux:modal name="transaction-form-modal" wire:model="showModal" class="max-w-lg">
    <form wire:submit="save" class="space-y-6">
        <flux:heading size="lg">
            {{ $transactionId ? __('Edit transaction') : __('Add transaction') }}
        </flux:heading>

        <flux:radio.group wire:model.live="type" variant="segmented">
            <flux:radio value="income">{{ __('Income') }}</flux:radio>
            <flux:radio value="expense">{{ __('Expense') }}</flux:radio>
            <flux:radio value="transfer">{{ __('Transfer') }}</flux:radio>
        </flux:radio.group>

        <flux:input wire:model="amount" :label="__('Amount')" type="number" step="0.01" min="0" required />

        <flux:select wire:model="account_id" :label="__('Account')" required>
            <flux:select.option value="">{{ __('Select an account') }}</flux:select.option>
            @foreach ($this->accounts as $account)
                <flux:select.option :value="(string) $account->id">{{ $account->name }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($type !== 'transfer')
            <flux:select wire:model="category_id" :label="__('Category')">
                <flux:select.option value="">{{ __('No category') }}</flux:select.option>
                @foreach ($this->categories as $category)
                    <flux:select.option :value="(string) $category->id">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:input wire:model="note" :label="__('Note')" />

        <flux:input wire:model="transacted_at" :label="__('Date')" type="date" required />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
        </div>
    </form>
</flux:modal>
