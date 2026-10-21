# Implementation & Refinement Plan

## Task Objective
Fix the fatal error `Call to undefined method Run::execute()` in `AF/Bulletin` by modernizing the `Run` class (`lib/Run.class.php`) and aligning core execution patterns with `AF/Business`, ensuring zero Turkish identifiers/methods remain in touched core files (`lib/Run.class.php`, `lib/Validator.class.php`) while respecting strict repository boundaries (no `mod_client`, `mod_service`, `mod_product`, `mod_reference` or related code) and validating with full e2e test suite passing 100%.

## SWE Light Sequential Execution Plan
1. **Round 0: Primary Implementation**
   - Agent: `teamwork_preview_implementer`
   - Goal: Investigate `lib/Run.class.php`, `lib/Validator.class.php`, and references across `AF/Bulletin` (comparing with `AF/Business` as needed). Implement `Run::execute()`, modernize methods in touched files, ensure repository boundaries, and run `php tests/e2e/run.php`.
2. **Round 1: Reviewer 1 (Adversarial Stress-Test & Fix)**
   - Agent: `teamwork_preview_reviewer`
   - Goal: Verify implementation, inspect diff, test edge cases, fix any issues, and re-run test suite.
3. **Round 2: Reviewer 2 (Deep Verification & Parity Check)**
   - Agent: `teamwork_preview_reviewer`
   - Goal: Check language hygiene, strict boundary isolation, backward compatibility, and re-run test suite.
4. **Round 3: Reviewer 3 (Final Polish & Consistency Check)**
   - Agent: `teamwork_preview_reviewer`
   - Goal: Third mandatory review round ensuring zero regression, clean code separation, and passing tests.
5. **Orchestrator Independent Verification & Victory Audit**
   - Re-run test suite independently.
   - Dispatch `teamwork_preview_victory_auditor` for blocking confirmation.
6. **Handoff & Completion**
   - Write handoff.md and report completion to parent agent.
