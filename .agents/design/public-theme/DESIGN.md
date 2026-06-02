# Public Theme Design Contract

## Purpose

Define baseline design expectations for public frontend themes under `templates/<theme>`.

This file gives visual direction only. The runtime contract remains in guidelines or theme code structure.

## Required Context

Before using this file:

1. Read `templates/AGENTS.md`.
2. Use `.agents/skills/frontend-theme/SKILL.md`.
3. Select one public theme profile from `.agents/design/INDEX.md` when the theme has a clear profile.

## Design Priorities

- Preserve CMS-managed content and module coverage.
- Make the first viewport identify the site purpose, active content type, and primary navigation path.
- Keep public themes usable on both mobile and desktop public viewports.
- Use visual assets that reveal the actual news, article, work, or organization when available.
- Keep typography, spacing, and component rhythm consistent across list, detail, and home surfaces.
- Use theme-owned CSS, JavaScript, and images under `templates/<theme>/static/`.

## Navigation

- Preserve the framework menu pipeline and active-language menu behavior.
- Keep header and footer navigation active-theme-relative.
- Preserve search, language switching, account actions, and theme controls when present in the baseline.
- Mobile navigation must support nested menu behavior without hiding child links.

## Homepage

- Build the homepage from CMS-managed modules and helper outputs.
- Do not introduce unmanaged marketing copy directly into Twig or JavaScript.
- If a block is removed or reordered, document the design reason in `templates/<theme>/DESIGN.md` or the task summary.
- Keep hero, latest content, news, reference, client, publication, video, and image-category surfaces covered when the baseline exposes them.
- Empty latest/module blocks should stay hidden or structurally quiet; do not add theme-flavored filler copy to make empty CMS data look complete.

## Component Rhythm

- Lists should have stable card or row structure, predictable image ratios, and clear scan order.
- Detail pages should prioritize title, media, metadata, main content, share controls, and related navigation in a consistent order.
- Repeated module cards should share interaction states and spacing, but may vary layout to match the selected profile.
- Avoid nested card layouts and decorative panels that obscure CMS content.

## Typography And Spacing

- Use readable line lengths for content-heavy text.
- Keep headings proportional to their container, not oversized inside compact modules.
- Avoid viewport-width font scaling.
- Keep letter spacing neutral.
- Ensure text does not overlap controls, images, or neighboring content.

## Visual Assets

- Prefer real CMS images or theme-owned assets with clear provenance.
- Generated raster assets are allowed only when the task calls for them.
- Do not use copied third-party templates, fonts, icons, or images without license review.
- Do not place theme identity only in tiny navigation text; the first viewport must signal the theme direction.

## Forbidden Patterns

- No visible hardcoded UI copy.
- No visible localization fallback copy.
- No hardcoded dependency on `templates/default` unless explicitly documented as intentional.
- No references to missing theme folders or sibling-repository assumptions.
- No public theme guidance may weaken admin phone-only rules.
- No style-only reskin that drops runtime behavior or module coverage.
