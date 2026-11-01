# Phase 3 — Hands-off operations (1–2 months)

**Gate:** one person handles daily volume in under half a day.

- [ ] **3.1 Passport MRZ OCR** — read passport scan into applicant form (external OCR API behind an interface); user confirms fields.
- [ ] **3.2 Automatic document checks** — passport expiry vs travel date + validity rule, blank pages declaration, file quality (resolution/blur), wrong file type.
- [ ] **3.3 Smart routing** — choose supplier by service, capacity, SLA score, cost; admin override.
- [ ] **3.4 SLA escalations** — timers per status; late → remind supplier → escalate to admin → suggest reassignment.
- [ ] **3.5 Partner performance scores** — on-time rate, rejection rate, rating; shown to partner and used by routing.
- [ ] **3.6 AI chat assistant** — answers from the service catalogue and FAQs only (retrieval over our own data); hands off to human; never reveals suppliers or promises approval.
- [ ] **3.7 WhatsApp status bot** — customer sends order number → gets status.
- [ ] **3.8 Daily digest email** — sales, profit, open orders, late jobs, items to approve.
- [ ] **3.9 Reviews and repeat sales** — review request on completion; renewal and "next trip" reminders; abandoned-checkout reminders.
- [ ] **3.10 AI-drafted visa news** — drafts for admin edit and publish; never auto-published.
