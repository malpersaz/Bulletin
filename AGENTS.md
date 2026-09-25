# AGENTS (Repository Baseline & Priority Hierarchy)

Repository-local agent documentation index lives under:
- `/.agents/INDEX.md`

Primary quality-agent policy:
- `/docs/CODE_QUALITY_AGENT_STRATEGY.md`

---

## Primary Rules Hierarchy (Strict Priority Order: 1 to 10)

### 1. User Question vs Execution Rule (Primary Interaction Rule)
When the user asks a question, treat it strictly as a question, NOT as a code modification or execution command. Answer the question directly without making any code edits or starting unauthorized refactoring. Do not execute research tools prior to answering unless the user explicitly instructs to research/investigate first.

### 2. Strict Code Separation Mandate
Do not write inline CSS (`style="..."` attributes or `<style>` blocks) or inline JavaScript (`<script>` logic or inline event handlers) within HTML, Twig templates, or PHP files. Never nest PHP, CSS, JS, and Twig code!

### 3. Language & Documentation Mandate (Agent, Skill & /docs/)
All Agent rules, Skill instructions (`SKILL.md`), and Markdown files inside `/docs/` MUST strictly be written in English at all times.

### 4. Workspace Isolation & Zero Autonomous Cross-Repo Sync
Do not make any autonomous decisions. Treat each repository as a strictly isolated workspace. Agents, skills, and tools must NEVER touch, mirror, sync, or inspect sibling repositories autonomously unless explicitly instructed by the user. If you spend tokens purchased with the user's own money through autonomous decisions, you will also spend tokens purchased with the user's own money when reversing these transactions, which means causing the user double the damage. Also, if you ask for confirmation in a language the user doesn't understand, you may cause the same results. This makes you responsible for the consequences.

### 5. Strict Versioning & Production Parity Policy
Write the `docs/VERSION.md` file in English.

### 6. Admin Mobile-First Surface Rule
The entire admin panel (`templates/admin/`) MUST be treated strictly as a phone-only product surface. Develop and test admin UI exclusively for phone-sized viewports. Tablet and desktop layouts are out of scope unless explicitly requested by the user.

### 7. Module Internal Quality & i18n Rule
Use only English in the code. If you encounter code other than English in the old code, rewrite it in English.

### 8. Conversation & Task Title Language Protocol
Engage in dialogue with the user in the Kurmanji dialect of Kurdish and the Latin alphabet, and present your plans accordingly. Apply this rule when requesting permission from the user.

### 9. Skill Design Rule
Repository skills must be short, ordered operation documents (`SKILL.md`), not knowledge bases. Keep durable knowledge in normal docs and keep skills focused on execution.

### 10. URL Generation Parity (Wikipedia Style)
Amed Framework generates URLs according to their original alphabet in all languages (e.g., using `legacy_seo`). Like Wikipedia URLs, original casing and characters are preserved, and spaces are replaced with underscores. Do not force lowercase hyphenated slugs if the module implements this style.
