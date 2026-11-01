# Agencies

B2B travel agencies buying at net prices through the agent portal (`/agent`).

- Registration with trade licence and admin approval exists (task 1.5).
- `Wallet`: prepaid balance, instant deduction per order, credit limit with auto-block (Phase 2).
- Tiers (`App\Enums\AgencyTier`: silver, gold, platinum) pick the B2B markup; upgrades from monthly
  volume come in Phase 2.
- Sub-users (`users.agency_id`), white-label PDFs and reports are Phase 2.

Status: **stub** (`Wallet::charge()` throws `NotBuiltYet`). Screens are S1 shells under
`resources/views/agent`.
