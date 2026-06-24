<?php

use App\Models\Budget;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Budgets')] class extends Component {
    public int $deletingId = 0;

    public bool $confirmingDelete = false;

    /**
     * @return Collection<int, Budget>
     */
    #[Computed]
    public function budgets(): Collection
    {
        return auth()->user()->budgets()
            ->with('category')
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function confirmDelete(int $budgetId): void
    {
        $this->deletingId = $budgetId;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $budget = auth()->user()->budgets()->findOrFail($this->deletingId);

        $this->authorize('delete', $budget);

        $budget->delete();

        $this->deletingId = 0;
        $this->confirmingDelete = false;
    }

    #[On('budget-saved')]
    public function refreshList(): void
    {
        unset($this->budgets);
    }
}; ?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl" level="1">{{ __('Budgets') }}</flux:heading>

        <flux:button variant="primary" icon="plus" wire:click="$dispatch('open-budget-form')">
            {{ __('Add budget') }}
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->budgets as $budget)
            @php
                $percent = $budget->progress_percent;
                $barColor = match (true) {
                    $percent >= 90 => 'bg-red-500',
                    $percent >= 70 => 'bg-yellow-500',
                    default => 'bg-green-500',
                };
            @endphp
            <flux:card wire:key="budget-{{ $budget->id }}" class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:text class="font-medium">{{ $budget->name }}</flux:text>
                    @if ($budget->category)
                        <flux:badge size="sm">{{ $budget->category->name }}</flux:badge>
                    @endif
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span>{{ number_format($budget->spent, 2) }}</span>
                        <span>{{ number_format($budget->amount, 2) }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full {{ $barColor }}" style="width: {{ min(100, $percent) }}%"></div>
                    </div>
                    <flux:text class="text-xs">{{ __(':percent% used', ['percent' => $percent]) }}</flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="$dispatch('open-budget-form', { budgetId: {{ $budget->id }} })" />
                    <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50" wire:click="confirmDelete({{ $budget->id }})" />
                </div>
            </flux:card>
        @empty
            <div class="col-span-full p-8 text-center">
                <flux:text>{{ __('No budgets yet') }}</flux:text>
            </div>
        @endforelse
    </div>

    <livewire:pages::budgets.form />

    <flux:modal name="confirm-budget-delete" wire:model.self="confirmingDelete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete budget?') }}</flux:heading>
            <div class="flex justify-end gap-2">
                <flux:button variant="outline" wire:click="$set('confirmingDelete', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
