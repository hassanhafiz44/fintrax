<?php

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Accounts')] class extends Component {
    public int $deletingId = 0;

    public bool $confirmingDelete = false;

    /**
     * @return Collection<int, Account>
     */
    #[Computed]
    public function accounts(): Collection
    {
        return auth()->user()->accounts()->orderByDesc('is_default')->orderBy('name')->get();
    }

    public function setDefault(int $accountId): void
    {
        $account = auth()->user()->accounts()->findOrFail($accountId);
        $this->authorize('update', $account);

        auth()->user()->accounts()->update(['is_default' => false]);
        $account->update(['is_default' => true]);

        unset($this->accounts);
    }

    public function confirmDelete(int $accountId): void
    {
        $this->deletingId = $accountId;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $account = auth()->user()->accounts()->findOrFail($this->deletingId);

        $this->authorize('delete', $account);

        $account->delete();

        $this->deletingId = 0;
        $this->confirmingDelete = false;
    }

    #[On('account-saved')]
    public function refreshList(): void
    {
        unset($this->accounts);
    }
}; ?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl" level="1">{{ __('Accounts') }}</flux:heading>

        <flux:button variant="primary" icon="plus" wire:click="$dispatch('open-account-form')">
            {{ __('Add account') }}
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->accounts as $account)
            <flux:card wire:key="account-{{ $account->id }}" class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full" style="background-color: {{ $account->color }}"></span>
                        <flux:text class="font-medium">{{ $account->name }}</flux:text>
                    </div>
                    @if ($account->is_default)
                        <flux:badge size="sm" color="green">{{ __('Default') }}</flux:badge>
                    @endif
                </div>

                <flux:heading size="lg">{{ number_format($account->balance, 2) }} {{ $account->currency }}</flux:heading>
                <flux:text class="text-xs">{{ __(ucfirst(str_replace('_', ' ', $account->type))) }}</flux:text>

                <div class="flex gap-2">
                    @unless ($account->is_default)
                        <flux:button size="sm" variant="outline" wire:click="setDefault({{ $account->id }})">
                            {{ __('Set default') }}
                        </flux:button>
                    @endunless
                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="$dispatch('open-account-form', { accountId: {{ $account->id }} })" />
                    <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50" wire:click="confirmDelete({{ $account->id }})" />
                </div>
            </flux:card>
        @empty
            <div class="col-span-full p-8 text-center">
                <flux:text>{{ __('No accounts yet') }}</flux:text>
            </div>
        @endforelse
    </div>

    <livewire:pages::accounts.form />

    <flux:modal name="confirm-account-delete" wire:model.self="confirmingDelete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete account?') }}</flux:heading>
            <flux:text>{{ __('This will also delete all of its transactions.') }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button variant="outline" wire:click="$set('confirmingDelete', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
