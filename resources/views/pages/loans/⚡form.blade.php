<?php

use App\Models\Account;
use App\Models\LoanDateExtension;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public bool $showLoanModal = false;

    public bool $showPaymentModal = false;

    public bool $showExtendModal = false;

    public ?int $loanId = null;

    public string $contact_name = '';

    public string $direction = 'lent';

    public string $amount = '';

    public string $loaned_at = '';

    public string $due_date = '';

    public string $note = '';

    public ?int $account_id = null;

    public string $payment_amount = '';

    public string $payment_note = '';

    public string $payment_paid_at = '';

    public ?int $payment_account_id = null;

    public string $current_due_date = '';

    public string $new_due_date = '';

    public string $extend_reason = '';

    /** @return Collection<int, Account> */
    #[Computed]
    public function accounts(): Collection
    {
        return auth()->user()->accounts()->orderBy('name')->get();
    }

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
            $this->loaned_at = $loan->loaned_at?->format('Y-m-d') ?? '';
            $this->due_date = $loan->due_date?->format('Y-m-d') ?? '';
            $this->note = (string) $loan->note;
            $this->account_id = $loan->account_id;
        } else {
            $this->contact_name = '';
            $this->direction = 'lent';
            $this->amount = '';
            $this->loaned_at = now()->format('Y-m-d');
            $this->due_date = '';
            $this->note = '';
            $this->account_id = null;
        }

        $this->showLoanModal = true;
    }

    public function saveLoan(): void
    {
        $validated = $this->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'direction' => ['required', 'in:lent,borrowed'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'loaned_at' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
        ]);

        if ($validated['account_id']) {
            $this->authorize('update', auth()->user()->accounts()->findOrFail($validated['account_id']));
        }

        if ($this->loanId) {
            $loan = auth()->user()->loans()->findOrFail($this->loanId);
            $this->authorize('update', $loan);

            $loan->update([
                'contact_name' => $validated['contact_name'],
                'direction' => $validated['direction'],
                'amount' => $validated['amount'],
                'loaned_at' => $validated['loaned_at'],
                'due_date' => $validated['due_date'] ?: null,
                'note' => $validated['note'],
                'account_id' => $validated['account_id'] ?: null,
            ]);

            // Keep remaining in sync with a manually-edited amount, preserving payments already made.
            $alreadyPaid = $loan->amount - $loan->remaining;
            $loan->update(['remaining' => max(0, $validated['amount'] - $alreadyPaid)]);
        } else {
            auth()->user()->loans()->create([
                ...$validated,
                'due_date' => $validated['due_date'] ?: null,
                'account_id' => $validated['account_id'] ?: null,
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
        $this->payment_account_id = null;
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
            'payment_account_id' => ['nullable', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
        ]);

        if ($validated['payment_account_id']) {
            $this->authorize('update', auth()->user()->accounts()->findOrFail($validated['payment_account_id']));
        }

        DB::transaction(function () use ($loan, $validated): void {
            $loan->payments()->create([
                'amount' => $validated['payment_amount'],
                'note' => $validated['payment_note'],
                'paid_at' => $validated['payment_paid_at'],
                'account_id' => $validated['payment_account_id'] ?: null,
            ]);
        });

        $this->showPaymentModal = false;
        $this->dispatch('loan-payment-saved');

        Flux::toast(variant: 'success', text: __('Payment logged.'));
    }

    #[On('open-loan-extend-form')]
    public function openExtendForm(int $loanId): void
    {
        $loan = auth()->user()->loans()->findOrFail($loanId);
        $this->authorize('update', $loan);
        abort_if($loan->status !== 'active', 403);

        $this->resetValidation();
        $this->loanId = $loanId;
        $this->current_due_date = $loan->due_date?->format('Y-m-d') ?? '';
        $this->new_due_date = '';
        $this->extend_reason = '';
        $this->showExtendModal = true;
    }

    public function extendDueDate(): void
    {
        $loan = auth()->user()->loans()->findOrFail($this->loanId);
        $this->authorize('update', $loan);
        abort_if($loan->status !== 'active', 403);

        $validated = $this->validate([
            'new_due_date' => array_filter([
                'required',
                'date',
                $loan->due_date ? 'after:'.$loan->due_date->format('Y-m-d') : null,
            ]),
            'extend_reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($loan, $validated): void {
            $loan->dateExtensions()->create([
                'previous_due_date' => $loan->due_date,
                'new_due_date' => $validated['new_due_date'],
                'reason' => $validated['extend_reason'],
            ]);

            $loan->update(['due_date' => $validated['new_due_date']]);
        });

        $this->showExtendModal = false;
        $this->dispatch('loan-saved');

        Flux::toast(variant: 'success', text: __('Due date extended.'));
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
            <flux:input wire:model="loaned_at" :label="__('Date loaned')" type="date" required />
            <flux:input wire:model="due_date" :label="__('Due date')" type="date" />
            <flux:input wire:model="note" :label="__('Note')" />

            <div>
                <flux:select wire:model.live="account_id" :label="__('Account (optional)')">
                    <flux:select.option value="">{{ __('No account') }}</flux:select.option>
                    @foreach ($this->accounts as $account)
                        <flux:select.option :value="$account->id">{{ $account->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if ($account_id)
                    <flux:text class="mt-1 text-xs">
                        {{ $direction === 'lent' ? __('Deducted from this account now.') : __('Added to this account now.') }}
                    </flux:text>
                @endif
            </div>

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
            <flux:select wire:model="payment_account_id" :label="__('Account (optional)')">
                <flux:select.option value="">{{ __('No account') }}</flux:select.option>
                @foreach ($this->accounts as $account)
                    <flux:select.option :value="$account->id">{{ $account->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="payment_note" :label="__('Note')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Log payment') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="loan-extend-modal" wire:model="showExtendModal" class="max-w-lg">
        <form wire:submit="extendDueDate" class="space-y-6">
            <flux:heading size="lg">{{ __('Extend due date') }}</flux:heading>

            <flux:input :label="__('Current due date')" :value="$current_due_date ?: __('No due date set')" readonly />
            <flux:input wire:model="new_due_date" :label="__('New due date')" type="date" required />
            <flux:input wire:model="extend_reason" :label="__('Reason')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Extend') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
