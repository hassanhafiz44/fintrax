<?php

use App\Models\Category;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Categories')] class extends Component {
    public bool $showModal = false;

    public ?int $categoryId = null;

    public string $name = '';

    public string $type = 'expense';

    public string $color = '#6366f1';

    public int $deletingId = 0;

    public bool $confirmingDelete = false;

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return auth()->user()->categories()->orderBy('type')->orderBy('name')->get();
    }

    public function openForm(?int $categoryId = null): void
    {
        $this->resetValidation();
        $this->categoryId = $categoryId;

        if ($categoryId) {
            $category = auth()->user()->categories()->findOrFail($categoryId);
            $this->authorize('update', $category);

            $this->name = $category->name;
            $this->type = $category->type;
            $this->color = $category->color;
        } else {
            $this->name = '';
            $this->type = 'expense';
            $this->color = '#6366f1';
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense'],
            'color' => ['required', 'string', 'max:7'],
        ]);

        if ($this->categoryId) {
            $category = auth()->user()->categories()->findOrFail($this->categoryId);
            $this->authorize('update', $category);
            $category->update($validated);
        } else {
            auth()->user()->categories()->create([
                ...$validated,
                'is_system' => false,
            ]);
        }

        $this->showModal = false;
        unset($this->categories);

        Flux::toast(variant: 'success', text: __('Category saved.'));
    }

    public function confirmDelete(int $categoryId): void
    {
        $this->deletingId = $categoryId;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $category = auth()->user()->categories()->findOrFail($this->deletingId);

        $this->authorize('delete', $category);

        $category->delete();

        $this->deletingId = 0;
        $this->confirmingDelete = false;
        unset($this->categories);
    }
}; ?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl" level="1">{{ __('Categories') }}</flux:heading>

        <flux:button variant="primary" icon="plus" wire:click="openForm">
            {{ __('Add category') }}
        </flux:button>
    </div>

    <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
        @forelse ($this->categories as $category)
            <div wire:key="category-{{ $category->id }}" class="flex items-center justify-between p-4">
                <div class="flex items-center gap-3">
                    <span class="size-3 rounded-full" style="background-color: {{ $category->color }}"></span>
                    <flux:text class="font-medium">{{ $category->name }}</flux:text>
                    <flux:badge size="sm" :color="$category->type === 'income' ? 'green' : 'red'">
                        {{ $category->type === 'income' ? __('Income') : __('Expense') }}
                    </flux:badge>
                    @if ($category->is_system)
                        <flux:badge size="sm">{{ __('System') }}</flux:badge>
                    @endif
                </div>

                @unless ($category->is_system)
                    <div class="flex gap-2">
                        <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openForm({{ $category->id }})" />
                        <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50" wire:click="confirmDelete({{ $category->id }})" />
                    </div>
                @endunless
            </div>
        @empty
            <div class="p-8 text-center">
                <flux:text>{{ __('No categories yet') }}</flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="category-form-modal" wire:model="showModal" class="max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $categoryId ? __('Edit category') : __('Add category') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required />

            <flux:radio.group wire:model="type" variant="segmented">
                <flux:radio value="expense">{{ __('Expense') }}</flux:radio>
                <flux:radio value="income">{{ __('Income') }}</flux:radio>
            </flux:radio.group>

            <flux:input wire:model="color" :label="__('Color')" type="color" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-category-delete" wire:model.self="confirmingDelete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete category?') }}</flux:heading>
            <div class="flex justify-end gap-2">
                <flux:button variant="outline" wire:click="$set('confirmingDelete', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
