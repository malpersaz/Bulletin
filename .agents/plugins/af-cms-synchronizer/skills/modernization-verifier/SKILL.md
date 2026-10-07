---
name: modernization-verifier
description: Audit and verify compliance with Amed Framework architectural standards: BaseModuleController, getRow(), mobile-first admin surface, code separation, and i18n keys.
---

# Modernization Verifier Skill

Use this skill to systematically audit and verify that code in `AF/Rojname` complies with all modern Amed Framework standards.

## 1. Architectural Checklist

Every modern module in Amed Framework must satisfy:
1. **Controller Inheritance**: Module controllers must extend `BaseModuleController`.
2. **Database Method Modernization**: Must use modernized DB methods (e.g. `getRow()` instead of deprecated query patterns).
3. **Admin Mobile-First Surface**: All templates under `templates/admin/` must strictly target phone-sized viewports.
4. **Strict Code Separation**: Zero inline `style="..."`, zero `<style>` blocks, zero `onclick="..."` handlers in `.twig` and `.php` files.
5. **i18n Consistency**: Keys in `en.php` must be descriptive English words.

## 2. Automated Tooling Commands

Run the suite of deterministic verification tools:

```bash
# 1. Audit modernization status of controllers and services
python3 tools/audit_modernization.py

# 2. Check strict code separation (inline styles / JS handlers)
python3 tools/code_separation_guard.py
```

## 3. Resolving Violations

- **Code Separation**: Move styles to `static/css/<module>.css` and event handlers to `static/js/<module>.js` using `addEventListener`.
- **Admin Mobile Viewport**: Ensure layouts use fluid single-column structures and touch-friendly interactive targets (44px min).
