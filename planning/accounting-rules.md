# Accounting rules (double-entry)

Every money event posts one balanced journal entry via `App\Domain\Ledger\Poster`.
Entries are immutable; corrections are reversing entries linked by `reversed_entry_id`.
All posting happens inside the same DB transaction as the business change, or in an idempotent
queued listener keyed by (event, reference) so it can never double-post.

## Chart of accounts (seed)
| Code | Account | Type |
|---|---|---|
| 1000 | Bank | Asset |
| 1100 | Gateway clearing — SSLCommerz | Asset |
| 1110 | Gateway clearing — Stripe | Asset |
| 1200 | Agent receivables (credit) | Asset |
| 2000 | Customer deposits (unearned) | Liability |
| 2100 | Agent wallet liability | Liability |
| 2200 | Supplier payable | Liability |
| 2300 | Embassy fees payable / pass-through | Liability |
| 2400 | VAT payable | Liability |
| 3000 | Owner's equity | Equity |
| 4000 | Service revenue — B2C | Revenue |
| 4010 | Service revenue — B2B | Revenue |
| 5000 | Cost of sales — supplier cost | Expense |
| 6000 | Gateway fees | Expense |
| 6100+ | Operating expenses (rent, ads, salaries, software) | Expense |

## Posting rules
| Event | Debit | Credit |
|---|---|---|
| Customer pays by gateway | Gateway clearing | Customer deposits (unearned) |
| Gateway settles to bank | Bank + Gateway fees | Gateway clearing |
| Agent tops up wallet | Bank (or gateway clearing) | Agent wallet liability |
| Agent order placed (wallet) | Agent wallet liability | Customer deposits (unearned) |
| Agent order placed (credit) | Agent receivables | Customer deposits (unearned) |
| Order assigned to supplier | Cost of sales | Supplier payable |
| Order completed | Customer deposits | Service revenue (B2C/B2B) + Embassy fee pass-through + VAT payable |
| Supplier payout | Supplier payable | Bank |
| Refund | Customer deposits / revenue reversal | Bank, gateway clearing or wallet liability |
| Expense recorded | Expense account | Bank |

## Multi-currency
Each journal line carries its currency. An entry must balance **per currency**. Reports convert at the
rate stored on the entry (`rate_to_bdt`). FX differences post to an FX gain/loss account (add `7000 FX gain/loss` when first needed — log a decision).

## Reports (derived, never stored)
P&L by month / service / country / supplier / channel · balance sheet · cash flow · agent receivables ageing ·
supplier payables due · VAT summary · per-order profit (revenue − cost of sales − allocated gateway fee).

## Tests that must exist
- Every posting rule above has a test asserting the exact lines.
- Unbalanced entry throws.
- Posting the same event twice creates one entry.
- Trial balance sums to zero after a seeded full order lifecycle including a refund.
