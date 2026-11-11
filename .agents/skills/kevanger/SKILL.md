---
name: kevanger
description: Curate, draft, structure, and stage Turkish-language news articles for Bulletin's mod_news via MCP. Coordinates with kevtemen (same-day freshness gate), kevkolin (research), kevrast (fact-checking), and kevseo (SEO & Semantic HTML) in the editorial pipeline.
---

# Kevanger News Drafting Skill (Turkish Editorial Pipeline)

## Operating Rule

- **STRICT NO-PUBLISH MANDATE**: AI is strictly prohibited from publishing news articles (`status: "y"`). Every new or updated article MUST be staged exclusively as a draft (`status: "n"`) for human editorial review and approval.
- **NO MCP BRIDGE BYPASS**: Always invoke MCP tools through native `call_mcp_tool` (`amed-bulletin`) when available, or via the official secret-opaque CLI wrapper `python3 tools/mcp_cli.py` (which enforces `--editorial-safe` by default). NEVER extract API keys from local bridge scripts (`mcp_config.json`) or bypass the local MCP bridge with direct `curl`/`urllib` requests to `mcp.php`.
- Keep this skill as a short ordered execution checklist. Follow the ordered pipeline strictly:
  `Stage 0: kevtemen (Seed URL Pre-Gate & Near-Midnight Recovery)` -> `kevkolin (Research & Local Radar)` -> `Stage 2: kevtemen (Dossier Freshness & Anti-Laundering Gate)` -> `kevrast (Stage 1 Dossier Verification)` -> `kevanger (Drafting in Turkish)` -> `kevseo (SEO & Semantic HTML)` -> `kevrast (Stage 2 Draft Audit with --explain)` -> `tools/verify_news_pipeline.sh` -> `Staging via MCP`.

In this repository (`amed-bulletin`), `kevanger` drafts news articles exclusively in **Turkish (`tr`)**.

## Sequence

### 1. Pre-Flight Verification of MCP Connectivity & Active Language Gate
- Verify that the `amed-bulletin` MCP server is functional and authenticated:
  ```bash
  python3 tools/mcp_health_check.py --preflight
  # Or when running inside swarm subagents without native MCP bindings:
  python3 tools/mcp_cli.py --help
  ```
  Environment truth: the mounted `amed-bulletin` MCP bridge proxies to live `https://amedbulten.com/mcp.php` — every MCP write touches the production database. The `http://localhost:8080` docker stack (`bulletin_app`) is a local development container; it does not receive production bridge writes.
- Call `list_site_settings(active_only='y')` to inspect `active_languages`, `default_language`, and `lang_status === 'y'`.
- Strictly separate **Conversational Language** (Kurmanji Kurdish with the user per `AGENTS.md` Rule 7) from **Target CMS Record Language (`record.lang`)**: every database field (`name`, `summary`, `content`, `title`, `description`, `keywords`, `seo`, and `mod_faq` `question`/`answer`) MUST be written 100% in the active target `record.lang` (`tr`).
- If connection fails or target language is inactive (`lang_status === 'n'`), report immediately in Kurmanji Kurdish and halt before drafting.

### 2. Same-Day Freshness & Evergreen Gate (`kevtemen`)
Enforce the two-pass freshness gate protocol:

1. **Stage 0 — Seed URL Pre-Gate (Zero Token Waste)**:
   When candidate URL(s) are supplied, run `check_freshness.py` *before* initiating research or building a dossier:
   ```bash
   python3 .agents/skills/kevtemen/scripts/check_freshness.py "$SEED_URL"
   ```
   - Exit 0 (`PASSED (Same-Day)` or `PASSED (EVERGREEN)`): proceed to Step 3 (`kevkolin`).
   - Exit 1 (`BLOCKED`): Check structured near-midnight diagnostics (`age_hours`, `is_yesterday_late_evening`).
     - If published in late evening of yesterday (`>= 20:00 Europe/Istanbul` or `is_yesterday_late_evening: true`): activate **Near-Midnight Recovery Protocol**:
       Run a bounded radar check via `python3 .agents/skills/kevkolin/scripts/source_catalog.py --scan-today --category amed_diyarbakir` or inspect official municipal/agency portals (`diyarbakir.bel.tr`, etc.) to verify whether an official or primary follow-up exists with `pub_date == today`.
       - If a verified same-day (`today`) primary source is found: promote that today-dated report to `role: "primary"` and demote the yesterday seed to `role: "supplementary"` (background context) in the dossier.
       - If NO same-day follow-up exists: HALT IMMEDIATELY (`BLOCKED`). Report exact publication age and absence of same-day follow-up. Anti-laundering contract remains mandatory (never launder via unrelated links).
     - If older than yesterday evening: HALT IMMEDIATELY (`BLOCKED`).

2. **Stage 2 — Dossier Gate (Anti-Laundering & Role Enforcement)**:
   Immediately after `kevkolin` compiles `$DOSSIER_JSON` and before `kevrast` Stage 1 or drafting:
   ```bash
   python3 .agents/skills/kevtemen/scripts/check_freshness.py --dossier "$DOSSIER_JSON"
   ```
   - Exit 0: proceed to Step 3 (`kevrast` Stage 1).
   - Exit 1: HALT. Verifies that at least one `role: "primary"` source is dated today in `Europe/Istanbul`, while `supplementary` and `historical` sources are exempt. Content cannot be reclassified mid-pipeline to bypass freshness checks.

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
- **Strict Code Separation Mandate (AGENTS.md Rule 1)**:
  - Do not insert inline `style` attributes, `<style>` blocks, or inline JavaScript handlers.

### 5. Ethical Image Intake, Master Optimization & Native Metadata
Extract and optimize editorial imagery using `.agents/skills/kevanger/scripts/process_news_image.py`:
- **Direct Article Image Intake (`--mode master`, default)**:
  Automatically extracts high-resolution, unbranded lead hero images, strips CDN wrappers (`/_next/image`), validates pixels via Pillow, inspects corner/edge watermarks, and outputs a clean master image (`width >= 1200px`, `max_width = 1920px`, unflattened aspect ratio, WebP quality 90 or lossless PNG intermediate < 1.5 MB):
  ```bash
  python3 .agents/skills/kevanger/scripts/process_news_image.py \
    --from-article "https://www.bbc.com/news/articles/cq2e9v3030gyo" \
    --source "Getty Images" \
    --alt "Diyarbakır Kent Parkı çalışmalarından genel görünüm" \
    --mode master \
    --out /tmp/news_img.webp > /tmp/img_payload.json
  ```
  This allows `Load::mod('image')->_resize(NEWS_IMAGE_SIZES)` in CMS backend to generate crisp, single-pass `1280×720` (`/`), `1200×900` (`/4x3`), and `1200×1200` (`/1x1`) Discover crops directly from full vertical image data without double lossy recompression or 1.78x stretch.
- **Local / Direct Image Intake (`--input`)**:
  ```bash
  python3 .agents/skills/kevanger/scripts/process_news_image.py \
    --input /path/to/source_image.jpg \
    --source "Tigris Haber" \
    --alt "Açılış töreninde konuşan yetkililer" \
    --mode master \
    --out /tmp/news_img.webp > /tmp/img_payload.json
  ```
- **Representative Image Intake (`--representative` / `-r`)**:
  When no authentic source photograph exists, generate via `image-generator` subagent and pass `-r`:
  ```bash
  python3 .agents/skills/kevanger/scripts/process_news_image.py \
    --input /path/to/generated.png \
    --representative \
    --alt "Diyarbakır Kent Parkı temsilî görseli" \
    --mode master \
    --out /tmp/news_img.webp > /tmp/img_payload.json
  ```
  Attribution requires either `--source <name>` or `--representative`. Omitting both fails fast with exit 2.
- **Secondary Inline Images (`--mode inline-16x9`)**:
  For optional secondary images inside article prose, use `--mode inline-16x9` (`1280×720`, `< 100 KB`).
- **Native Image Credit & Alt Schema**:
  Pass `image_alt` and `image_credit` natively in `create_news` / `update_news`.
  The public template (`news.twig`) automatically renders `<figure class="detail-hero">` with `alt="{{ d.image_alt|default(d.name) }}"` and `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>`, while populating JSON-LD `ImageObject.caption` and `ImageObject.creditText`.
  **DO NOT** append `<p><strong>Fotoğraf: ...</strong></p>` into the article body `content` prose.

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
- Call `create_news` on `amed-bulletin` MCP server (or via `tools/mcp_cli.py` in subagents):
  - `lang: "tr"`
  - `username: "habermerkezi"` (**Never stage under `'admin'`**)
  - `status: "n"` (**Draft only**)
  - `deck: "y"`
  - `name`: Turkish Headline (**40 to 70 characters**)
  - `summary`: Deck summary (**90 to 120 characters**)
  - `description`: Meta description (**synchronized with `summary`**, 90 to 120 characters)
  - `image_alt`: Descriptive alternative text (e.g. "Diyarbakır Kent Parkı çalışmalarından genel görünüm")
  - `image_credit`: Attribution credit (e.g. "Tigris Haber", "Getty Images", "Temsili / Arşiv")
  - `content`: Full semantic HTML body with outbound citations (zero inline styles, zero credit paragraphs)
  - `image`: Mandatory featured image object from `process_news_image.py` payload
  - `seo`: Wikipedia-style SEO slug (`AGENTS.md Rule 9`)

- **Swarm Sub-Agent CLI Bridge (`tools/mcp_cli.py`)**:
  When running in swarm workers or automated verification environments without direct `call_mcp_tool` access, stage via the official stdio bridge wrapper:
  ```bash
  python3 tools/mcp_cli.py call create_news \
    --args-file /tmp/staged_news_payload.json
  ```
  `tools/mcp_cli.py` enforces `--editorial-safe` by default: asserts `status == "n"`, `username == "habermerkezi"`, and rejects destructive `delete_*` calls.

### 9. Editorial Reporting & User Handoff
Report completion to the user in Kurmanji (per `AGENTS.md` Rule 7), providing:
- Generated News ID
- Category name
- Turkish Headline and summary
- List of verified sources and detected contradictions (if any)
- Confirmation that the article is staged as a draft (`status: "n"`) awaiting human editorial approval.
