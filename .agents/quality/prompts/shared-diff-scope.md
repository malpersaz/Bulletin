# Shared Scope Policy

Use mixed review scope.

Rules:

- Inspect only the current diff or explicitly provided changed files.
- Fully review newly added files.
- In modified existing files, review only changed hunks for normal policy checks.
- In modified existing files, also scan the full file for:
  - non-English comments
  - non-English hard-coded user-facing strings
- Do not report pre-existing legacy violations outside the current diff.
- Do not propose broad cleanup outside the active task.
- Only mention a legacy issue if:
  - the user explicitly asked for a legacy audit
  - the current change directly interacts with it
  - it creates an immediate runtime, security, or release risk
  - it is a non-English comment or non-English hard-coded user-facing string inside a touched file

Output rules:

- Report only concrete findings.
- Keep normal policy findings diff-scoped.
- Allow file-wide findings only for non-English comments and non-English hard-coded user-facing strings in touched files.
- Prefer short, actionable findings.
- If there are no findings, say that explicitly.
