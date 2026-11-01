# Orders

From "Apply now" to "visa delivered".

- `OrderStateMachine`: the only code that changes `orders.status` (CLAUDE.md rule 5). Allowed transitions,
  who may trigger them, history rows and events are in `planning/order-lifecycle.md`. Listeners do the side
  effects (invoice, ledger, notifications, routing), never controllers.
- `JobRouter`: picks the primary (else backup) supplier offer and opens a `ServiceJob` (task 1.20).
- Models: `Order`, `OrderStatusHistory`, `Applicant`, `SavedTraveller`, `Document`, `ServiceJob`,
  `ServiceJobUpdate`, `Message`.

Privacy: partners see order number, applicant names and assigned documents only, until the job closes
(CLAUDE.md rule 2). Documents live on the private disk behind signed URLs (rule 6).

Status: **stubs** (throw `NotBuiltYet`). Built in Area B (Orders + Documents): tasks 1.16–1.22.
