---
name: frontend-theme
description: Create, audit, or register repository-safe public frontend themes under templates theme folders for this Amed Framework/CMS repository. Use when Codex is asked to create a new public theme, scaffold from templates/default, update theme activation through the tema cookie, audit frontend theme parity, wire localized theme UI, or validate a theme against repository agent policy.
---

# Frontend Theme Skill

## Operating Rule

Keep this skill as a short ordered operation. Do not treat it as a knowledge base. Load large repository docs only by relevant section when the next operation needs them.

## Sequence

1. Read `.agents/INDEX.md`.
2. Read `templates/AGENTS.md`; if touching admin templates, read `templates/admin/AGENTS.md`.
3. Inspect only the needed sections of `docs/NEW_THEME_CREATION_GUIDE.md` when it exists; do not load the full guide by default.
4. Read `CODE_QUALITY_AGENT_STRATEGY.md` only when the work changes templates, language keys, JavaScript, or docs.
5. If creating or substantially redesigning a visual theme, read `.agents/design/INDEX.md`, then load only the public design contract, one matching profile, and the theme-local `templates/<theme>/DESIGN.md` when it exists.

6. Inventory the real theme state:

   ```bash
   find templates -maxdepth 2 -type d | sort
   find templates/default -maxdepth 3 -type f | sort
   rg -n "app_template|\\$_COOKIE\\['tema'\\]|template_dir|site\\.app_template|site\\.tadres" config lib templates/default
   ```

7. Confirm the requested theme name is lowercase ASCII and will live at `templates/<theme>`.
8. If creating a durable visual theme, draft `templates/<theme>/DESIGN.md` before visual implementation.
9. If creating a production theme, scaffold from `templates/default` unless the user explicitly asks for a different strategy.
10. Clean copied references before visual work:

   ```bash
   rg -n "templates/default|default/static|default/|artist|new|yeni_tema|tema.php" templates/<theme>
   ```

11. Preserve layout metadata, menu pipeline, homepage composition, module templates (like `mod_news`, `mod_publication`), auth flows, cookie consent, popup, gallery/share behavior, context script injection, and active-theme asset paths.
12. Use `{{ site.tadres }}{{ site.app_template }}/...` for theme-owned assets unless there is a documented reason not to.
13. Keep visible UI copy in language files. Do not use `default(...)`, inline fallback copy, or JavaScript literals for visible labels.
14. Use `lang-refactor` only when language-key migration is required.
15. Use `imagegen` (or other image assets) only when generated raster assets are explicitly needed.
16. Validate with route checks, quality-agent checks, design-doc checks when applicable, and `git diff --check`.

## Agent Usage

When the user explicitly requests agents or delegation, use `.agents/theme/prompts/frontend-theme-agent.md` as the primary specialized agent prompt.

Useful split:

- Explorer: inventory current theme files, loader behavior, includes, asset paths, context keys, and activation points.
- Worker: create or modify `templates/<theme>` and related registration files.
- Quality pass: apply `.agents/quality/manifest.md` and consolidate through `policy-gate`.

Do not spawn agents unless the user has explicitly asked for agent use.

## Version Log

When the theme task changes docs or implementation, update `docs/VERSION.md`. Before appending, re-run the repository version checks from the start and place unpublished work under the next unpublished version section, not under the shipped Git version.

## Final Checks

Run at least:

```bash
git diff --check
rg -n "\\|default\\(|TODO|FIXME|lorem|placeholder" templates/<theme>
```

Use the visible-string search as a review aid, then manually classify matches instead of treating the search as a complete policy decision.

When design docs were created or changed, also run:

```bash
find .agents/design -maxdepth 3 -type f | sort
rg -n "default\\(|lorem|placeholder|TODO|FIXME" .agents/design
```
