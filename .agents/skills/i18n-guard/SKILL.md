---
name: i18n-guard
description: Automated quality guard to audit localization completeness, code language standards (non-English identifiers/comments), forbidden aliases, code separation, and missing keys.
---

# i18n-guard Skill

## Operating Rule

Use `python3 tools/i18n_guard.py` to perform instant, automated code quality and localization checks across the entire Amed Framework repository or specific paths.

## Execution Commands

1. **Audit Git Working Tree (Modified/Staged Files)**:
   ```bash
   python3 tools/i18n_guard.py
   ```

2. **Audit Specific Path or Directory**:
   ```bash
   python3 tools/i18n_guard.py --path templates/admin/mod_content
   ```

3. **Audit Entire Repository**:
   ```bash
   python3 tools/i18n_guard.py --all
   ```

4. **Programmatic / Agent JSON Output**:
   ```bash
   python3 tools/i18n_guard.py --json
   ```

## Checks Performed Automatically

- **NON_ENGLISH_FUNCTION**: Flags function and method names that are not in English.
- **NON_ENGLISH_VARIABLE**: Flags variable names in code that contain non-English tokens.
- **NON_ENGLISH_KEY_NAME**: Flags language key names that contain non-English tokens.
- **NON_ENGLISH_CLASS**: Flags class, trait, or interface names that are not in English.
- **NON_ENGLISH_COMMENT**: Flags code comments containing non-English tokens.
- **FORBIDDEN_ALIAS**: Flags SQL and code alias usage (e.g. `AS alias_name`).
- **INLINE_STYLE_ATTR / INLINE_STYLE_TAG**: Flags inline CSS attributes or `<style>` blocks in PHP/Twig/HTML files.
- **INLINE_SCRIPT_TAG / INLINE_EVENT_HANDLER**: Flags inline JavaScript blocks or inline event handlers (`onclick=`, etc.) in PHP/Twig/HTML files.
- **FORBIDDEN_FALLBACK**: Flags forbidden inline default fallbacks (e.g. `lang.*|default(...)`).
- **MISSING_KEY**: Flags referenced translation keys that do not exist across language packs.

## Resolution Workflow

If `i18n-guard` reports violations:
1. Rename non-English functions, variables, classes, and keys to standard English.
2. Translate all code comments into English.
3. Remove SQL aliases and reference canonical column or entity names directly.
4. Move inline styles to external CSS files and inline scripts/handlers to external JS files.
5. Fix missing keys in the respective language packs.
6. Remove inline `|default(...)` fallbacks from template keys.
7. Re-run `python3 tools/i18n_guard.py` to confirm 100% clean pass.
