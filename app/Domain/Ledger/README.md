# Ledger

Double-entry bookkeeping for every money event (CLAUDE.md rule 4, `planning/accounting-rules.md`).

- `LedgerPoster::post()` writes one balanced journal entry (lines per currency must balance) in a DB
  transaction. `reverse()` corrects with a reversing entry; entries are never edited or deleted.
- Called from listeners on order and payment events.
- Models: `LedgerAccount`, `JournalEntry`, `JournalLine`, `Payment`, `Payout`, `PayoutItem`.
- The payment gateway layer (SSLCommerz, Stripe; task 1.23) will sit in `app/Domain/Payments` and hand
  confirmed payments to the state machine, which triggers the posting.

Status: **stub** (throws `NotBuiltYet`). Built in Area C (Payments + Ledger): tasks 1.23–1.27.
