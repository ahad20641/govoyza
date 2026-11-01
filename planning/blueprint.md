# Govoyza — Platform Blueprint (condensed for builders)

Source: "Govoyza — Platform Blueprint & Mind Map" (owner's planning doc, 2026-11-01).
This file is the builder's version. If they disagree, ask the owner and log a decision.

## Business model
Supplier cost + Govoyza markup (B2C) or lower agent markup (B2B) = selling price.
Profit recorded automatically on every order. Based in Bangladesh; sell in BDT with USD/EUR shown.

**Service categories:** Tourist, Medical, Business, Student, Work Permit, Transit, Family/Spouse.
**Add-ons:** document translation, attestation, flight/hotel reservation letters (from real providers), travel insurance, appointment booking.
**Headline markets:** Schengen/Europe, USA, Canada, UK, Australia (all countries supported).

## Portals (one codebase, one DB)
| Portal | Address | Who | Visibility | Sign-up |
|---|---|---|---|---|
| Public website | govoyza.com | Anyone | Public, SEO-indexed | Not needed to browse |
| Customer portal | govoyza.com/account | B2C travellers | Logged-in | Open, email/phone OTP |
| Agent portal | govoyza.com/agent | Travel agencies | Logged-in | Register + trade licence; admin approves |
| Partner portal | partner.govoyza.com | Suppliers | Hidden: no links, `noindex`, not in sitemap | Invite link or direct URL; admin approves (KYC) |
| Admin console | admin.govoyza.com | Owner + 1 staff | 2FA, IP-restrictable | Created by owner only |

**Admin roles:** Owner (everything), Operations (orders, documents, customers), Finance (read-only accounts + payout approval).

## Site map
**Public:** Home with search "I am a [nationality] going to [country] for [purpose]" · Visa finder results · Country pages `/visa/{country}` auto-generated from catalogue (main SEO engine) · Visa-type pages · Service detail (requirements, timeline, price, FAQs, Apply now) · Add-ons · Track application (order no. + email, no login) · Blog/visa news, FAQ, About, Contact, Terms, Privacy, Refund policy · "Become a B2B agent" (no mention of suppliers).

**Customer /account:** dashboard with status bars · application detail (timeline, checklist, uploads, messages) · saved travellers (passport data stored once) · payments & invoice PDFs · support tickets, profile, notification settings.

**Agent /agent:** dashboard (orders by status, wallet, monthly spend) · B2B net price list + bulk/group form · apply for a client · wallet (top-up, credit limit, statement) · white-label PDFs with agency logo · sub-users, commission reports, API key (later).

**Partner portal:** onboarding + KYC · my services (Draft / Pending review / Live / Rejected) · assigned jobs (order no. + applicant name only) · document exchange · job status updates (trigger customer notifications) · earnings & payouts · performance (on-time, rejection rate, rating).

**Admin:** Today board (orders needing action, SLA breaches, listings to approve) · catalogue (approve listings, set markups, publish) · orders (lifecycle, reassign supplier, refunds) · users (customers, agents, partners) · accounting (ledger, P&L, receivables, payables, payouts, tax) · CMS (country pages, blog, banners, coupons) · settings (gateways, templates, exchange rates, roles).

## Supplier listing & approval flow
1. Supplier registers at partner portal, submits KYC, e-signs agreement.
2. Admin approves supplier (Pending → Active).
3. Supplier lists a service: country, visa type, applicant nationality, requirements, processing time, validity, cost price, inclusions, refund terms.
4. Admin review queue shows it side by side with existing offers for same country + visa type.
5. Approve / Request changes (comment to supplier) / Reject.
6. On approval admin sets B2C markup, B2B markup, public name/description in Govoyza voice, primary vs backup supplier.
7. Publish → appears on public site, country page and agent price list automatically.
8. Supplier edits after publishing stay unpublished until re-approved; cost increases flagged on Today board.

One public Product can map to several SupplierOffers; routing picks primary, else backup if primary is at capacity, late on SLAs or paused.

## Pricing
Supplier cost (supplier currency) + embassy/VFS fee (pass-through, shown separately) + Govoyza markup (fixed or %, per service/country/category) + optional gateway fee recovery + VAT = selling price, converted to BDT/USD/EUR at the admin-set rate (daily auto-update with safety buffer).

| Tier | Who | Markup |
|---|---|---|
| Retail | B2C | Full |
| Agent Silver | New agencies | Reduced |
| Agent Gold | Above monthly volume | Lower |
| Agent Platinum / Corporate | Negotiated | Custom |

Tier upgrades automatic from monthly volume. Coupons are B2C-only by default.
**Agent wallet:** prepaid top-up (bank, bKash, card); orders deduct instantly; trusted agencies get a credit limit with auto-block when overdue.

## Automations (the "website is the employee" list)
Lead capture & abandoned-checkout reminders · AI chat assistant + WhatsApp status bot · dynamic document checklist, passport MRZ OCR, file checks · rules-based supplier routing · supplier update → email + SMS + WhatsApp + timeline · SLA timers with escalation · gateway webhooks, wallet auto-deduct, overdue auto-block · invoice/receipt/credit-note PDFs · journal entries + auto-reconcile · weekly payout batch (approve once) · review requests + renewal reminders · catalogue-generated country pages + AI-drafted news · daily email digest.

## Legal guardrails built into the product
- Terms: "we do not guarantee visa approval"; service fee covers processing, not the decision; embassy fees at cost shown separately.
- Never forge or "arrange" documents. Work-permit offers require proof the supplier is licensed.
- Supplier agreement: confidentiality, non-solicit of customers, SLA, data handling.
