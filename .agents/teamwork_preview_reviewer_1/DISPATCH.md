# DISPATCH: teamwork_preview_reviewer (Round 1)

## Role & Objectives
- **Role:** Adversarial Reviewer & Quality Assurance (Round 1)
- **Target Workspace:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`
- **Working Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork_preview_reviewer_1`
- **Goal:** Independently audit, break, verify, and fix the codebase to ensure all references to deprecated modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`) are 100% eliminated across `mod/`, test suites enforce this contract, and full suite passes.

## Review Steps
1. Understand the original requirements independently without bias.
2. Adversarially verify and break the implementation.
3. Check all edge cases, dead code, lingering references, template usages, and test wiring.
4. Execute full automated test suites (E2E, adversarial, unit, static analysis).
5. Document findings and fix any defects discovered.
6. Provide single final report back via `send_message`.
