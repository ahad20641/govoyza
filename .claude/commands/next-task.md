---
description: Pick up the next unchecked task from the current phase and build it
---

1. Read `memory/project-state.md`, `planning/roadmap.md` and the current phase file in `planning/`.
2. Find the first unchecked task (or the one named here: $ARGUMENTS). If it is blocked on the owner, note it under "Waiting on owner" in `memory/project-state.md` and take the next one.
3. Read only the supporting specs that task needs (`data-model.md`, `order-lifecycle.md`, `accounting-rules.md`, `security.md`, `deploy/hostinger.md`).
4. Show a short plan (files to create/change, tests to write) — then build it without waiting unless the task is irreversible or the spec is ambiguous.
5. Create branch `feat/<task-number>-<slug>` from `develop`.
6. Implement with Pest tests. Run `php artisan test` and `./vendor/bin/pint` until both pass.
7. Commit with a conventional message, push the branch, and open a PR into `develop` with `gh pr create` if `gh` is available.
8. Tick the task checkbox in the phase file and run the steps in `/save-memory`.
