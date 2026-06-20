<?php

use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public bool $showModal = false;

    public ?int $accountId = null;

    public string $name = '';

    public string $type = 'cash';

    public string $balance = '0';

    public string $currency = 'PKR';

    public string $color = '#6366f1';

    #[On('open-account-form')]
    public function open(?int $accountId = null): void
    {
        $this->resetValidation();
        $this->accountId = $accountId;

        if ($accountId) {
            $account = auth()->user()->accounts()->findOrFail($accountId);
            $this->authorize('update', $account);

            $this->name = $account->name;
            $this->type = $account->type;
            $this->balance = (string) $account->balance;
            $this->currency = $account->currency;
            $this->color = $account->color;
        } else {
            $this->name = '';
            $this->type = 'cash';
            $this->balance = '0';
            $this->currency = 'PKR';
            $this->color = '#6366f1';
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:cash,bank,mobile_wallet,other'],
            'balance' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'color' => ['required', 'string', 'max:7'],
        ]);

        if ($this->accountId) {
            $account = auth()->user()->accounts()->findOrFail($this->accountId);
            $this->authorize('update', $account);
            $account->update($validated);
        } else {
            auth()->user()->accounts()->create([
                ...$validated,
                'is_default' => false,
            ]);
        }

        $this->showModal = false;
        $this->dispatch('account-saved');

        Flux::toast(variant: 'success', text: __('Account saved.'));
    }
}; ?>

<flux:modal name="account-form-modal" wire:model="showModal" class="max-w-lg">
    <form wire:submit="save" class="space-y-6">
        <flux:heading size="lg">{{ $accountId ? __('Edit account') : __('Add account') }}</flux:heading>

        <flux:input wire:model="name" :label="__('Name')" required />

        <flux:select wire:model="type" :label="__('Type')" required>
            <flux:select.option value="cash">{{ __('Cash') }}</flux:select.option>
            <flux:select.option value="bank">{{ __('Bank') }}</flux:select.option>
            <flux:select.option value="mobile_wallet">{{ __('Mobile wallet') }}</flux:select.option>
            <flux:select.option value="other">{{ __('Other') }}</flux:select.option>
        </flux:select>

        <flux:input wire:model="balance" :label="__('Balance')" type="number" step="0.01" required />
        <flux:input wire:model="currency" :label="__('Currency')" maxlength="3" required />
        <flux:input wire:model="color" :label="__('Color')" type="color" />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
        </div>
    </form>
</flux:modal>
