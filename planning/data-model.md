# Data model

Money columns are always `*_minor BIGINT` + `currency CHAR(3)`. Sensitive columns use encrypted casts.
All tables get `id`, timestamps; business tables also get soft deletes where noted.

## Core design
A public **Product** (what customers buy) is separate from a partner's **SupplierOffer** (what a supplier
provides at a cost). This keeps suppliers hidden and lets us swap them per order.

## Tables

| Table | Key columns | Relations |
|---|---|---|
| `users` | name, email, phone, type (customer/agent_user/partner_user/admin), 2fa, locale | belongsTo agency / partner (nullable) |
| `agencies` | legal_name, trade_licence_path, tier, status, credit_limit_minor, currency | hasMany users, orders, wallet_transactions |
| `partners` | legal_name, status (pending/active/suspended), kyc_status, bank_details (encrypted), sla_score, agreement_version, agreement_signed_at, agreement_ip | hasMany users, supplier_offers, jobs, payouts |
| `countries` | iso2, name, region (schengen/…), slug, seo content | hasMany products |
| `visa_types` | slug (tourist/medical/business/student/work/transit/family), name | hasMany products |
| `products` | country_id, visa_type_id, nationality_iso2 (nullable = any), public_name, description, processing_days_min/max, validity, b2c_markup (type+value), b2b_markup (type+value), embassy_fee_minor+currency, status (draft/live/paused), slug | hasMany supplier_offers, requirements, orders |
| `supplier_offers` | product_id, partner_id, cost_minor+currency, processing_days, capacity_per_week, priority (primary/backup/n), status (draft/pending/live/rejected/paused), inclusions, refund_terms | belongsTo product, partner; hasMany offer_revisions |
| `offer_revisions` | supplier_offer_id, payload (json), status (pending/approved/rejected), reviewer_id, comment | — |
| `requirements` | product_id, label, doc_type, is_required, rules (json: formats, max_mb, passport_valid_months) | — |
| `orders` | number, buyer_type (user/agency), buyer_id, product_id, channel (b2c/b2b), tier, status, price_snapshot (json breakdown), total_minor+currency, travel_date | hasMany applicants, payments; hasOne job |
| `applicants` | order_id, saved_traveller_id (nullable), given_names, surname, passport_number (enc), nationality, dob (enc), passport_expiry | hasMany documents |
| `saved_travellers` | user_id, same passport fields (enc) | — |
| `documents` | applicant_id, requirement_id, disk, path, mime, size, version, check_status (pending/ok/flagged), flag_reason | — |
| `service_jobs` (model `ServiceJob`; `jobs` is taken by the Laravel queue) | order_id, supplier_offer_id, partner_id, status, sla_due_at, assigned_at, closed_at, cost_snapshot_minor+currency | hasMany job_updates |
| `service_job_updates` | service_job_id, status, note, appointment_at, result_document_id | — |
| `payments` | order_id or agency_id, gateway, gateway_ref, amount_minor+currency, status, raw (json), idempotency_key | — |
| `wallet_transactions` | agency_id, type (topup/order/refund/adjust), amount_minor+currency, balance_after_minor, order_id | — |
| `ledger_accounts` | code, name, type (asset/liability/equity/revenue/expense) | hasMany journal_lines |
| `journal_entries` | posted_at, event (string), reference_type/id, memo, reversed_entry_id | hasMany journal_lines (immutable) |
| `journal_lines` | journal_entry_id, ledger_account_id, debit_minor, credit_minor, currency, partner_id/agency_id (nullable) | — |
| `payouts` | partner_id, period, total_minor+currency, status (draft/approved/paid), approved_by, paid_at | hasMany payout_items |
| `exchange_rates` | base, quote, rate, buffer_pct, effective_at | — |
| `messages` | order_id, author_id, audience (customer/partner/internal), body | — |
| `notifications_log` | user_id, channel (email/sms/whatsapp), template, status | — |
| `audit_logs` | actor_id, action, subject_type/id, before/after (json), ip | — |
| `pages`, `posts`, `coupons` | CMS and promos | — |

## Indexes and constraints
- Unique: `orders.number`, `products.slug`, `(supplier_offers.product_id, partner_id)`.
- `journal_lines`: DB check that each line has exactly one of debit/credit > 0; app-level guard that entry is balanced per currency.
- Foreign keys everywhere; `restrictOnDelete` on anything money-related.
