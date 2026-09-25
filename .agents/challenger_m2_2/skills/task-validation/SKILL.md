---
name: task-validation
description: Task Completion Validation - Enforce project quality guards (comment-guard, i18n-guard, policy-gate) before completing a task.
---

# Task Completion Validation (Task Validation)

## Purpose
This skill defines the checks required before completing a task in the repository.

## Step-by-Step Sequence
1. Execute Local Quality Guards:
   - comment-guard (no non-English comments in touched files)
   - i18n-guard (`python3 tools/i18n_guard.py`)
   - code-separation-guard (`python3 tools/code_separation_guard.py`)
   - policy-gate
2. Prepare verification results.
