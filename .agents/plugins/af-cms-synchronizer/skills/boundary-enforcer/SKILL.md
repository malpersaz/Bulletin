---
name: boundary-enforcer
description: Enforce domain boundaries, prevent foreign commercial, assessment, and property modules from leaking into AF/Bulletin, and maintain domain isolation.
---

# Boundary Enforcer Skill

Use this skill to audit, protect, and enforce strict module boundaries in `AF/Bulletin`.

## 1. Domain Ownership Rules

- **Bulletin Domain Modules**:
  - `mod_news`, `mod_news_category`, `mod_headline`, `mod_author`
  - `mod_publication`, `mod_publication_category`
  - `mod_video`, `mod_video_embed`, `mod_video_embed_category`
  - `mod_page`, `mod_slider`, `mod_survey`, `mod_faq`, `mod_announcements`, `mod_gallery`
- **Forbidden Foreign Modules**:
  - `mod_content`, `mod_content_category`
  - `mod_product`, `mod_product_category`
  - `mod_service`, `mod_service_category`
  - `mod_client`, `mod_reference`
  - `mod_project`, `mod_project_category`
  - `mod_appointment`, `mod_reservation`, `mod_hr`, `mod_organization_type`
  - `mod_test`, `mod_assessment`
  - `mod_property`, `mod_property_category`
  - `mod_hero_slider`

## 2. Boundary Verification Procedure

Execute the automated boundary guard:

```bash
python3 tools/module_boundary_guard.py
```

- **Exit Code 0**: No boundary violations detected.
- **Exit Code 1**: Violations detected. Files listed must be removed or decoupled immediately.

## 3. Core Decoupling Invariants

1. **No Direct Domain Instantiation in `lib/`**:
   Shared-core classes under `lib/` must never contain hardcoded references to domain modules.
2. **Dynamic Discovery**:
   Modules must be registered and discovered via `lib/ModuleRegistry.class.php`.
3. **No Domain Schema in Shared Migrations**:
   Tables for forbidden commercial or assessment modules must never appear in `AF/Bulletin` installer schemas.
