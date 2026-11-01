---
description: Update the memory folder at the end of a work session
---

Update the project memory so the next session can pick up cold:

1. `memory/project-state.md` — rewrite the snapshot: last updated date, current phase, current task, branch, what exists, environment status, waiting on owner, open questions, next 3 tasks. Keep it under ~60 lines.
2. `memory/progress-log.md` — add a dated entry at the top: what was done this session (2–5 bullets) and what's next.
3. `memory/decisions.md` — append any new architectural/product decision as the next D-number (never edit old ones; supersede instead).
4. `memory/known-issues.md` — add any gotcha that cost time, with its fix.
5. Commit only the memory/planning changes: `chore(memory): update project state`.

$ARGUMENTS
