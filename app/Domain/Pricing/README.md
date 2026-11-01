# Pricing

Selling prices are calculated, never typed (CLAUDE.md rule 3).

supplier cost + embassy/VFS fee (pass-through, shown separately) + Govoyza markup (fixed or %, B2C or B2B
tier) + optional gateway fee recovery + VAT, converted to BDT/USD/EUR at the admin rate with its buffer.

- `PriceCalculator::quote()` returns a `PriceBreakdown` (no supplier cost in it, because it reaches customers).
- The breakdown is stored on the order as `price_snapshot` at `placed` and never changes after.
- Money maths uses `App\Support\Money\Money` and `App\Services\Money\CurrencyConverter`.

Status: **stub** (throws `NotBuiltYet`). Built in task 1.12, Area A (Catalogue + Pricing).
