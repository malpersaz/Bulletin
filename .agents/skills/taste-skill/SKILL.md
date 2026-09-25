---
name: taste-skill
description: Anti-slop frontend design skill for public themes, landing pages, and redesigns. Infers design direction, enforces spatial and typographic discipline, and prevents AI slop.
---

# Taste Skill (Anti-Slop Frontend)

## Operating Principle

Keep this skill focused on operational UI/UX execution. Prevent default LLM aesthetics (generic purple gradients, uniform three-column card grids, excessive glassmorphism, and bland typography).

## Sequence

### 1. Brief Inference (Read the Context)
Before generating frontend markup or styles, declare a one-line Design Read:
`"Reading this as: <page kind> for <audience>, with a <vibe> language, leaning toward <aesthetic family>."`
- **Signals to check**: Audience type, domain context, brand identity, existing typography/palette, and quiet accessibility constraints.

### 2. Configure the Three Dials
Calibrate three core dials (scale 1 to 10) to guide execution:
- **`DESIGN_VARIANCE`** (1 = Strict Symmetry, 10 = Asymmetric / Expressive)
  - Default: `7-8` for landing/editorial; `5-6` for minimalist/clean.
- **`MOTION_INTENSITY`** (1 = Static, 10 = Fluid / Micro-Interactions)
  - Default: `5-6` for subtle micro-interactions; `2-3` for high-density tools.
- **`VISUAL_DENSITY`** (1 = Airy / Gallery, 10 = Compact / Data-dense)
  - Default: `3-4` for public marketing/editorial; `6-8` for administrative listings.

### 3. Apply Anti-Default Discipline
- **Typography**: Establish clear hierarchy with expressive heading choices and distinct scale rather than unstyled system fallbacks.
- **Spacing & Rhythm**: Use intentional vertical rhythm (`gap-`, `margin`, `padding` based on an explicit 4px/8px scale) rather than uniform padding everywhere.
- **Color & Contrast**: Avoid washed-out pure gray borders or un-tinted shadows. Use deliberate background tints and accessible text contrast.
- **Layout Structure**: Avoid standard centered hero blocks with 3 identical feature cards unless specifically indicated. Use asymmetrical offsets, broken grids, or editorial flows where appropriate.

### 4. Repository Architectural Constraints
- **Strict Code Separation**: Never insert inline styles (`style="..."` or `<style>`) or inline JavaScript in Twig/HTML templates. All styles must reside in dedicated CSS files (e.g. `templates/amida/assets/css/` or theme asset folders).
- **Theme Parity**: When designing public theme views, respect the repository theme contract documented in `/.agents/design/public-theme/DESIGN.md`.
