# Order lifecycle

One state machine (`App\Domain\Orders\OrderStateMachine`). Controllers and Filament actions call it;
they never set `status` directly. Each transition fires an event; listeners do the side effects.

| # | Status | Entered when | Who triggers | Automatic side effects (listeners) |
|---|---|---|---|---|
| 1 | `placed` | Customer/agent submits applicant form | Customer / Agent | Order number, price snapshot locked, checklist generated |
| 2 | `paid` | Gateway webhook or wallet deduction succeeds | System | Invoice PDF, receipt email (SMS later), journal: payment received |
| 3 | `documents_pending` | Immediately after paid | System | Reminders at 24h / 72h; auto-checks (type, size, passport expiry) |
| 4 | `under_review` | All required documents uploaded | System | Appears on admin Today board |
| — | (back to 3) | Admin flags missing/blurry items | Admin | Email customer with reasons |
| 5 | `assigned` | Admin approves completeness | Admin (1 click) → System routes | Job created for chosen offer, partner notified, journal: cost of sales / supplier payable, contacts masked |
| 6 | `in_process` | Partner marks submitted to embassy/VFS | Partner | Customer notified; SLA timer runs |
| 7 | `appointment` | Partner sets date + uploads letter | Partner | Calendar invite + reminder to applicant |
| 8 | `decision` | Partner records Approved / Rejected / Additional docs | Partner | Result file delivered; review request (Phase 3) |
| — | (back to 3) | Decision = Additional docs | Partner | Customer asked for new documents |
| 9 | `completed` | Passport returned / e-visa delivered | Partner or Admin | Revenue recognised, payable becomes due (after holding period), profit posted, partner access to files revoked |
| — | `cancelled` | Before `assigned`, per refund policy | Admin | Refund (gateway or wallet), reversing journal |
| — | `refunded` | After `assigned`, per refund policy | Admin (Owner/Finance) | Partial/full refund, reversing journals, job cancelled |

## Rules
- Allowed transitions only as listed; anything else throws `InvalidTransition`.
- `decision` outcome stored separately (`approved`/`rejected`/`additional_docs`). Visa rejection is still `completed` (service delivered) unless refund policy says otherwise.
- Every transition writes an `order_status_history` row (from, to, actor, reason, at).
- Listeners are queued and idempotent (safe to run twice).
- Price snapshot never changes after `placed`.

## Terms to show customers
Service fee covers processing and expertise, not the visa decision. Embassy fees passed through at cost and shown separately.
