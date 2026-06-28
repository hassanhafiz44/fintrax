# Loan Date Extension — Design

> **Status: shipped.** This was implemented and merged. The data model and goals
> below hold as designed. A few details diverged during build — they're marked
> **As-built** inline, and summarized in [As-built summary](#as-built-summary).

## Problem

Loans have a single `due_date` with no record of when the loan was actually taken
(`loaned_at`), and no way to track that a borrower asked for — and was granted —
more time to repay. Currently extending means silently overwriting `due_date`
with no history and no reason.

## Goals

- Record the date a loan was taken (`loaned_at`), separate from `created_at`.
- Allow a loan's due date to be extended multiple times, each with an optional
  reason, preserving full history.
- Surface that history in the UI per loan.

## Data Model

### `loans` table — add column

```php
$table->date('loaned_at')->nullable()->after('contact_name');
```

- Nullable at the DB level (existing rows have none), but the Livewire form
  requires it on create/edit, defaulting to today.
- Backfill migration: for existing rows, set `loaned_at = created_at->toDateString()`.

### New table: `loan_date_extensions`

```php
Schema::create('loan_date_extensions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
    $table->date('previous_due_date')->nullable(); // As-built: nullable — a loan with no due date can still be extended
    $table->date('new_due_date');
    $table->string('reason')->nullable();
    $table->timestamp('extended_at')->useCurrent();
    $table->timestamps();
});
```

- One row per extension. `loans.due_date` is mutated directly to `new_due_date`
  on each extension (no derived/computed due date elsewhere).
- `reason` optional, max 255 chars (string column).
- Ordered by `extended_at` for history display (oldest → newest, or reverse in UI).

## Model Changes

### `Loan`

```php
protected function casts(): array
{
    return [
        'loaned_at' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'remaining' => 'decimal:2',
    ];
}

/** @return HasMany<LoanDateExtension, $this> */
public function dateExtensions(): HasMany
{
    return $this->hasMany(LoanDateExtension::class)->latest('extended_at');
}
```

`isOverdue()` unchanged.

### New model: `LoanDateExtension`

```php
class LoanDateExtension extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'previous_due_date' => 'date',
            'new_due_date' => 'date',
            'extended_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
```

No observer needed — the extension action (below) writes both the history row
and updates `loans.due_date` in one transaction.

## Extension Action

**As-built:** there is no action class or `Loan` model method — the logic lives
inline in the `loans/form` SFC (`extendDueDate()` in
`resources/views/pages/loans/⚡form.blade.php`). It:

1. Validates `new_due_date` is `required, date`, plus `after:<current due_date>`
   **only when the loan already has a due date** (a loan with no due date can be
   given its first one via the same flow).
2. Wraps in `DB::transaction()`:
   - Creates `LoanDateExtension` row (`previous_due_date` = current `due_date`,
     which may be null; `new_due_date`; `reason`).
   - Updates `loans.due_date = new_due_date`.
3. Only callable when `loan.status === 'active'` — enforced by
   `abort_if($loan->status !== 'active', 403)` in both `openExtendForm()` and
   `extendDueDate()`.

Authorization: reuses `LoanPolicy` `update` (`$this->authorize('update', $loan)`).
On success it dispatches `loan-saved` (the same event the loan list already
listens to) — no separate `loan-extended` event.

## UI

### `loans/index`

- Each **active** loan row gets an "Extend" button/icon (hidden for `settled`
  loans).
- Clicking opens a modal. **As-built:** no new component — it's a third modal
  (`loan-extend-modal`, opened via the `open-loan-extend-form` event) inside the
  existing `loans/form` SFC, alongside the loan and payment modals:
  - Current due date — read-only `flux:input`, shows "No due date set" when absent
  - New due date (`flux:input type="date"`, required, after current when one exists)
  - Reason (`flux:input`, optional, max 255 — plain input, not a textarea)
  - Submit → runs `extendDueDate()`, dispatches `loan-saved`, closes modal.
- Loan row expands (or shows below contact/amount) a compact extension history
  list, e.g.:
  ```
  Jun 20 → Jul 10 (no reason given)
  May 01 → Jun 20 — "asked for more time, payday delayed"
  ```
  Sourced from `$loan->dateExtensions` (eager-loaded in the index query).

### `loans/form`

- Add `loaned_at` date input (required, defaults to today on create) alongside
  existing fields (`contact_name`, `direction`, `amount`, `due_date`, `note`).

## Validation Rules

- `loaned_at`: required, date, not after today (can't claim a loan was taken
  in the future) — confirm against existing form's validation style.
- Extension `new_due_date`: required, date, after current `due_date`.
- Extension `reason`: nullable, string, max 255.

## Testing

- Feature test: extending a loan creates a `loan_date_extensions` row and
  updates `loans.due_date`.
- Feature test: extension rejected if `new_due_date` <= current `due_date`.
- Feature test: extend button/action unavailable for settled loans
  (authorization or UI-level check).
- Feature test: `loaned_at` required and persisted via `loans/form`.
- Update existing `LoanFormTest` to cover `loaned_at` field.

## Out of Scope

- Editing/deleting past extension history rows.
- Notifications to the other party about extension.
- Multi-currency or amount changes during extension (due-date only).

## As-built summary

How the shipped code differs from the original design:

| Area | Design | Shipped |
|------|--------|---------|
| `previous_due_date` column | `date` (not null) | `date` **nullable** — loans with no due date can still be extended |
| Extension logic | Action class / `Loan` method | Inline `extendDueDate()` in the `loans/form` SFC |
| Extend UI | New `pages::loans.extend-due-date` component | Third modal inside existing `loans/form` (`open-loan-extend-form` event) |
| Reason field | `flux:textarea` or `flux:input` | `flux:input` |
| Event on success | `loan-extended` or `loan-saved` | `loan-saved` |
| `new_due_date` `after:` rule | always | only when the loan already has a due date |
| Active-only guard | step in action | `abort_if($loan->status !== 'active', 403)` on open + submit |

Everything else (the `loaned_at` column + backfill, the `LoanDateExtension`
model, `Loan::dateExtensions()` ordered newest-first, history display, and the
core data model) shipped as designed.

Source: `resources/views/pages/loans/⚡form.blade.php`, `app/Models/Loan.php`,
`app/Models/LoanDateExtension.php`, and the `loan_date_extensions` /
`loaned_at` migrations.
