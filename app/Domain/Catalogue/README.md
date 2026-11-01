# Catalogue

What Govoyza sells and who supplies it.

- **Product**: what customers buy (country + visa type + optional nationality), with Govoyza's name, copy,
  markups and embassy fee. Public. Model: `App\Models\Product`.
- **SupplierOffer**: a partner's private offer behind a product (cost, capacity, priority). Never public
  (CLAUDE.md rule 1). Edits to a live offer become an `OfferRevision` until admin approves.
- **Requirement**: the document checklist per product (task 1.11).

Services here:
- `SupplierOfferWorkflow`: partner creates, edits and submits offers (task 1.9).
- `OfferReviewWorkflow`: admin review queue: approve (sets markups, copy, priority and publishes), request
  changes, reject; revision approvals (task 1.10).

Screens: admin Catalogue group, partner "My services". Specs: `planning/blueprint.md`
("Supplier listing & approval flow"), `planning/data-model.md`.
