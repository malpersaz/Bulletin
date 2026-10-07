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
  - Format: *"Li gor $A weşandiye ..., lê li gor $B ... "*

### 3. Journalistic Drafting & Storytelling
- Write fluent, engaging Kurmanji prose following standard newsroom practices (lead paragraph, inverted pyramid, concise paragraphs).
- Craft a compelling headline and deck summary (aiming for 90 to 120 characters).
  - Use lightweight punctuation: prefer a comma (`,`) over redundant conjunctions (`ku`) after reporting verbs (e.g. `ragihand, ew...` instead of `ragihand ku ew...`).
- Embed in-text citations linking to verified live URLs:
  - Format: `Li gor <a href="$URL">$Name</a> weşandiye` (e.g., `Li gor <a href="https://rudaw.net/kurmanci">Rûdawê</a> weşandiye`). Do not use verbose formulas like `"li gor çavkaniya $Name di $zeman de weşandiye"`; keep it clean as `"li gor $Name weşandiye"`.
- **Date Grounding Constraint**:
  - Do NOT inject today's date or current date into the news content if it was not explicitly stated in the source text. Only include dates or days of the week that appear in verified source reporting.
- **Kurmancî Syntax & Kinetic Strike Distinction (Desktop/projects/ocr/Kurmanci_Syntax.md)**:
  - For physical, kinetic strikes, missile hits, or bombings, strictly use **`li ... xistin`** (e.g. `Ûrisyayê li Kyîvê li pira duyemîn a sereke xist`) or passive **`hat lêdan`** (e.g. `Pira duyemîn a sereke hat lêdan`).
  - Reserve **`kirin armanc`** strictly for abstract, rhetorical, debate, or political contexts (e.g. `Rêzan di konferansê de Derya kir armanc`). Do not use `kirin armanc` when a physical strike/impact has occurred.
- **Capitalization of Day Names**:
  - Always capitalize names of the week in all contexts: **`Yekşem, Duşem, Sêşem, Çarşem, Pêncşem, În, Şemî`** (e.g. `serê sibeha Şemiyê`, `rojên Pêncşem û Înê`, `roja Duşemê`).
- **Vowel Reduction Before "y" (`î` and `ê` -> `i`)**:
  - Before the consonant **`y`**, long vowels **`î`** and **`ê`** are shortened to **`i`** (sequences `îy` and `êy` are strictly prohibited):
    - `î` + `y` -> `iy` (e.g. `Şemî` + `yê` -> `Şemiyê`, never `Şemîyê`; `welatî` + `yek` -> `welatiyek`, never `welatîyek`).
    - `ê` + `y` -> `iy` (e.g. `rê` + `ya` -> `riya`, never `rêya`; `pê` + `yê` -> `piyê`, never `pêyê`; `sê` + `yan` -> `siyan`, never `sêyan`).
- **Quotation Marks**: Strictly use standard double quotes **`" ... "`** for direct speech and quotes. NEVER use French guillemets (`« ... »`).
- **Preposition Constraint**: Strictly use **`ji bo`**; NEVER use bare **`bo`** alone!
- **Cargo & Freight Terminology**: Strictly use **`bar`** (e.g. `keştiya bar`, `firokeya bar`); NEVER use `barhilgir`!
- **Interactive Month Name Annotation (MANDATORY & SIMPLIFIED)**:
  - Whenever a Kurmanji month name appears in the drafted prose or date references (`Rêbendan`, `Reşemî`, `Adar`, `Avrêl`, `Gulan`, `Pûşper`, `Tîrmeh`, `Gelawêj`, `Rezber`, `Kewçêr`, `Sermawez`, `Berfanbar`), wrap it in a clean semantic element:
  - **Required HTML Structure**:
    `<span class="month-popover">$KU</span>`
  - Do NOT repeat verbose data attributes (`data-month-ku`, `data-month-en`, etc.); the frontend engine (`month-popover.js`) automatically resolves multilingual translations from its built-in canonical catalog.
  - **Grammatical Suffixes**: Always place Kurdish case/construct suffixes outside the element (e.g. `<span class="month-popover">Kewçêr</span>ê` or `2'yê <span class="month-popover">Kewçêr</span>a 2026'an`).
  - **Strict Code Separation**: Do not insert inline `style` or inline JavaScript handlers; styling and behavior are provided via repository frontend theme assets.
- **Country, Region, and Acronym Parity**:
  - Always consult and strictly enforce the Kurdish toponymy and country reference (`Desktop/projects/ocr/Nave_Dewletan_u_Cihan.md`):
    - `Turkî` (never `Turkiye` / `Tirkiye`)
    - `Yewnan` (never `Yunanistan` / `Yunan` / `Yûnan`)
    - `Ûrisya` (never `Rusya` / `Rûsya`)
    - `Mosqow` (never `Moskow` / `Moskova` / `Mosco`)
    - `Kyîv` (never `Kiev` / `Kyiv` / `Kîev`)
    - `Sûrî` (never `Suriye` / `Sûriye`)
    - `Îraq` (never `Irak` / `Iraq`)
    - `Îran`
    - `London` (never `Londra`)
    - `Frensa` (never `Fransa`)
    - `Hewlêr` (never `Erbîl` / `Erbil`)
    - `Amed` (never `Diyarbekir` / `Diyarbakır`)
    - `Stenbol` (never `İstanbul`)
    - `Hisiça` (never `Hesekê` / `Heseke`)
  - Strictly use **`HSD`** for Syrian Democratic Forces in Kurmanji (never Arabic `QSD`).

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
- **Multilingual Translation & Author Invariance**: When translating, syndicating, or porting news articles to other active languages (`en`, `sv`, `de`, `fr`, `zza`, `ckb`), ALWAYS preserve and propagate the exact author `user_id` (or `username`) of the source article. Never allow `user_id` to default to the acting API key / admin (`user_id: 1`).
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
