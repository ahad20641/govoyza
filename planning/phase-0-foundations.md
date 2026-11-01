# Phase 0 — Foundations (2–4 weeks)

Mostly owner tasks. Claude can help draft documents but cannot do the registrations.
Tick boxes as they're done; move blockers to `memory/project-state.md` → "Waiting on owner".

## Business (owner)
- [ ] Company registration and trade licence covering travel/visa consultancy
- [ ] Check need for travel-agency registration / ATAB membership (ask a Bangladeshi lawyer)
- [ ] e-TIN / BIN and VAT registration
- [ ] Business bank account
- [ ] Apply for payment gateways: SSLCommerz, bKash merchant, Nagad merchant, Stripe (or alternative for international cards)
- [ ] Sign 5–10 trusted suppliers offline (screen for licensing; no document "arranging")
- [ ] Choose top 5 launch destinations and visa types
- [ ] Brand: logo, colours, tone of voice

## Accounts & infrastructure (owner, Claude guides)
- [ ] GitHub account + private repo `govoyza` (owner owns it)
- [ ] Hostinger plan confirmed (Business/Cloud or VPS) — owner owns the account
- [ ] Domain govoyza.com on Hostinger (or DNS pointed there), SSL enabled
- [ ] Subdomains in hPanel: `partner`, `admin`, `staging` (and `partner-staging`, `admin-staging` if wanted)
- [ ] SSH access enabled; deploy key added (see `deploy/hostinger.md`)
- [ ] MySQL databases: `govoyza_prod`, `govoyza_staging`
- [ ] Transactional email provider (SMTP from Hostinger mail to start, Postmark/SES later)
- [ ] Cloudflare in front of the domain (optional but recommended: WAF, DDoS)

## Legal documents (Claude can draft, lawyer reviews)
- [ ] Terms of service (no visa-approval guarantee; refund policy)
- [ ] Privacy policy (document storage, sharing with processing partners)
- [ ] Refund policy
- [ ] Supplier agreement (confidentiality, non-solicit, SLA, data handling)
- [ ] Agent agreement (wallet/credit terms, white-label rules)
