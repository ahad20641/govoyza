# Roadmap

Launch a lean core that earns money, then add automation in layers.
Each phase ends with a **gate** — don't start the next phase's big features until the gate is met
(small fixes and prep are fine).

| Phase | File | Scope | Gate | Status |
|---|---|---|---|---|
| 0 Foundations | `phase-0-foundations.md` | Business setup (owner) + dev environment | Repo, hosting, domains, gateway sandboxes ready | In progress (owner) |
| 1 MVP | `phase-1-mvp.md` | Public site, customer + partner portals, admin, orders, payments, invoices, email, basic ledger | First 50 paid orders end to end without spreadsheets | **Current** |
| 2 B2B & money automation | `phase-2-b2b-money.md` | Agent portal, wallet/credit, tiers, white-label PDFs, payouts, reconciliation, SMS + WhatsApp | 10 active agencies; payouts in one weekly click | Not started |
| 3 Hands-off ops | `phase-3-automation.md` | OCR, document checks, AI chat, SLA escalation, smart routing, digest, reviews/renewals | One person handles daily volume in < half a day | Not started |
| 4 Scale | `phase-4-scale.md` | Agent API, mobile app, languages, more destinations, affiliates, corporate | — | Not started |

Supporting specs (read when the task touches them):
- `data-model.md` — tables and relationships
- `order-lifecycle.md` — statuses, transitions, side effects
- `accounting-rules.md` — chart of accounts, journal entry per event
- `security.md` — security and privacy requirements
