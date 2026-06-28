# Fintrax — User Guide

Fintrax is a personal finance tracker. It helps you record where your money is
(**accounts**), what comes in and goes out (**transactions**), money you've lent or
borrowed (**loans**), and spending limits (**budgets**) — with a **dashboard** that
sums it all up.

Everything you create is private to your own account. The default currency is **PKR**,
set per account.

---

## 1. Getting started

### Create an account
On the registration page you provide:

| Field | Required | Rules |
|-------|----------|-------|
| Name | Yes | up to 255 characters |
| Email | Yes | valid, unique email |
| Password | Yes | at least 8 characters, with an uppercase letter, a lowercase letter, a number, and a special character |
| Confirm password | Yes | must match the password |

### Verify your email
After registering you **must verify your email** before you can reach the dashboard.
Click the link in the email Fintrax sends you. If it didn't arrive, you can resend the
link from **Settings → Profile**.

### What's already set up for you
The moment you register, Fintrax creates a starting point so you can begin immediately:

- A default account named **"Cash"** (PKR), marked as your default account.
- Ready-made categories you can use right away:
  - **Expense:** Food, Transport, Utilities, Shopping, Health, Entertainment, Rent, Other
  - **Income:** Salary, Freelance, Business, Gift, Other

These are "system" categories — you can use them anywhere, but they can't be edited or
deleted (you can always add your own).

### Signing in later
- **Login** with your email and password. Tick **Remember me** to stay signed in.
- **Forgot your password?** sends a reset link to your email.
- **Passkey sign-in** is supported if you've added a passkey (see Security settings).

---

## 2. Dashboard

Your financial overview at a glance:

- **Summary row** — Total balance (all accounts combined), Month income, Month expense,
  and Net this month (income − expense; green when positive, red when negative).
- **Active loans** — a strip of loans you haven't settled, with overdue ones highlighted.
- **Budget alerts** — budgets that are **80% or more** spent, so you notice them early.
- **Recent transactions** — your last 10 entries.

---

## 3. Accounts

`Accounts` page. An account is a place your money lives — cash, a bank, a mobile wallet,
or other.

Each card shows the account's color, name, current balance with currency, type, and a
**Default** badge on your default account.

**You can:**
- **Add / Edit an account** — fields:
  - **Name** (required)
  - **Type** — Cash, Bank, Mobile wallet, or Other
  - **Balance** (the starting/current balance)
  - **Currency** — a 3-letter code such as `PKR`
  - **Color**
- **Set default** — makes a non-default account your default.
- **Delete** — only if the account has **no transactions**. If it has transactions,
  Fintrax blocks the delete and tells you to remove or move them first.

---

## 4. Transactions

`Transactions` page. Every movement of money. Three types:

- **Income** — money coming into an account.
- **Expense** — money leaving an account.
- **Transfer** — money moving from one of your accounts to another.

**Filter the list** by search text (matches the note), type, account, category, and a
date range (from / to). The list is paginated.

**Add / Edit a transaction** — fields:

| Field | Notes |
|-------|-------|
| Type | Income, Expense, or Transfer |
| Amount | required, at least 0.01 |
| Account | the source account (required) |
| To account | **transfers only** — the destination account; must be different from the source |
| Category | optional; hidden for transfers |
| Note | optional |
| Date | required; defaults to today |

**Delete a transaction** — Fintrax asks you to confirm, because deleting it **reverses
its effect on the account balance** (e.g. deleting an expense adds the money back).

> Your account balances update automatically with every transaction you add, edit, or
> delete — you never adjust a balance by hand.

---

## 5. Loans

`Loans` page. Track money you've **lent** ("I lent") or **borrowed** ("I borrowed").

Filter by status: **Active**, **Settled**, or **All**. Each card shows the contact name,
direction, the remaining amount out of the total, and a status badge — **Settled**,
**Due (date)**, or **Overdue** when past the due date.

**Add / Edit a loan** — fields:

| Field | Notes |
|-------|-------|
| Direction | I lent / I borrowed |
| Contact name | required |
| Amount | required, at least 0.01 |
| Date loaned | required, today or earlier |
| Due date | optional |
| Note | optional |

**Log a payment** (on active loans) — fields:
- **Amount** — required; cannot exceed the remaining balance.
- **Date** — required.
- **Account** — optional. If you pick one, Fintrax also adjusts that account's balance
  (paying back a borrowed loan takes money out; receiving on a lent loan puts money in).
- **Note** — optional.

When the remaining reaches zero, the loan is **settled automatically**.

**Extend the due date** (on active loans) — set a **new due date** (must be later than
the current one) and an optional **reason**. Each extension is kept as history on the
loan card.

**Delete a loan** — also deletes its payment history (you'll be asked to confirm).

---

## 6. Budgets

`Budgets` page. Set a spending limit for a period and watch your progress.

Each card shows the budget name, the category it tracks (or all expense categories), and
a progress bar that's **green below 70%**, **yellow at 70–89%**, and **red at 90%+**,
with the percentage used.

**Add / Edit a budget** — fields:

| Field | Notes |
|-------|-------|
| Name | required |
| Category | optional — choose one expense category, or leave as **All expense categories** |
| Amount | required, at least 0.01 |
| Period | Monthly, Weekly, or Custom |
| Start date | required |
| End date | required **only** for Custom budgets |

How the period decides what counts:
- **Monthly** — through the end of the start month.
- **Weekly** — the 7 days starting on the start date.
- **Custom** — up to the end date you set.

Spending is the sum of your **expense** transactions in that window. If the budget has a
category, only that category's expenses count; otherwise all expenses do.

---

## 7. Categories

**Settings → Categories.** Manage the labels used to classify income and expenses.

Each row shows the color, name, type (**Income** / **Expense**), and a **System** badge
on the built-in ones.

**You can:**
- **Add a category** — Name, Type (Expense / Income), Color.
- **Edit / Delete** your own categories.
- **System categories cannot be edited or deleted** — only your custom ones.

---

## 8. Account & security settings

- **Profile** (`Settings → Profile`) — change your **name** and **email**. Changing your
  email requires verifying the new address again. You can also **delete your account**
  (available once your email is verified).
- **Security** (`Settings → Security`) — change your **password**, enable/disable
  **two-factor authentication (2FA)** with an authenticator app and view/regenerate
  **recovery codes**, and add or remove **passkeys**.
- **Appearance** (`Settings → Appearance`) — choose **Light**, **Dark**, or **System**
  theme.

---

## Good to know

- **Currency** defaults to **PKR** and is set per account.
- **Your data is private** — you only ever see your own accounts, transactions, loans,
  budgets, and categories.
- For the rules behind the automatic behavior (balance updates, transfers, loan
  settlement, budget periods), see **[how-it-works.md](how-it-works.md)**.
