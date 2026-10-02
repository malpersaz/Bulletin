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
- **Interactive Month Name Annotation (MANDATORY)**:
  - Whenever a Kurmanji month name appears in the drafted prose or date references (`Rêbendan`, `Reşemî`, `Adar`, `Avrêl`, `Gulan`, `Pûşper`, `Tîrmeh`, `Gelawêj`, `Rezber`, `Kewçêr`, `Sermawez`, `Berfanbar`), wrap it in a clickable semantic element with all multilingual data attributes so that clicking it triggers the contextual popover displaying the month name in English, Turkish, and Arabic:
  - **Required HTML Structure**:
    `<span class="month-popover" role="button" tabindex="0" data-month-ku="$KU" data-month-en="$EN" data-month-tr="$TR" data-month-ar="$AR">$KU</span>`
  - **Canonical 12-Month Mapping Table**:
    | Kurdî (`data-month-ku`) | English (`data-month-en`) | Türkçe (`data-month-tr`) | العربية (`data-month-ar`) |
    | :--- | :--- | :--- | :--- |
    | `Rêbendan` | `January` | `Ocak` | `كانون الثاني` |
    | `Reşemî` | `February` | `Şubat` | `شباط` |
    | `Adar` | `March` | `Mart` | `آذار` |
    | `Avrêl` | `April` | `Nisan` | `نيسان` |
    | `Gulan` | `May` | `Mayıs` | `أيار` |
    | `Pûşper` | `June` | `Haziran` | `حزيران` |
    | `Tîrmeh` | `July` | `Temmuz` | `تموز` |
    | `Gelawêj` | `August` | `Ağustos` | `آب` |
    | `Rezber` | `September` | `Eylül` | `أيلول` |
    | `Kewçêr` | `October` | `Ekim` | `تشرين الأول` |
    | `Sermawez` | `November` | `Kasım` | `تشرين الثاني` |
    | `Berfanbar` | `December` | `Aralık` | `كانون الأول` |
  - Grammatical Suffixes: Place Kurdish case/construct suffixes outside the element (e.g. `<span class="month-popover" ...>Kewçêr</span>ê` or `2'yê <span class="month-popover" ...>Kewçêr</span>a 2026'an`).
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

### 6. Linguistic, Orthographic & SEO Gate (`kevheng`)
- Determine the intended Wikipedia-style SEO slug `$SEO_SLUG` or leave empty to auto-generate via MCP.
- Execute `kevheng` validator script against the fact-checked article, deck summary, and SEO slug:
  ```bash
  .agents/skills/kevheng/scripts/validate_kurmanji.py --text "$DRAFT_HTML" --summary "$SUMMARY" --seo "$SEO_SLUG"
  ```
- Enforce strict Latin alphabet, diacritics, conjunction `û`, apostrophes on numbers/acronyms (`14'emîn`, `DYE'yê`), direct suffixation on proper nouns (`SpaceXê`, `Amerîkayê`), mandatory vocabulary, exact 90-120 character deck constraint, complete interactive month annotations, and Wikipedia-style SEO slug compliance.
- Address any flagged typos, missing month attributes, or invalid URL slugs before proceeding.

### 7. Provenance Tracking, Categorization & Draft Staging
- Map article to the appropriate category in `mod_news` matching the language (`ku`).
- Attribute to author **Derya Welat** (`user_id: 535` / `username: "deryawelat"`).
- **Amed Framework Wikipedia-Style SEO Slug (AGENTS.md Rule 10)**:
  - Strictly adhere to Amed Framework's Wikipedia-style URL generation parity (`Helper::legacy_seo`).
  - **Delimiters**: Use underscores (`_`), NEVER hyphens/dashes (`-`). Hyphenated slugs are strictly prohibited.
  - **Diacritics**: Preserve authentic Kurdish Unicode characters (`ç, ê, î, ş, û`). Never strip, flatten, or ASCII-fold characters (`ş` must NEVER become `s`, `î` must NEVER become `i`, `ê` must NEVER become `e`).
  - Example: For headline `Ji bo qaymeqamtiya Şingalê deriyê xweberbijêrkirinê tê vekirin`, the canonical slug is `ji_bo_qaymeqamtiya_şingalê_deriyê_xweberbijêrkirinê_tê_vekirin`.
  - Alternatively, omit the `seo` argument when calling `create_news` so that MCP automatically computes it via `makeSeo($name, $lang)`.
- Call `create_news` on the `amidakurd` MCP server with:
  - `user_id: 535`
  - `status: "n"` (**STRICT REQUIREMENT: Save as draft, DO NOT publish**)
  - `deck: "y"` (**STRICT REQUIREMENT: Show summary deck**)
  - Valid `summary` (90–120 characters)
  - Full HTML `content` with in-text citations, complete interactive month annotations, and image credits
  - Wikipedia-style `seo` slug (with underscores and Kurdish diacritics), meta description, and 3–5 relevant keywords.
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
