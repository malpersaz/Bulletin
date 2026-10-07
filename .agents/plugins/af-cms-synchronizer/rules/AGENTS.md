# AF CMS Synchronizer Rules (AF/Rojname Adopter Workspace)

## 1. Upstream-Downstream Model & Authority
- **Canonical Upstream Reference**: `AF/Business` is the authoritative source for shared-core components (`lib/`, shared installer logic, base tools).
- **Adopting Role**: `AF/Rojname` pulls and mirrors shared-core improvements while strictly preserving local domain autonomy (journalism, newspaper publishing, articles, headlines, press columns).
- **Zero Autonomous Cross-Repo Write**: The agent must NEVER write to sibling repositories autonomously. All cross-repo reading from `AF/Business` is strictly READ-ONLY.

## 2. Domain Boundaries & Forbidden Modules
- **Rojname Domain Ownership**: Specializes in news journalism (`mod_news`, `mod_news_category`), headlines (`mod_headline`), publications (`mod_publication`, `mod_publication_category`), videos, pages, surveys, and press archives.
- **Forbidden Commercial, Content & Assessment Modules**:
  The following modules belong exclusively to other repositories and MUST NOT be introduced, imported, seeded, or referenced in `AF/Rojname`:
  - `mod_content`, `mod_content_category`
  - `mod_product`, `mod_product_category`
  - `mod_service`, `mod_service_category`
  - `mod_client`, `mod_reference`
  - `mod_project`, `mod_project_category`
  - `mod_appointment`, `mod_reservation`, `mod_hr`, `mod_organization_type`
  - `mod_test`, `mod_assessment`
  - `mod_hero_slider`
- **Module Boundary Enforcement**: Any attempt to introduce foreign modules will be blocked by `tools/module_boundary_guard.py` and the `PreToolUse` hook.

## 3. Deterministic Verification (Zero-Hallucination Protocol)
- Do not assume class methods, parameters, or database schemas exist without verifying the code.
- Run `python3 tools/cross_repo_sync.py --sibling /path/to/AF/Business` to get exact byte and AST drift reports.
- All PHP changes must pass `php -l` syntax validation.
- All template changes must pass `python3 tools/code_separation_guard.py`.

## 4. Architectural Invariants
- **Phone-Only Admin Surface**: All views under `templates/admin/` are phone-only mobile viewport surfaces.
- **Strict Code Separation**: No inline styles (`style="..."` or `<style>`) and no inline JavaScript (`onclick="..."`, inline `<script>`) in HTML, Twig, or PHP files.
- **i18n Key Protocol**: Translation keys must be descriptive English in `en.php`.
- **Wikipedia-Style SEO URLs**: Preserve original casing and characters in slugs, using underscores instead of forced hyphens.
