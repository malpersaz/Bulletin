---
name: kevanger
description: Curate, draft, structure, and stage Turkish-language news articles for Bulletin's mod_news via MCP. Coordinates with kevtemen (same-day freshness gate), kevkolin (research), kevrast (fact-checking), and kevseo (SEO & Semantic HTML) in the editorial pipeline.
---

# Kevanger News Drafting Skill (Turkish Editorial Pipeline)

## Operating Rule

- **STRICT NO-PUBLISH MANDATE**: AI is strictly prohibited from publishing news articles (`status: "y"`). Every new or updated article MUST be staged exclusively as a draft (`status: "n"`) for human editorial review and approval.
- **NO MCP BRIDGE BYPASS**: Always invoke MCP tools through `call_mcp_tool` (`amed-bulletin`). NEVER extract API keys from local bridge scripts or bypass the local MCP bridge with direct `curl`/`urllib` requests to `mcp.php`.
- Keep this skill as a short ordered execution checklist. Follow the 5-tier pipeline strictly:
  `kevtemen` (Same-Day / Evergreen Gate) -> `kevkolin` (Research) -> `kevrast` (Stage 1) -> `kevanger` (Drafting in Turkish) -> `kevseo` (SEO & Semantic HTML) -> `kevrast` (Stage 2 & Explain) -> Staging.

In this repository (`amed-bulletin`), `kevanger` drafts news articles exclusively in **Turkish (`tr`)**.

## Sequence

### 1. Pre-Flight Verification of MCP Connectivity & Active Language Gate
- Verify that the `amed-bulletin` MCP server is functional and authenticated:
  ```bash
  python3 tools/mcp_health_check.py --preflight
  ```
  Environment truth: the mounted `amed-bulletin` MCP bridge proxies to live `https://amedbulten.com/mcp.php` — every MCP write touches the production database. The `http://localhost:8080` docker stack (`bulletin_app`) is a local development container; it does not receive production bridge writes.
- Call `list_site_settings(active_only='y')` to inspect `active_languages`, `default_language`, and `lang_status === 'y'`.
- Strictly separate **Conversational Language** (Kurmanji Kurdish with the user per `AGENTS.md` Rule 7) from **Target CMS Record Language (`record.lang`)**: every database field (`name`, `summary`, `content`, `title`, `description`, `keywords`, `seo`, and `mod_faq` `question`/`answer`) MUST be written 100% in the active target `record.lang` (`tr`).
- If connection fails or target language is inactive (`lang_status === 'n'`), report immediately in Kurmanji Kurdish and halt before drafting.

### 2. Same-Day Freshness & Evergreen Gate (`kevtemen`)
- Run `.agents/skills/kevtemen/scripts/check_freshness.py` against the research dossier:
  ```bash
  python3 .agents/skills/kevtemen/scripts/check_freshness.py --dossier "$DOSSIER_JSON"
  ```
- Exit 1 (`BLOCKED`): STOP. Do not draft, do not stage. Report the sentinel's warning verbatim and halt the job.
- Exit 0 (`PASSED (Same-Day)` or `PASSED (EVERGREEN)`): proceed to drafting. Content cannot be reclassified mid-pipeline to bypass freshness checks.

### 3. Research Dossier Intake (`kevkolin` & `kevrast` Stage 1)
- Receive the verified Research Dossier (JSON file, e.g. `/tmp/dossier_<slug>.json`) produced by `kevkolin` and pre-verified by `kevrast` (Stage 1). The dossier is the ONLY source of URLs for this article.
- **URL Chain-of-Custody (STRICT)**: URLs in the draft may ONLY be copied verbatim from the dossier file. Synthesizing, guessing, or shortening URLs is strictly prohibited. If an additional source is needed mid-drafting, first add it to the dossier with a role tag (`primary` / `supplementary` / `historical`) and re-run `kevrast` Stage 1 on the dossier.
- For **Diyarbakır (Amed)** local news items, ensure multi-source cross-referencing against the dedicated `amed_diyarbakir` registry in `.agents/skills/kevkolin/scripts/source_catalog.py`.
- If contradictory reports were identified by `kevkolin`, formulate the Turkish prose to explicitly reflect the variance:
  - Format: *"$A tarafından paylaşılan bilgilere göre ..., ancak $B kaynağında ..."*

### 4. Journalistic Drafting & Storytelling (Turkish - `tr`)
- Write fluent, engaging, and objective Turkish prose following professional newsroom standards (5N1K lead paragraph, inverted pyramid, concise paragraphs).
- Craft a compelling Turkish headline (`name` strictly **40 to 70 characters**) and deck summary (`summary` strictly **90 to 120 characters** matching `McpCrudToolsBase::assertSummaryLength()`).
- Embed in-text citations linking to verified live URLs — every URL copied verbatim from the dossier JSON:
  - Format: `<a href="$URL">$Name</a> tarafından aktarılan bilgilere göre` or `<a href="$URL">$Name</a> haberine göre`.
- **Date Grounding Constraint**:
  - Do NOT inject today's date or current date into the news content if it was not explicitly stated in the source text.
- **Turkish Orthography & Punctuation Standards**:
  - Use standard double quotes **`" ... "`** for direct speech and quotes. NEVER use French guillemets (`« ... »`).
  - Preserve authentic Turkish characters (`ç, ğ, ı, İ, ö, ş, ü`) across headlines, summaries, body text, and Wikipedia-style URL slugs.
- **Strict Code Separation**:
  - Do not insert inline `style` attributes, `<style>` blocks, or inline JavaScript handlers.

### 5. Ethical Image Intake, WebP Optimization & De-Branding
Extract and optimize editorial imagery using `.agents/skills/kevanger/scripts/process_news_image.py`:
- **Direct Article Image Intake (`--from-article`)**:
  Automatically extracts high-resolution, unbranded lead hero images (e.g. 1536w WebP variants from `<picture><source srcset>` over watermarked `og:image` social cards), validates pixels via Pillow, center-crops to 16:9 (1200×675), and compresses to WebP under 100 KB:
  ```bash
  python3 .agents/skills/kevanger/scripts/process_news_image.py \
    --from-article "https://www.bbc.com/news/articles/cq2e9v3030gyo" \
    --source "Getty Images" \
    --out /tmp/news_img.webp > /tmp/img_payload.json
  ```
- **Local / Direct Image Intake (`--input`)**:
  ```bash
  python3 .agents/skills/kevanger/scripts/process_news_image.py \
    --input /path/to/source_image.jpg \
    --source "Tigris Haber" \
    --out /tmp/news_img.webp > /tmp/img_payload.json
  ```
- **Representative Image Intake (`--representative` / `-r`)**:
  When no authentic source photograph exists, generate via `image-generator` subagent and pass `-r`:
  ```bash
  python3 .agents/skills/kevanger/scripts/process_news_image.py \
    --input /path/to/generated.png \
    --representative \
    --out /tmp/news_img.webp > /tmp/img_payload.json
  ```
  Attribution requires either `--source <name>` or `--representative`. Omitting both fails fast with exit 2.
- The output JSON includes `mcp_image_payload: {"image": {"data_base64": "...", "mime": "image/webp"}}` which feeds directly into `create_news` or `update_news`.
- Add attribution at the bottom of the news body:
  - Kurdish format: `<p><strong>Wêne: $Source</strong></p>` or `<p><strong>Wêne: Temsîlî</strong></p>`
  - Turkish format: `<p><strong>Fotoğraf: $Source</strong></p>` or `<p><strong>Fotoğraf: Temsili</strong></p>`

### 6. Automated Pipeline Verification (`verify_news_pipeline.sh`)
Execute the single-command automated editorial pipeline runner before calling MCP `create_news`:
```bash
tools/verify_news_pipeline.sh \
  --draft /path/to/draft.html \
  --dossier /path/to/dossier.json \
  --headline "$HEADLINE" \
  --summary "$SUMMARY" \
  --seo "$SEO_SLUG"
```
The script runs the complete 4-stage validation chain:
1. `kevtemen`: Freshness & anti-laundering checks.
2. `kevseo`: Headline length (40–70 chars), summary length (90–120 chars), heading hierarchy.
3. `kevrast`: Link health, claim anchoring, citation linkage, and `--explain` diagnostics.
4. Optional `audit_content_seo.py`: Post-staging rubric check with `--record-json` when staging locally.

### 7. Fact-Checking Diagnostics (`kevrast --explain`)
When troubleshooting draft issues, run `kevrast` with `--explain` to inspect paragraph-by-paragraph diagnostics:
```bash
python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py \
  --file /path/to/draft.html \
  --dossier /path/to/dossier.json \
  --explain
```
Pinpoints exact paragraph indices (`[P1]..[Pn]`), missing numbers, unlinked attribution phrases, and multi-link collisions with actionable English remediation.

### 8. Provenance Tracking, Categorization & Draft Staging
- Map the article to the appropriate Turkish (`tr`) category in `mod_news`.
- **Amed Framework Wikipedia-Style SEO Slug (AGENTS.md Rule 9)**:
  - Delimiters: Use underscores (`_`), NEVER hyphens (`-`).
  - Diacritics: Preserve authentic Turkish Unicode characters (`ç, ğ, ı, İ, ö, ş, ü`).
- Call `create_news` on `amed-bulletin` MCP server:
  - `lang: "tr"`
  - `username: "habermerkezi"` (**Never stage under `'admin'`**)
  - `status: "n"` (**Draft only**)
  - `deck: "y"`
  - Valid `summary` (90–120 characters)
  - Full HTML `content` with citations and image credit
  - Mandatory featured `image` object from `process_news_image.py` payload
  - Wikipedia-style `seo` slug

### 9. Editorial Reporting & User Handoff
Report completion to the user in Kurmanji (per `AGENTS.md` Rule 7), providing:
- Generated News ID
- Category name
- Turkish Headline and summary
- List of verified sources and detected contradictions (if any)
- Confirmation that the article is staged as a draft (`status: "n"`) awaiting human editorial approval.
