---
name: task-validation
description: Task Completion Validation - Enforce project quality guards (comment-guard, i18n-guard, policy-gate) before completing a task.
---

# Task Completion Validation (Task Validation)

## Purpose

This skill defines the checks required before completing a task in the repository. It ensures that all code quality gates are passed.

## Operating Rule

* **No Sibling Parity Checks:** Under no circumstances should this skill scan sibling folders or perform cross-repository parity comparisons. Those operations belong exclusively to the `sibling-parity-check` skill.
* **Frequency:** Run this skill checklist at the end of every active task, before proposing the walkthrough and finishing the task.

## Step-by-Step Sequence

1. **Execute Local Quality Guards:**
   * Run `comment-guard` to clean Turkish/Kurdish comments in touched files.
   * Run `i18n-guard` (`python3 tools/i18n_guard.py`) to check for hard-coded visible strings and missing language keys.
   * Run `code-separation-guard` (`python3 tools/code_separation_guard.py`) to enforce strict code separation (no inline styles or event handlers).
   * Run `min-css-guard` (`python3 tools/min_css_guard.py`) to verify `.min.css` bundle rule-set parity and absence of orphan `.min.css` artifacts.
   * Run `policy-gate` to consolidate final findings.
2. **Prepare Walkthrough:** Update or create the `walkthrough.md` artifact summarizing findings and verification results.
