# Cross-Repo Synchronization Policy (AF CMS Family)

Decided by the user on 2026-09-16.

## Model

- **One-way**: `AKM Business CMS` (`AF/Business`) is the **canonical shared-core
  reference**. Adopting sibling repositories (`AF/Bulletin`, Association, Artist,
  Property, Rojname) adopt Business shared-core changes.
- **Uniqueness is preserved through boundaries**: `AF/Bulletin` keeps its own
  journalism domain modules (`mod_news`, `mod_news_category`, `mod_headline`,
  `mod_publication`, `mod_publication_category`, `mod_survey`), editorial
  curation skills (`kevanger`, `kevkolin`, `kevrast`, `kevseo`), installer
  catalogs, sample seed content, brand tokens, guard allowlists, and public
  theme (`amida`). Commercial and foreign modules are never ported into `AF/Bulletin`.

## Shared Core vs Per-Repo

| Layer | Policy |
| :--- | :--- |
| `lib/*.php` core classes | mirror (canonical = Business) |
| `lib/McpServer`, `lib/LlmsService`, `lib/ModuleRegistry`, `lib/AdminTemplate` | adapted (domain tools/sections/catalogs) |
| `tools/i18n_guard.py`, `tools/module_boundary_guard.py`, `tools/audit_modernization.py` | adapted (per-repo allowlists / forbidden lists / module sets) |
| `tools/cross_repo_sync.py` | mirror (identical everywhere) |
| `installer/app/*` (except Catalog/StateStore/ConfigWriter), `installer/index.php` | mirror |
| `installer/installerbase.php` | adapted (Association holds the `getRow()` modernization; reverse backlog) |
| `installer/lang/`, `installer/mod_*` | adapted (module labels + sample seeds per repo) |
| `config/`, `mod/`, `templates/`, `static/` | adapted (per-repo by nature) |

The authoritative, commented ledger lives in `tools/cross_repo_sync.py` (`POLICY`
table). Keep the table identical in every repository.

## Tooling

```bash
# run from AF/Bulletin (adopting repository):
python3 tools/cross_repo_sync.py --sibling /path/to/AF/Business            # text report; exit 1 on drift
python3 tools/cross_repo_sync.py --sibling /path/to/AF/Business --json     # machine-readable
```

- `DRIFT` / `MISSING LOCALLY` -> must be ported from the canonical repository.
- `ADAPTED` -> expected differences (visibility only).
- `DOMAIN_LOCAL` -> the other repository's domain modules; never portable.

## Obligations

1. When a shared-core change lands in Business, port it to adopting repositories
   in the same pass, or clear `cross_repo_sync.py` before closing the task.
2. When a file legitimately becomes per-repository, move it to the `adapted`
   list with a reason in both copies of the tool.
3. Never copy dead code for parity's sake; record it as backlog instead.

## Known Coordinated Backlog (both repositories)

- `coloms` -> `columns` DB-layer rename in `lib/Database`, `lib/Orm`, and
  `Make_Model` must happen in ALL repositories in one coordinated pass
  (Business's generator rename is currently half-done).
- Business calls `TwigTemplate::clearTpl()` but does not define it (latent fatal
  in the `AmedError` render path); the method exists in Association.
- Both `installerbase.php` files emit `installer-msg-*` classes; Business's
  `installer/assets/css/installer.css` lacks their definitions.

## Boundary Summary

- **Bulletin-owned domains (`AF/Bulletin`)**: journalism and newspaper publishing modules (`mod_news`, `mod_news_category`, `mod_headline`, `mod_publication`, `mod_publication_category`, `mod_survey`), editorial curation skills (`kevanger`, `kevkolin`, `kevrast`, `kevseo`), and the `amida` public frontend theme.
- **Forbidden foreign/commercial modules in `AF/Bulletin`**: `mod_content(+category)`, `mod_product(+category)`, `mod_service(+category)`, `mod_project(+category)`, `mod_client`, `mod_reference`, `mod_appointment`, `mod_reservation`, `mod_hr`, `mod_organization_type`, `mod_test`, `mod_assessment`, `mod_hero_slider`.
- Foreign-module ingestion is guarded in `AF/Bulletin` by `tools/module_boundary_guard.py`.
