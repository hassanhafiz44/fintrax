<?php

use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public bool $showLoanModal = false;

    public bool $showPaymentModal = false;

    public ?int $loanId = null;

    public string $contact_name = '';

    public string $direction = 'lent';

    public string $amount = '';

    public string $due_date = '';

    public string $note = '';

    public string $payment_amount = '';

    public string $payment_note = '';

    public string $payment_paid_at = '';

    #[On('open-loan-form')]
    public function openLoanForm(?int $loanId = null): void
    {
        $this->resetValidation();
        $this->loanId = $loanId;

        if ($loanId) {
            $loan = auth()->user()->loans()->findOrFail($loanId);
            $this->authorize('update', $loan);

            $this->contact_name = $loan->contact_name;
            $this->direction = $loan->direction;
            $this->amount = (string) $loan->amount;
            $this->due_date = $loan->due_date?->format('Y-m-d') ?? '';
            $this->note = (string) $loan->note;
        } else {
            $this->contact_name = '';
            $this->direction = 'lent';
            $this->amount = '';
            $this->due_date = '';
            $this->note = '';
        }

        $this->showLoanModal = true;
    }

    public function saveLoan(): void
    {
        $validated = $this->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'direction' => ['required', 'in:lent,borrowed'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->loanId) {
            $loan = auth()->user()->loans()->findOrFail($this->loanId);
            $this->authorize('update', $loan);

            $loan->update([
                'contact_name' => $validated['contact_name'],
                'direction' => $validated['direction'],
                'amount' => $validated['amount'],
                'due_date' => $validated['due_date'] ?: null,
                'note' => $validated['note'],
            ]);

            // Keep remaining in sync with a manually-edited amount, preserving payments already made.
            $alreadyPaid = $loan->amount - $loan->remaining;
            $loan->update(['remaining' => max(0, $validated['amount'] - $alreadyPaid)]);
        } else {
            auth()->user()->loans()->create([
                ...$validated,
                'due_date' => $validated['due_date'] ?: null,
                'remaining' => $validated['amount'],
                'status' => 'active',
            ]);
        }

        $this->showLoanModal = false;
        $this->dispatch('loan-saved');

        Flux::toast(variant: 'success', text: __('Loan saved.'));
    }

    #[On('open-loan-payment-form')]
    public function openPaymentForm(int $loanId): void
    {
        $this->resetValidation();
        $this->loanId = $loanId;
        $this->payment_amount = '';
        $this->payment_note = '';
        $this->payment_paid_at = now()->format('Y-m-d');
        $this->showPaymentModal = true;
    }

    public function savePayment(): void
    {
        $loan = auth()->user()->loans()->findOrFail($this->loanId);
        $this->authorize('update', $loan);

        $validated = $this->validate([
            'payment_amount' => ['required', 'numeric', 'min:0.01', 'max:'.$loan->remaining],
            'payment_note' => ['nullable', 'string', 'max:255'],
            'payment_paid_at' => ['required', 'date'],
        ]);

        $loan->payments()->create([
            'amount' => $validated['payment_amount'],
            'note' => $validated['payment_note'],
            'paid_at' => $validated['payment_paid_at'],
        ]);

        $this->showPaymentModal = false;
        $this->dispatch('loan-payment-saved');

        Flux::toast(variant: 'success', text: __('Payment logged.'));
    }
}; ?>

<div>
    <flux:modal name="loan-form-modal" wire:model="showLoanModal" class="max-w-lg">
        <form wire:submit="saveLoan" class="space-y-6">
            <flux:heading size="lg">{{ $loanId ? __('Edit loan') : __('Add loan') }}</flux:heading>

            <flux:radio.group wire:model="direction" variant="segmented">
                <flux:radio value="lent">{{ __('I lent') }}</flux:radio>
                <flux:radio value="borrowed">{{ __('I borrowed') }}</flux:radio>
            </flux:radio.group>

            <flux:input wire:model="contact_name" :label="__('Contact name')" required />
            <flux:input wire:model="amount" :label="__('Amount')" type="number" step="0.01" min="0" required />
            <flux:input wire:model="due_date" :label="__('Due date')" type="date" />
            <flux:input wire:model="note" :label="__('Note')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="loan-payment-form-modal" wire:model="showPaymentModal" class="max-w-lg">
        <form wire:submit="savePayment" class="space-y-6">
            <flux:heading size="lg">{{ __('Log payment') }}</flux:heading>

            <flux:input wire:model="payment_amount" :label="__('Amount')" type="number" step="0.01" min="0" required />
            <flux:input wire:model="payment_paid_at" :label="__('Date')" type="date" required />
            <flux:input wire:model="payment_note" :label="__('Note')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Log payment') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
