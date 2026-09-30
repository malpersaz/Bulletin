---
name: kevanger
description: Curate, draft, structure, and stage news articles for Rojname's mod_news via MCP. Coordinates with kevkolin (research), kevrast (fact-checking), and kevheng (linguistics) in the editorial pipeline.
---

# Kevanger News Drafting Skill

## Operating Rule

Keep this skill as a short ordered execution checklist. Follow the 4-tier pipeline strictly:
`kevkolin` (Research) -> `kevrast` (Stage 1) -> `kevanger` (Drafting) -> `kevrast` (Stage 2) -> `kevheng` (Linguistics) -> Staging.

## Sequence

### 1. Pre-Flight Verification of MCP Connectivity
- Verify that `amidakurd.net/mcp.php` bridge is functional and authenticated.
- Ensure the MCP API key is active (via `amidakurd` MCP server).
- Perform a lightweight liveness check (e.g. `list_news_categories` or `get_site_settings`).
- If connection fails or credentials are missing, report immediately and halt before drafting.

### 2. Research Dossier Intake (`kevkolin` & `kevrast` Stage 1)
- Receive the verified Research Dossier produced by `kevkolin` and pre-verified by `kevrast` (Stage 1).
- Ensure all source evidence, dates, numbers, and live URLs are grounded before beginning prose writing.
- If contradictory reports were identified by `kevkolin`, formulate the prose to explicitly reflect the variance:
  - Format: *"Li gor çavkaniya $A weşandiye ..., lê li gor çavkaniya $B ... "*

### 3. Journalistic Drafting & Storytelling
- Write fluent, engaging Kurmanji prose following standard newsroom practices (lead paragraph, inverted pyramid, concise paragraphs).
- Craft a compelling headline and deck summary (aiming for 90 to 120 characters).
- Embed in-text citations linking to verified live URLs:
  - Format: `<a href="$URL">çavkaniya $Name</a>` (e.g., `<a href="https://rudaw.net/kurmanci">çavkaniya Rûdaw</a>`).
- **Interactive Month Name Annotation**:
  - Whenever a Kurmanji month name appears in the drafted prose or date references (`Rêbendan`, `Reşemî`, `Adar`, `Avrêl`, `Gulan`, `Pûşper`, `Tîrmeh`, `Gelawêj`, `Rezber`, `Kewçêr`, `Sermawez`, `Berfanbar`), wrap it in a clickable semantic element with multilingual data attributes so that clicking it triggers an aesthetic, compact contextual popover/box displaying the month name in English, Turkish, and Arabic without mentioning "Gregorian calendar":
  - Format: `<span class="month-popover" role="button" tabindex="0" data-month-ku="Rezber" data-month-en="September" data-month-tr="Eylül" data-month-ar="أيلول">Rezber</span>`
  - Strict Code Separation: Do not insert inline `style` or inline JavaScript handlers; styling and behavior are provided via repository frontend theme assets.

### 4. Ethical Image Creation & WebP Optimization
- If an existing image from verified sources is attached:
  - Add attribution at the bottom: `<p><strong>Wêne: $Source</strong></p>`.
- If an image is AI-generated (`generate_image`):
  - Follow clean editorial photojournalism style (no artificial text or banners).
  - Optimize as WebP (under 100 KB) and attach via MCP.
  - Add attribution at the bottom: `<p><strong>Wêne: Amîda Kurd / Sembolîk</strong></p>`.

### 5. Fact-Checking & Anti-Hallucination Gate (`kevrast` Stage 2)
- Execute `kevrast` auditor script against the drafted article:
  ```bash
  .agents/skills/kevrast/scripts/verify_facts_and_links.py --text "$DRAFT_HTML"
  ```
- **Rule**: Do not proceed to `kevheng` until `kevrast` passes 100% (`Status: PASSED`).
- If any link fails (not HTTP 200) or claim drifts, correct the draft text immediately.

### 6. Linguistic & Orthographic Gate (`kevheng`)
- Execute `kevheng` validator script against the fact-checked article and deck summary:
  ```bash
  .agents/skills/kevheng/scripts/validate_kurmanji.py --text "$DRAFT_HTML" --summary "$SUMMARY"
  ```
- Enforce strict Latin alphabet, diacritics, conjunction `û`, apostrophes on numbers/acronyms (`14'emîn`, `DYE'yê`), direct suffixation on proper nouns (`SpaceXê`, `Amerîkayê`), mandatory vocabulary, and exact 90-120 character deck constraint.
- Address any flagged typos or neologisms.

### 7. Provenance Tracking, Categorization & Draft Staging
- Map article to the appropriate category in `mod_news` matching the language (`ku`).
- Attribute to author **Derya Welat** (`user_id: 535` / `username: "deryawelat"`).
- Call `create_news` on the `amidakurd` MCP server with:
  - `user_id: 535`
  - `status: "n"` (**STRICT REQUIREMENT: Save as draft, DO NOT publish**)
  - `deck: "y"` (**STRICT REQUIREMENT: Show summary deck**)
  - Valid `summary` (90–120 characters)
  - Full HTML `content` with in-text citations and image credits
  - SEO slug, meta description, and 3–5 relevant keywords.
- Attach the WebP image via `update_news`.

### 8. Editorial Reporting & User Handoff
Report completion to the user in Kurmanji, providing:
- Generated News ID
- Author name (*Derya Welat @deryawelat*)
- Category name
- Headline and summary
- List of verified sources and detected contradictions (if any)
- Confirmation that the article is staged as a draft awaiting human editorial approval.

### 9. Non-Destructive Editorial Updates (Strict Anti-Overwrite Gate)
When instructed to update an existing news article (`update_news`):
1. **Mandatory Read-Before-Write**: ALWAYS call `get_news` before updating.
2. **Preserve User Revisions**: User manual corrections are canonical; never revert them.
3. **Surgical Patching**: Modify only targeted fields/substrings.
4. **Draft Status Invariance**: Never flip `status: "n"` to `"y"` unless explicitly commanded.
