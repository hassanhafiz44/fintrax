# Fintrax — How It Works

This document explains the business rules and automatic behavior behind the UI. It's
useful for understanding *why* numbers change, and as a reference for developers. For the
end-user walkthrough, see **[user-guide.md](user-guide.md)**.

Stack: Laravel 13 · Livewire 4 (native single-file components) · Flux UI (free) ·
Fortify (auth). Default currency **PKR**.

---

## Ownership & access

- Every query is scoped to the logged-in user (`auth()->user()->relation()`), so users
  only ever see their own data.
- Policies (`app/Policies/*`) enforce that a user can only view, update, and delete their
  own records.
- **System categories** (`is_system = true`) are additionally protected: `CategoryPolicy`
  blocks updating or deleting them even by their owner.

---

## Account balance sync — `TransactionObserver`

Balances are never edited by hand; they're derived from transactions. The observer keeps
each account's `balance` in sync.

**On create:**
- **Income** → increment the source account by `amount`.
- **Expense** → decrement the source account by `amount`.
- **Transfer** → decrement the source account and increment the destination
  (`to_account_id`) by `amount`.

**On delete:** each of the above is reversed (income decrements, expense increments,
transfer puts both sides back).

**On edit:** the form deletes the old transaction and creates a new one inside a single
`DB::transaction()`. This keeps the observer the **single source of truth** for balances —
the old effect is reversed and the new one applied, with no chance of a half-applied
update leaving a balance drifted.

This is also why deleting a transaction in the UI shows a confirmation: it changes an
account balance.

---

## Transfers

- Require a **source account** and a **different destination account**
  (`to_account_id`, validated `different:account_id`).
- The destination is **required only when** the type is `transfer`
  (`required_if:type,transfer`).
- Transfers carry **no category** (the category field is hidden for transfers).

---

## Loan lifecycle — `LoanPaymentObserver`

A loan tracks `amount` (original) and `remaining` (still owed), with `direction` of
`lent` or `borrowed`.

**Logging a payment (create):**
1. Decrement the loan's `remaining` by the payment amount.
2. If `remaining <= 0`, set the loan `status` to **`settled`** (auto-settle).
3. If the payment names an `account_id`, adjust that account's balance:
   - **borrowed** → money **leaves** the account (you're paying back).
   - **lent** → money **enters** the account (you're being repaid).

**Deleting a payment:**
1. Increment `remaining` back by the payment amount.
2. If the loan was `settled` and `remaining > 0` again, set `status` back to **`active`**
   (auto-reactivate).
3. If the payment named an account, reverse the balance change from create.

Payment amount is validated to never exceed the loan's current `remaining`
(`max:<loan.remaining>`), so a loan can't be overpaid.

**Editing a loan's amount** recomputes the remaining without losing payment history:

```
alreadyPaid = old amount − old remaining
remaining   = max(0, new amount − alreadyPaid)
```

**Date extensions** are recorded as `LoanDateExtension` rows (previous due date, new due
date, optional reason, timestamp) and shown as history on the loan card. A new due date
must be **after** the current one.

`Loan::isOverdue()` returns true when the loan is still `active` and its `due_date` is in
the past.

---

## Budget periods & spending — `Budget`

A budget has an `amount`, a `period` (`monthly` / `weekly` / `custom`), a `start_date`,
and an optional `end_date`.

**`effectiveEndDate()`** decides the window's end:
- An explicit **`end_date`** always wins, when set.
- Otherwise: **weekly** → `start_date + 6 days` (end of day); **monthly** (and the custom
  fallback) → end of the start date's month.

**`spent`** = the sum of the user's **expense** transactions whose `transacted_at` falls
within `[start_date, effectiveEndDate()]`:
- If the budget has a `category_id`, only that category's expenses count.
- If `category_id` is **null**, **all** expense categories count.

**Overlap is intentional:** an expense logged to, say, *Food* counts toward both a
Food-specific budget and an all-categories budget for the same period. The all-categories
budget logically includes every category, so the same transaction legitimately affects
both.

**`progressPercent`** = `min(100, (spent / amount) * 100)`, returning `0` when `amount`
is not positive. The bar color thresholds in the UI: green `< 70%`, yellow `70–89%`,
red `>= 90%`.

---

## Onboarding seeding — `UserObserver`

When a `User` is created, the observer seeds a usable starting state:

- A default account: `name = 'Cash'`, `type = 'cash'`, `is_default = true` (currency
  defaults to `PKR`).
- System categories (`is_system = true`):
  - **Expense:** Food, Transport, Utilities, Shopping, Health, Entertainment, Rent, Other
  - **Income:** Salary, Freelance, Business, Gift, Other

---

## Authentication — Fortify

Auth is handled by Laravel Fortify (configured in `config/fortify.php`):

- **Registration** and **password reset**.
- **Email verification** — required; users hit the `verified` middleware before reaching
  the app. Changing your email resets verification.
- **Two-factor authentication** — TOTP via an authenticator app, with single-use
  **recovery codes**.
- **Passkeys** — WebAuthn sign-in and management.

---

## Key model map

| Model | Purpose | Notable behavior |
|-------|---------|------------------|
| `Account` | Wallet/bank holding a balance | `hasTransactions()` guards deletion |
| `Category` | Income/expense label | `is_system` ones are protected |
| `Transaction` | Income / expense / transfer | drives balances via `TransactionObserver` |
| `Loan` | Lent or borrowed money | `isOverdue()`, auto-settle/reactivate |
| `LoanPayment` | A repayment on a loan | adjusts `remaining` + optional account balance |
| `LoanDateExtension` | Due-date change history | previous/new date + reason |
| `Budget` | Spending limit for a period | computed `spent` / `progressPercent` |
