---
name: task-validation
description: Task Completion & Release Validation - Verify VERSION.md updates based on Git history and enforce project quality guards (comment-guard, i18n-guard, policy-gate) before completing a task.
---

# Task Completion & Release Validation (Task Validation)

## Purpose

This skill defines the checks and release gates required before completing a task in the repository. It ensures that development notes are correctly logged and that all code quality gates are passed.

## Operating Rule

* **No Sibling Parity Checks:** Under no circumstances should this skill scan sibling folders or perform cross-repository parity comparisons. Those operations belong exclusively to the `sibling-parity-check` skill.
* **Frequency:** Run this skill checklist at the end of every active task, before proposing the walkthrough and finishing the task.

## Step-by-Step Sequence

1. **Version History Check:**
   * Run `git log` or `git describe` to find the last tag or version code that was pushed/uploaded to GitHub.
   * Open `/docs/VERSION.md`.
   * Verify that the historical section corresponding to the latest Git-uploaded version is intact (frozen).
2. **Version Note Appending:**
   * If there is an open next/unpublished section in `VERSION.md` corresponding to the active unpublished version, append the new task changes there.
   * If there is no open next/unpublished version section, create the next version code section and add the development notes under it.
   * **Note Language:** All version notes appended to `/docs/VERSION.md` MUST be written in English. Never use Kurdish or Turkish in `/docs/VERSION.md` even if communicating with the user in Kurdish.
3. **Execute Local Quality Guards:**
   * Run `comment-guard` to clean Turkish/Kurdish comments in touched files. Ensure all comments, function names, variable names, translation keys, and documentation (except `implementation_plan.md`) are in English.
   * Run `i18n-guard` to check for hard-coded visible strings.
   * Run `policy-gate` to consolidate final findings.
4. **Prepare Walkthrough:** Update or create the `walkthrough.md` artifact summarizing findings and verification results.
