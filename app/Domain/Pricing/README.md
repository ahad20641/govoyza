# Pricing

Selling prices are calculated, never typed (CLAUDE.md rule 3).

`PriceCalculator::quote()` selects the live primary supplier offer, then backup, then other live offers. It converts
the supplier cost to the quote currency using the configured exchange rate and buffer, applies the product's B2C
or B2B markup, and for B2B reduces that markup by the configured agency-tier percentage. Fixed product markups
are minor units in the product currency; percent markups are calculated on the converted supplier cost.

The embassy fee is converted without the safety buffer because it is a pass-through. VAT is calculated on the
service price only, not the embassy fee. VAT and tier reductions are exact decimal strings in `config/govoyza.php`;
both default to zero until the owner supplies approved rates. No jurisdiction or commercial tier rates are assumed.

- `PriceBreakdown` exposes service price, embassy fee, VAT and total. It never contains supplier cost or partner identity.
- `PriceBreakdown::toArray()` is the stable snapshot format for `orders.price_snapshot`; placement locks it permanently.
- Money maths uses `App\Support\Money\Money` and `App\Services\Money\CurrencyConverter`.
