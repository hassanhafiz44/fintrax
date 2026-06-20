<?php

use App\Models\Loan;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Loans')] class extends Component {
    public string $statusFilter = 'active';

    public int $deletingId = 0;

    /**
     * @return Collection<int, Loan>
     */
    #[Computed]
    public function loans(): Collection
    {
        return auth()->user()->loans()
            ->with('payments')
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get();
    }

    public function confirmDelete(int $loanId): void
    {
        $this->deletingId = $loanId;
    }

    public function delete(): void
    {
        $loan = auth()->user()->loans()->findOrFail($this->deletingId);

        $this->authorize('delete', $loan);

        $loan->delete();

        $this->deletingId = 0;
    }

    #[On('loan-saved')]
    #[On('loan-payment-saved')]
    public function refreshList(): void
    {
        unset($this->loans);
    }
}; ?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl" level="1">{{ __('Loans') }}</flux:heading>

        <flux:button variant="primary" icon="plus" wire:click="$dispatch('open-loan-form')">
            {{ __('Add loan') }}
        </flux:button>
    </div>

    <flux:radio.group wire:model.live="statusFilter" variant="segmented">
        <flux:radio value="active">{{ __('Active') }}</flux:radio>
        <flux:radio value="settled">{{ __('Settled') }}</flux:radio>
        <flux:radio value="">{{ __('All') }}</flux:radio>
    </flux:radio.group>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->loans as $loan)
            <flux:card
                wire:key="loan-{{ $loan->id }}"
                class="space-y-3 {{ $loan->isOverdue() ? 'border-orange-300 bg-orange-50 dark:border-orange-700 dark:bg-orange-950/30' : '' }}"
            >
                <div class="flex items-center justify-between">
                    <flux:text class="font-medium">{{ $loan->contact_name }}</flux:text>
                    <flux:badge size="sm" :color="$loan->direction === 'lent' ? 'blue' : 'amber'">
                        {{ __(ucfirst($loan->direction)) }}
                    </flux:badge>
                </div>

                <flux:heading size="lg">{{ number_format($loan->remaining, 2) }}</flux:heading>
                <flux:text class="text-xs">{{ __('of :amount', ['amount' => number_format($loan->amount, 2)]) }}</flux:text>

                @if ($loan->due_date)
                    <flux:badge size="sm" :color="$loan->isOverdue() ? 'orange' : ($loan->status === 'settled' ? 'green' : 'yellow')">
                        {{ $loan->status === 'settled' ? __('Settled') : ($loan->isOverdue() ? __('Overdue') : __('Due :date', ['date' => $loan->due_date->format('d M Y')])) }}
                    </flux:badge>
                @endif

                <div class="flex gap-2">
                    @if ($loan->status === 'active')
                        <flux:button size="sm" variant="primary" wire:click="$dispatch('open-loan-payment-form', { loanId: {{ $loan->id }} })">
                            {{ __('Log payment') }}
                        </flux:button>
                    @endif
                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="$dispatch('open-loan-form', { loanId: {{ $loan->id }} })" />
                    <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50" wire:click="confirmDelete({{ $loan->id }})" />
                </div>
            </flux:card>
        @empty
            <div class="col-span-full p-8 text-center">
                <flux:text>{{ __('No loans yet') }}</flux:text>
            </div>
        @endforelse
    </div>

    <livewire:pages::loans.form />

    <flux:modal name="confirm-loan-delete" :show="$deletingId > 0" @close="$set('deletingId', 0)" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete loan?') }}</flux:heading>
            <flux:text>{{ __('This will also delete its payment history.') }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button variant="outline" wire:click="$set('deletingId', 0)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
