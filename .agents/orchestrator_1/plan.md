# Orchestration Plan — AF/Rojname Modernization & Synchronization

## Objectives
1. Align mod_content and mod_category naming with latest AF/Business standard (mod_content and mod_content_category).
2. Port and sync core modules from AF/Business (mod_seo, mod_faq & mod_faq_mod, mod_newsletter, mod_settings).
3. Thoroughly clean up deprecated modules (mod_client, mod_service & mod_service_category, mod_product & mod_product_category, mod_reference & mod_reference_category) across all codebase directories.
4. Enforce strict repository policies (English code/comments, phone-only mobile admin UI, strict code separation, Registry usage, clean PHP syntax linting).

## Strategy & Workflow
- **Pattern**: Project Pattern (Dual Track: Implementation Track + E2E Testing Track)
- **Phase 0: Scope Survey**:
  - Explorer 1: Focus on mod_content & mod_content_category naming/structure + porting/syncing mod_seo, mod_faq, mod_newsletter, mod_settings from AF/Business.
  - Explorer 2: Focus on deprecated module removal (mod_client, mod_service, mod_product, mod_reference) across mod/, installer/, lib/, templates/admin/, helpers, ACL, nav.
  - Explorer 3: Focus on architectural compliance, code separation, comment language, phone-only admin layout, test runner requirements.
- **Phase 1: Project Blueprint (`PROJECT.md`)**:
  - Merge Explorer reports into Architecture, Feature Inventory, Milestones, Code Layout, Interface Contracts.
- **Phase 2: Execution via Sub-orchestrators & E2E Testing Track**:
  - Sub-orchestrators for modular milestones.
  - E2E Testing Orchestrator for opaque-box test suites (Tiers 1-4).
- **Phase 3: Integration & Final Milestone**:
  - 100% E2E test pass + Tier 5 adversarial verification.
