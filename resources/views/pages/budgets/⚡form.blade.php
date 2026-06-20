<?php

use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public bool $showModal = false;

    public ?int $budgetId = null;

    public string $name = '';

    public string $category_id = '';

    public string $amount = '';

    public string $period = 'monthly';

    public string $start_date = '';

    public string $end_date = '';

    #[On('open-budget-form')]
    public function open(?int $budgetId = null): void
    {
        $this->resetValidation();
        $this->budgetId = $budgetId;

        if ($budgetId) {
            $budget = auth()->user()->budgets()->findOrFail($budgetId);
            $this->authorize('update', $budget);

            $this->name = $budget->name;
            $this->category_id = $budget->category_id ? (string) $budget->category_id : '';
            $this->amount = (string) $budget->amount;
            $this->period = $budget->period;
            $this->start_date = $budget->start_date->format('Y-m-d');
            $this->end_date = $budget->end_date?->format('Y-m-d') ?? '';
        } else {
            $this->name = '';
            $this->category_id = '';
            $this->amount = '';
            $this->period = 'monthly';
            $this->start_date = now()->startOfMonth()->format('Y-m-d');
            $this->end_date = '';
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'period' => ['required', 'in:monthly,weekly,custom'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $payload = [
            ...$validated,
            'category_id' => $validated['category_id'] ?: null,
            'end_date' => $validated['end_date'] ?: null,
        ];

        if ($this->budgetId) {
            $budget = auth()->user()->budgets()->findOrFail($this->budgetId);
            $this->authorize('update', $budget);
            $budget->update($payload);
        } else {
            auth()->user()->budgets()->create($payload);
        }

        $this->showModal = false;
        $this->dispatch('budget-saved');

        Flux::toast(variant: 'success', text: __('Budget saved.'));
    }

    #[Computed]
    public function categories()
    {
        return auth()->user()->categories()->where('type', 'expense')->orderBy('name')->get();
    }
}; ?>

<flux:modal name="budget-form-modal" wire:model="showModal" class="max-w-lg">
    <form wire:submit="save" class="space-y-6">
        <flux:heading size="lg">{{ $budgetId ? __('Edit budget') : __('Add budget') }}</flux:heading>

        <flux:input wire:model="name" :label="__('Name')" required />

        <flux:select wire:model="category_id" :label="__('Category')">
            <flux:select.option value="">{{ __('All expense categories') }}</flux:select.option>
            @foreach ($this->categories as $category)
                <flux:select.option :value="(string) $category->id">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model="amount" :label="__('Amount')" type="number" step="0.01" min="0" required />

        <flux:radio.group wire:model="period" variant="segmented">
            <flux:radio value="monthly">{{ __('Monthly') }}</flux:radio>
            <flux:radio value="weekly">{{ __('Weekly') }}</flux:radio>
            <flux:radio value="custom">{{ __('Custom') }}</flux:radio>
        </flux:radio.group>

        <flux:input wire:model="start_date" :label="__('Start date')" type="date" required />
        <flux:input wire:model="end_date" :label="__('End date (optional)')" type="date" />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
        </div>
    </form>
</flux:modal>
