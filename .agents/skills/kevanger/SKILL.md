---
name: kevanger
description: Curate, draft, structure, and stage Turkish-language news articles for Bulletin's mod_news via MCP. Coordinates with kevkolin (research), kevrast (fact-checking), and kevseo (SEO & Semantic HTML) in the editorial pipeline.
---

# Kevanger News Drafting Skill (Turkish Editorial Pipeline)

## Operating Rule

- **STRICT NO-PUBLISH MANDATE**: AI is strictly prohibited from publishing news articles (`status: "y"`). Every new or updated article MUST be staged exclusively as a draft (`status: "n"`) for human editorial review and approval.
- **NO MCP BRIDGE BYPASS**: Always invoke MCP tools through `call_mcp_tool` (`amed-bulletin`). NEVER extract API keys from local bridge scripts or bypass the local MCP bridge with direct `curl`/`urllib` requests to `mcp.php`.
- Keep this skill as a short ordered execution checklist. Follow the 4-tier pipeline strictly:
  `kevkolin` (Research) -> `kevrast` (Stage 1) -> `kevanger` (Drafting in Turkish) -> `kevseo` (SEO & Semantic HTML) -> `kevrast` (Stage 2) -> Staging.

In this repository (`amed-bulletin`), `kevanger` drafts news articles exclusively in **Turkish (`tr`)**.

## Sequence

### 1. Pre-Flight Verification of MCP Connectivity & Active Language Gate
- Verify that the local `amed-bulletin` (`amed-bulletin-mcp` via `http://localhost:8080/mcp.php`) MCP bridge is functional and authenticated. Never call sibling servers (e.g. `amed-rojname` or `amidakurd`).
- Call `list_site_settings(active_only='y')` to inspect `active_languages`, `default_language`, and `lang_status === 'y'`. Never infer active site languages from `site_status` or unverified category rows (`lang_status === 'n'`).
- Strictly separate **Conversational Language** (Kurmanji Kurdish with the user per `AGENTS.md` Rule 8) from **Target CMS Record Language (`record.lang`)**: every database field (`name`, `summary`, `content`, `title`, `description`, `keywords`, `seo`, and `mod_faq` `question`/`answer`) MUST be written 100% in the active target `record.lang`.
- Perform a lightweight liveness check (e.g. `list_news_categories` with `lang: "tr"`).
- If connection fails, credentials are missing, or the target language is inactive (`lang_status === 'n'`), report immediately in Kurmanji Kurdish and halt before drafting.

### 2. Research Dossier Intake (`kevkolin` & `kevrast` Stage 1)
- Receive the verified Research Dossier produced by `kevkolin` and pre-verified by `kevrast` (Stage 1).
- For **Diyarbakır (Amed)** local news items, ensure multi-source cross-referencing against the dedicated `amed_diyarbakir` registry in `.agents/skills/kevkolin/scripts/source_catalog.py` (Amida Haber, Tigris Haber, Güneydoğu Ekspres, Diyarbakır Yenigün, Mücadele Gazetesi, CEGA Medya, Amed Times, Diyarbakır Söz, Özgür Haber Gazetesi, Güneydoğu Güncel, Amed Haber, and official institutions such as Diyarbakır Büyükşehir Belediyesi, DTSO, DTB, Diyarbakır Barosu, Karacadağ Kalkınma Ajansı, Dicle Üniversitesi, and Amedspor).
- Ensure all source evidence, dates, numbers, and live URLs are grounded before beginning prose writing.
- If contradictory reports were identified by `kevkolin`, formulate the Turkish prose to explicitly reflect the variance:
  - Format: *"$A tarafından paylaşılan bilgilere göre ..., ancak $B kaynağında ..."*

### 3. Journalistic Drafting & Storytelling (Turkish - `tr`)
- Write fluent, engaging, and objective Turkish prose following professional newsroom standards (5N1K lead paragraph, inverted pyramid, concise paragraphs).
- Craft a compelling Turkish headline (`name`) and deck summary (`summary` strictly **90 to 120 characters**).
- Embed in-text citations linking to verified live URLs:
  - Format: `<a href="$URL">$Name</a> tarafından aktarılan bilgilere göre` or `<a href="$URL">$Name</a> haberine göre`.
- **Date Grounding Constraint**:
  - Do NOT inject today's date or current date into the news content if it was not explicitly stated in the source text. Only include dates or days of the week that appear in verified source reporting.
- **Turkish Orthography & Punctuation Standards**:
  - Strictly adhere to standard Turkish grammar and TDK punctuation conventions.
  - Use standard double quotes **`" ... "`** for direct speech and quotes. NEVER use French guillemets (`« ... »`).
  - Preserve authentic Turkish characters (`ç, ğ, ı, İ, ö, ş, ü`) across headlines, summaries, body text, and Wikipedia-style URL slugs.
- **Strict Code Separation**:
  - Do not insert inline `style` attributes, `<style>` blocks, or inline JavaScript handlers.

### 4. Ethical Image Intake, AI Generation & WebP Optimization
Whenever `kevanger` brings or drafts a news article from any source:
- **Case 1: Source Image Present**:
  - Extract and verify the original photograph from the source.
  - Follow professional photojournalistic standards (minimum 1200px width, 16:9 aspect ratio, authentic composition, strictly no artificial distortion or sensational filters).
  - Optimize and compress to WebP format under 100 KB.
  - Attach the image object to `create_news` or `update_news` via MCP (`image: {"data_base64": "...", "mime": "image/webp"}`).
  - Add transparent source attribution at the bottom of the news body:
    - Kurdish format: `<p><strong>Wêne: $Çavkanî</strong></p>` (e.g. `<p><strong>Wêne: Amida Haber</strong></p>`).
    - Turkish format: `<p><strong>Fotoğraf: $Kaynak</strong></p>` (e.g. `<p><strong>Fotoğraf: Amida Haber</strong></p>`).
- **Case 2: Source Image Absent / Unavailable**:
  - If no photograph exists at the source or the source has no accessible media:
  - Invoke the `image-generator` subagent to generate a representative photojournalistic image depicting the news subject accurately.
  - Follow clean editorial photojournalism guidelines: realistic scene, 16:9 framing, strictly zero artificial text, zero watermarks, and zero overlay banners.
  - Optimize as WebP format under 100 KB (minimum 1200px width).
  - Attach the image object to `create_news` or `update_news` via MCP (`image: {"data_base64": "...", "mime": "image/webp"}`).
  - Add explicit representative attribution at the bottom of the news body:
    - Kurdish format: `<p><strong>Wêne: Temsîlî</strong></p>`.
    - Turkish format: `<p><strong>Fotoğraf: Temsili</strong></p>`.
- **Automated Processing Helper**:
  - Use `.agents/skills/kevanger/scripts/process_news_image.py` to prepare images deterministically:
    ```bash
    # For source image:
    python3 .agents/skills/kevanger/scripts/process_news_image.py --input /path/to/img.jpg --source "$Source" --out /tmp/news_img.webp > /tmp/img_payload.json
    # For representative image:
    python3 .agents/skills/kevanger/scripts/process_news_image.py --input /path/to/generated.png --representative --out /tmp/news_img.webp > /tmp/img_payload.json
    ```
  - **Programmatic & File-Based Transfer**:
    - In environments with programmatic tool invocation (e.g., OMP `tool.<name>(args)`), parse `/tmp/img_payload.json` programmatically in code and pass `image: {"data_base64": payload["data_base64"], "mime": "image/webp"}` directly so the base64 string is never truncated by terminal output limits.
    - In local MCP bridge environments (or same-filesystem local servers), you may also pass `image: {"file_path": "/tmp/news_img.webp", "mime": "image/webp"}`. Never copy-paste `<truncated ...>` base64 strings from terminal logs.

### 5. SEO & Semantic HTML Gate (`kevseo` & `audit_content_seo.py`)
- Determine the intended Wikipedia-style SEO slug `$SEO_SLUG` or leave empty to auto-generate via MCP.
- Execute the `kevseo` auditor script against the drafted article, deck summary, and headline:
  ```bash
  python3 .agents/skills/kevseo/scripts/audit_seo.py --content "$DRAFT_HTML" --summary "$SUMMARY" --headline "$HEADLINE" --seo "$SEO_SLUG"
  ```
- After staging or updating via MCP, verify the persisted database record against the 100-point rubric (`tools/audit_content_seo.py --module mod_news --id <id>`) to confirm active language status, zero cross-language prose contamination, metadata synchronization, and strict code separation.
- Address any flagged HTML architecture issues, deck length violations (must be 90–120 chars; 90–160 chars accepted by MCP SERP parity), or invalid URL slugs before proceeding.

### 6. Fact-Checking & Anti-Hallucination Gate (`kevrast` Stage 2)
- Execute `kevrast` auditor script against the drafted article:
  ```bash
  python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --text "$DRAFT_HTML"
  ```
- **Rule**: Do not proceed to staging until `kevrast` passes 100% (`Status: PASSED`).
- If any link fails (not HTTP 200) or claim drifts, correct the draft text immediately.

### 7. Provenance Tracking, Categorization & Draft Staging
- Map the article to the appropriate Turkish (`tr`) category in `mod_news` (e.g., under `Haberler` / `id: 18` or the designated Diyarbakır category).
- **Amed Framework Wikipedia-Style SEO Slug (AGENTS.md Rule 10)**:
  - Strictly adhere to Amed Framework's Wikipedia-style URL generation parity (`Helper::legacy_seo`).
  - **Delimiters**: Use underscores (`_`), NEVER hyphens/dashes (`-`). Hyphenated slugs are strictly prohibited.
  - **Diacritics**: Preserve authentic Turkish Unicode characters (`ç, ğ, ı, İ, ö, ş, ü`). Never strip, flatten, or ASCII-fold characters (`ş` must NEVER become `s`, `ı` must NEVER become `i`, `ğ` must NEVER become `g`).
  - Alternatively, omit the `seo` argument when calling `create_news` so that MCP automatically computes it via `makeSeo($name, $lang)`.
- Call `create_news` on the `amed-bulletin` (`amed-bulletin-mcp`) MCP server with:
  - `lang: "tr"`
  - `username: "habermerkezi"` (**STRICT REQUIREMENT: Never publish or stage news or `mod_publication` records under `'admin'`; always attribute news to `'habermerkezi'`**)
  - `status: "n"` (**STRICT REQUIREMENT: Save as draft, DO NOT publish**)
  - `deck: "y"` (**STRICT REQUIREMENT: Show summary deck**)
  - Valid `summary` (90–120 characters)
  - Full HTML `content` with in-text citations and image credits (`<p><strong>Wêne: $Çavkanî</strong></p>` / `<p><strong>Wêne: Temsîlî</strong></p>` or `<p><strong>Fotoğraf: $Kaynak</strong></p>` / `<p><strong>Fotoğraf: Temsili</strong></p>`)
  - Wikipedia-style `seo` slug (with underscores and Turkish characters), meta description, and 3–5 relevant Turkish keywords.
  - Mandatory featured `image` object (`data_base64` and `mime: "image/webp"` / `"image/jpeg"`). News articles cannot be created without an image.

### 8. Editorial Reporting & User Handoff
Report completion to the user in Kurmanji (per user communication protocol), providing:
- Generated News ID
- Category name
- Turkish Headline and summary
- List of verified sources and detected contradictions (if any)
- Confirmation that the article is staged as a draft (`status: "n"`) awaiting human editorial approval.

### 9. Non-Destructive Editorial Updates (Strict Anti-Overwrite Gate)
When instructed to update an existing news article (`update_news`):
1. **Mandatory Read-Before-Write**: ALWAYS call `get_news` before updating.
2. **Preserve User Revisions**: User manual corrections are canonical; never revert them.
3. **Surgical Patching**: Modify only targeted fields/substrings.
4. **Draft Status Invariance**: Never flip `status: "n"` to `"y"` unless explicitly commanded.
