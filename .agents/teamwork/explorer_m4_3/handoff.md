# Handoff Report: Milestone 4 Explorer 3 — Skill Docs Synchronization & Rule Citation Corrections

**Date**: 2026-11-11  
**Investigator**: Explorer 3 (`explorer_m4_3`)  
**Workspace**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Target Files**:
- `.agents/skills/kevanger/SKILL.md`
- `.agents/skills/kevtemen/SKILL.md`
- `.agents/skills/kevkolin/SKILL.md`
- `.agents/skills/kevrast/SKILL.md`
- `.agents/skills/kevseo/SKILL.md`
- `tools/code_separation_guard.py`
- `docs/CODE_QUALITY_AGENT_STRATEGY.md`
- (Cross-check files: `.agents/skills/kevseo/scripts/audit_seo.py`, `mod/mod_news/validator/add.validator.php`, `mod/mod_news/validator/edit.validator.php`)

---

## 1. Observation

Direct code and documentation observations were gathered across the repository:

### 1.1 `.agents/skills/kevanger/SKILL.md`
- **Line 11**: Prohibits MCP bypass (`call_mcp_tool` only) but omits the sanctioned secret-opaque stdio CLI wrapper `tools/mcp_cli.py`:
  > *"Always invoke MCP tools through `call_mcp_tool` (`amed-bulletin`). NEVER extract API keys from local bridge scripts or bypass the local MCP bridge with direct `curl`/`urllib` requests to `mcp.php`."*
  Swarm sub-agents lack native `call_mcp_tool` bindings, causing deadlocks unless `tools/mcp_cli.py` is documented.
- **Line 13**: Summarizes pipeline order without distinguishing Stage 0 vs Stage 2:
  > *"Follow the 5-tier pipeline strictly: `kevtemen` (Same-Day / Evergreen Gate) -> `kevkolin` (Research) -> `kevrast` (Stage 1) -> `kevanger` (Drafting in Turkish) -> `kevseo` (SEO & Semantic HTML) -> `kevrast` (Stage 2 & Explain) -> Staging."*
- **Lines 29–36 (Section 2)**: Only shows running `check_freshness.py --dossier "$DOSSIER_JSON"`. It omits Stage 0 seed URL pre-flight checking before research and omits the Near-Midnight Recovery Protocol.
- **Lines 54–56 (Section 4)**: Cites code separation without specifying `AGENTS.md Rule 1`.
- **Lines 57–87 (Section 5)**: Describes center-cropping to `1200×675` WebP under 100 KB and appending `<p><strong>Fotoğraf: $Source</strong></p>` to `mod_news.content` body prose. This causes double lossy recompression/upscaling in `McpCrudToolsBase::storeUploadedImage()` and mixes image metadata into article prose.
- **Lines 114–128 (Section 8)**: Lists `create_news` arguments without `image_alt`, `image_credit`, or `description` (synchronized with `summary`), and lacks instructions for invoking `tools/mcp_cli.py` from swarm sub-agents.

### 1.2 `.agents/skills/kevtemen/SKILL.md`
- **Lines 10–17 (Operating Rule)**: Mandates strict same-day fail-closed gate and evergreen bypass, but does not describe the two-pass execution order (Stage 0 seed URL pre-gate vs Stage 2 dossier gate) or the Near-Midnight Recovery Protocol.
- **Lines 21–30 (Section 1)**: Conflates seed URL and dossier checks into a single step without defining the two-pass lifecycle:
  > `python3 .agents/skills/kevtemen/scripts/check_freshness.py --dossier /path/to/dossier.json`
  > `python3 .agents/skills/kevtemen/scripts/check_freshness.py "$URL1" "$URL2"`
- **Lines 32–36 (Section 2)**: Instructs agents to halt on exit 1 without utilizing structured near-midnight diagnostics (`age_hours`, `is_yesterday_late_evening`) emitted by `check_freshness.py`.

### 1.3 `.agents/skills/kevkolin/SKILL.md`
- **Lines 8–10 (Operating Rule)**: Does not define the dependency on Stage 0 `kevtemen` pre-gate clearance or the hand-off to Stage 2 `kevtemen` dossier check.
- **Lines 26–65 (Section 2)**: Only lists static outlet domains; omits the newly implemented Local News Radar `source_catalog.py --scan-today` and the Near-Midnight Recovery check for same-day official follow-ups.
- **Lines 80–99 (Section 4)**: Omits schema support for `source_lang` and `source_anchors` in `dossier.schema.json`.
- **Lines 105–109 (Section 5)**: Jumps directly to `kevrast` Stage 1 without invoking `kevtemen` Stage 2 on the compiled dossier.

### 1.4 `.agents/skills/kevrast/SKILL.md`
- **Lines 18–30 (Section 2)**: Lists Stage 2 and Stage 1 commands, but lacks explanation of `--explain` diagnostics (`[P1]..[Pn]`).
- **Lines 31–40 (Section 3)**:
  - R1 does not cite shared `editorial_http.py` transport with TLS verification, SSRF guard, and `Retry-After` backoff.
  - R2 does not mention the vendor single-segment allowlist (`/download`, `/releases`, `/changelog`).
  - R3 does not mention exact numeric equality, Turkish morphology (`tr_fold()`), institutional suffix stemming, or cross-language anchoring (`source_lang` + `source_anchors`).
  - R4 does not differentiate `OUTLET_ATTRIBUTION_RE` (strict same-paragraph link) from `SPEECH_ATTRIBUTION_RE` (allows preceding paragraph link).

### 1.5 `.agents/skills/kevseo/SKILL.md`
- **Line 39**: Explicitly cites the wrong rule number:
  > `8. **Strict Code Separation Mandate (AGENTS.md Rule 2)**:`
  In `AGENTS.md`, Rule 1 is "Strict Code Separation Mandate", while Rule 2 is "Language & Documentation Mandate".
- **Lines 20–23 (Section 1.1)**: Mentions `<h2>` subheadings without specifying the mandatory `>= 3 <h2>` threshold required by `tools/audit_content_seo.py --profile editorial`.
- **Lines 27–28 (Section 1.3)**: Specifies 3 to 4 sentences per `<p>` tag but omits the warning threshold (paragraphs exceeding 5 sentences or 90 words).
- **Lines 36–38 (Section 1.7) & Lines 62–66 (Section 4)**: Instructs adding photo credit `<p>` blocks into `content`, which conflicts with the native `image_alt` and `image_credit` schema columns.
- **Lines 59–61 (Section 3.2)**: Mentions `summary` (90–120 chars) but omits mandatory `description` synchronization (`description == summary`, 90–120 chars).
- **Lines 74–79 (Section 6)**: Command line omits `--description` and `--strict-editorial`.

### 1.6 Rule Citation Fixes Across Code and Strategy Docs
- **`tools/code_separation_guard.py` line 6**:
  > `Enforces AGENTS.md Rule 2 (Strict Code Separation Mandate):`
  Incorrect; must cite `AGENTS.md Rule 1`.
- **`docs/CODE_QUALITY_AGENT_STRATEGY.md` lines 16 and 20**:
  > Line 16: `## Zero Non-English Tolerance Rule (`AGENTS.md` Rule 7)`
  > Line 20: `...must be rewritten in clean English immediately (`AGENTS.md` Rule 7):`
  Incorrect; in `AGENTS.md`, Rule 6 is "Module Internal Quality & i18n Rule" (English code/comments), while Rule 7 is "Conversation & Task Title Language Protocol" (dialogue with user in Kurmanji). Must cite `AGENTS.md Rule 6`.
- **`.agents/skills/kevseo/scripts/audit_seo.py` line 63**:
  > `# 3. Check for inline styles and scripts (AGENTS.md Rule 2)`
  Incorrect; must cite `AGENTS.md Rule 1`.
- **`mod/mod_news/validator/add.validator.php` line 141 & `edit.validator.php` line 140**:
  > `// Normalize SEO slug (Rule 10)`
  Incorrect; in `AGENTS.md`, Rule 9 is "URL Generation Parity (Wikipedia Style)".

---

## 2. Logic Chain

1. **Gate Ordering & Token Efficiency (A5)**:
   - When a user supplies candidate URLs, scraping articles or performing web searches before checking freshness wastes tokens on stale stories.
   - Stage 0 (`check_freshness.py "$SEED_URL"`) runs first with zero token waste.
   - However, once `kevkolin` compiles supplementary/historical links or an evergreen guide, Stage 2 (`check_freshness.py --dossier "$DOSSIER_JSON"`) is required to verify anti-laundering and role invariants.
   - Therefore, `kevanger/SKILL.md`, `kevtemen/SKILL.md`, and `kevkolin/SKILL.md` must clearly document this two-pass sequence.

2. **Near-Midnight Recovery Protocol (A1)**:
   - `check_freshness.py` enforces calendar equality in `Europe/Istanbul`. A story published at `23:13+03:00` yesterday fails calendar check today.
   - Halting immediately without checking for morning follow-ups on municipal/agency projects causes workflow friction.
   - When structured diagnostics show `is_yesterday_late_evening: true` (`>= 20:00 Europe/Istanbul`), running a single bounded scan via `source_catalog.py --scan-today --category amed_diyarbakir` allows discovering legitimate today-dated primary follow-ups (`role: "primary"`), demoting the yesterday story to `role: "supplementary"`.
   - If no today primary report exists, fail-closed enforcement immediately halts the pipeline.
   - Documenting this protocol in `kevtemen/SKILL.md`, `kevkolin/SKILL.md`, and `kevanger/SKILL.md` restores editorial flow while strictly preventing stale news laundering.

3. **Media Pipeline Modernization (B5)**:
   - Milestone 3 modernized the media pipeline: `process_news_image.py` provides `--mode master` (clean `>= 1200px` unflattened master), and `mod_news` has native `image_alt` and `image_credit` columns.
   - In `news.twig`, `<figure class="detail-hero">` automatically renders `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>` and populates Schema.org `ImageObject.creditText`.
   - Hence, appending `<p><strong>Fotoğraf: ...</strong></p>` into `content` is obsolete and forbidden.
   - `kevanger/SKILL.md` and `kevseo/SKILL.md` must be updated to mandate passing native attributes and omitting body credit paragraphs.

4. **Swarm Sub-Agent CLI Access (A2)**:
   - In swarm sub-agents, MCP tools cannot be called directly via `call_mcp_tool`.
   - `tools/mcp_cli.py` was created to provide a secret-opaque JSON-RPC stdio client enforcing `--editorial-safe` (`status: "n"`, `username: "habermerkezi"`, no `delete_*`).
   - `kevanger/SKILL.md` must document `tools/mcp_cli.py` as the sanctioned execution bridge for subagents.

5. **Rule Citation Alignment (B6)**:
   - Root `AGENTS.md` establishes the canonical priority hierarchy:
     - Rule 1: Strict Code Separation Mandate
     - Rule 2: Language & Documentation Mandate (English agent rules/skills/docs)
     - Rule 6: Module Internal Quality & i18n Rule (English code and comments)
     - Rule 7: Conversation & Task Title Language Protocol (Kurmanji dialogue with user)
     - Rule 9: URL Generation Parity (Wikipedia Style)
   - Stale references citing Rule 2 for code separation, Rule 7 for code comments, or Rule 10 for URL slugs cause confusion and fail static audits.
   - Correcting these citations across `kevseo/SKILL.md`, `code_separation_guard.py`, `CODE_QUALITY_AGENT_STRATEGY.md`, `audit_seo.py`, and `add.validator.php`/`edit.validator.php` ensures 100% repository consistency.

---

## 3. Caveats

1. **Read-Only Explorer Scope**: Explorer 3 performs inspection and specification only; no source code or documentation files were modified during this turn.
2. **Coordination with Concurrent Milestones**:
   - Milestone 3 Worker (`worker_m3_1`) is finalizing `tools/mcp_cli.py`, `process_news_image.py --mode master`, and `mod_news` schema columns (`image_alt`, `image_credit`).
   - Milestone 4 Explorer 1 (`explorer_m4_1`) is detailing `source_catalog.py --scan-today`.
   - Milestone 4 Explorer 2 (`explorer_m4_2`) is detailing `audit_seo.py` rubric alignment.
   - The specifications below coordinate seamlessly with these components.

---

## 4. Conclusion & Concrete Implementation Specification for Worker

Worker should apply the following line-by-line / section-by-section changes:

### 4.1 Update `.agents/skills/kevanger/SKILL.md`

#### Section: Operating Rule
Replace lines 10–14 with:
```markdown
- **STRICT NO-PUBLISH MANDATE**: AI is strictly prohibited from publishing news articles (`status: "y"`). Every new or updated article MUST be staged exclusively as a draft (`status: "n"`) for human editorial review and approval.
- **NO MCP BRIDGE BYPASS**: Always invoke MCP tools through native `call_mcp_tool` (`amed-bulletin`) when available, or via the official secret-opaque CLI wrapper `python3 tools/mcp_cli.py` (which enforces `--editorial-safe` by default). NEVER extract API keys from local bridge scripts (`mcp_config.json`) or bypass the local MCP bridge with direct `curl`/`urllib` requests to `mcp.php`.
- Keep this skill as a short ordered execution checklist. Follow the ordered pipeline strictly:
  `Stage 0: kevtemen (Seed URL Pre-Gate & Near-Midnight Recovery)` -> `kevkolin (Research & Local Radar)` -> `Stage 2: kevtemen (Dossier Freshness & Anti-Laundering Gate)` -> `kevrast (Stage 1 Dossier Verification)` -> `kevanger (Drafting in Turkish)` -> `kevseo (SEO & Semantic HTML)` -> `kevrast (Stage 2 Draft Audit with --explain)` -> `tools/verify_news_pipeline.sh` -> `Staging via MCP`.
```

#### Section: 1. Pre-Flight Verification of MCP Connectivity & Active Language Gate
Update lines 20–25 to mention `tools/mcp_cli.py`:
```markdown
- Verify that the `amed-bulletin` MCP server is functional and authenticated:
  ```bash
  python3 tools/mcp_health_check.py --preflight
  # Or when running inside swarm subagents without native MCP bindings:
  python3 tools/mcp_cli.py --help
  ```
  Environment truth: the mounted `amed-bulletin` MCP bridge proxies to live `https://amedbulten.com/mcp.php` — every MCP write touches the production database. The `http://localhost:8080` docker stack (`bulletin_app`) is a local development container; it does not receive production bridge writes.
```

#### Section: 2. Same-Day Freshness & Evergreen Gate (`kevtemen`)
Replace lines 29–36 with:
```markdown
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
```

#### Section: 4. Journalistic Drafting & Storytelling
Update line 54–56:
```markdown
- **Strict Code Separation Mandate (AGENTS.md Rule 1)**:
  - Do not insert inline `style` attributes, `<style>` blocks, or inline JavaScript handlers.
```

#### Section: 5. Ethical Image Intake, WebP Optimization & De-Branding
Replace lines 57–87 with:
```markdown
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
```

#### Section: 8. Provenance Tracking, Categorization & Draft Staging
Update lines 119–128:
```markdown
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
```

---

### 4.2 Update `.agents/skills/kevtemen/SKILL.md`

#### Section: Operating Rule
Replace lines 8–17 with:
```markdown
## Operating Rule

- **ABSOLUTE SAME-DAY GATE FOR NEWS**: For content classified as `news` (default), only content whose source publication date is TODAY (Europe/Istanbul) may be drafted, edited, or staged on the site.
- **TWO-PASS FRESHNESS GATE ORDER**:
  1. **Stage 0 (Seed URL Pre-Gate)**: Evaluates candidate URLs before any research or drafting begins to eliminate token waste.
  2. **Stage 2 (Dossier Gate)**: Evaluates compiled research dossiers, enforcing role tags (`primary` vs `supplementary` / `historical`) and anti-laundering rules.
- **NEAR-MIDNIGHT RECOVERY PROTOCOL**: When a user-supplied seed URL fails Stage 0 solely because it was published in the late evening of yesterday (`>= 20:00 Europe/Istanbul` or structured diagnostic `is_yesterday_late_evening: true`), permit a single bounded scan for a same-day primary follow-up (`today` in `Europe/Istanbul`) via `kevkolin` (`source_catalog.py --scan-today`). If an official or primary same-day report is verified, promote to `role: "primary"` and demote seed to `role: "supplementary"`. If no same-day follow-up exists, immediately HALT (`BLOCKED`). Never launder via unrelated links.
- **CONTENT-TYPE AWARE EVERGREEN BYPASS**: For content classified as `evergreen` in the research dossier, the same-day date gate is bypassed (`PASSED (EVERGREEN)`) and HTTP liveness is delegated to `kevrast` Stage 1, protected by strict anti-laundering checks:
  1. Mandatory `evergreen_rationale` (`>= 20` characters).
  2. All draft hyperlinks must be declared in the research dossier `sources`.
  3. Strict prohibition of breaking-news temporal markers (`bugün`, `dün`, `bu sabah`, `az önce`, `son dakika`).
- **FAIL-CLOSED**: Undatable, unreachable, or ambiguous sources are treated as NOT same-day and blocked.
- **NO OVERRIDE**: There is no `--allow-stale`, no backdating, no exceptions. If the user supplies an old or stale link, kevtemen stops the job and reports it.
- Keep this skill as a short ordered execution checklist.
```

#### Section: 1. Gate First — Two-Pass Gate Order
Replace lines 21–30 with:
```markdown
### 1. Gate First — Two-Pass Gate Order

#### Stage 0: Seed URL Pre-Gate (Zero Token Waste)
Run on candidate URL(s) before any research or drafting starts:
```bash
python3 .agents/skills/kevtemen/scripts/check_freshness.py "$SEED_URL"
```
- Exit 0 (`Status: PASSED (Same-Day)`): source is today-dated; proceed to `kevkolin` research.
- Exit 1 (`Status: BLOCKED`): check near-midnight structured diagnostics (`age_hours`, `is_yesterday_late_evening`).
  - If `is_yesterday_late_evening` is true: trigger Near-Midnight Recovery Protocol.
  - If older: STOP. Report exact publication date/age and halt.

#### Stage 2: Dossier Gate (Role-Aware & Anti-Laundering)
Run immediately after `kevkolin` compiles the research dossier JSON, before `kevrast` Stage 1:
```bash
python3 .agents/skills/kevtemen/scripts/check_freshness.py --dossier /path/to/dossier.json
```
- In `news` mode, verifies that at least one `role: "primary"` source is dated today in `Europe/Istanbul`. `supplementary` and `historical` sources are exempt from the date gate.
- In `evergreen` mode, verifies that temporal markers are absent and that all declared sources comply.
```

#### Section: 2. Verdict Handling & Diagnostics
Replace lines 32–36 with:
```markdown
### 2. Verdict Handling & Diagnostics
- Exit 0 (`Status: PASSED (Same-Day)` or `Status: PASSED (EVERGREEN)`): the source satisfies freshness or evergreen policy; pipeline proceeds.
- Exit 1 (`Status: BLOCKED`): STOP EVERYTHING. Do not research, do not draft, do not call `create_news`/`update_news`.
  - When reporting a block to the user, inspect structured near-midnight diagnostic attributes emitted by `check_freshness.py`:
    - `age_hours` and `is_yesterday_late_evening`.
    - Report exact age (e.g. *"Çavkanî 13.3 saet berê, di 23:13+03:00 de (47 xulek berî nîvê şevê) hatiye weşandin"*) rather than a generic date mismatch string.
- Never offer to proceed after a block; the gate is not negotiable and there is no approval path around it.
```

---

### 4.3 Update `.agents/skills/kevkolin/SKILL.md`

#### Section: Operating Rule
Replace lines 8–10 with:
```markdown
## Operating Rule

Keep this skill as a short ordered execution checklist. Follow multi-source investigation protocols strictly and output a structured Research Dossier for downstream validation and Turkish journalistic drafting.
- **Upstream Gate**: Only initiate research after Stage 0 `kevtemen` passes (or via Near-Midnight Recovery Protocol).
- **Downstream Hand-off**: Always validate the completed dossier via Stage 2 `kevtemen` before triggering `kevrast` Stage 1.
```

#### Section: 2. Multi-Source Investigation against Authoritative Registry & Local Radar
Add after line 28:
```markdown
#### Local News Radar (`--scan-today`) & Near-Midnight Recovery
Scan live feeds of verified outlets to discover same-day primary stories or follow-ups:
```bash
python3 .agents/skills/kevkolin/scripts/source_catalog.py --scan-today [--category amed_diyarbakir] [--query "topic"] [--json]
```
- Fetches live RSS, Atom, and Google News sitemaps of verified outlets (e.g. Amida Haber, Tigris Haber, Güneydoğu Ekspres, CEGA Medya, Diyarbakır BB) via shared `editorial_http.py`.
- Filters entries strictly to today's date in `Europe/Istanbul` (+03:00).
- **Near-Midnight Recovery Protocol**: When dispatched to investigate a near-midnight yesterday lead from Stage 0, execute `--scan-today --category amed_diyarbakir` to find today's primary follow-up reports. If found, mark the today-dated article as `role: "primary"` and yesterday's seed as `role: "supplementary"`. If none exist, halt.
```

#### Section: 4. Compile Structured Research Dossier
Add after line 99:
```markdown
5. **Cross-Language Source Anchoring**:
   For foreign-language or non-Turkish sources (e.g. English, Kurdish), declare `"source_lang": "en"` and `"source_anchors": ["...", "..."]` inside the source entry in `dossier.schema.json`. `kevrast` uses these anchors to evaluate cross-language numeric and entity equivalence in Turkish drafts.
```

#### Section: 5. Hand-off to Verification
Replace lines 105–109 with:
```markdown
### 5. Hand-off to Verification Pipeline
Follow the strict two-step pre-drafting gate sequence:
1. **Stage 2 kevtemen Dossier Gate**:
   ```bash
   python3 .agents/skills/kevtemen/scripts/check_freshness.py --dossier /tmp/dossier_<slug>.json
   ```
2. **Stage 1 kevrast Dossier Verification**:
   ```bash
   python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --dossier /tmp/dossier_<slug>.json
   ```
Drafting in `kevanger` may ONLY begin after both commands exit 0 (`PASSED`).
```

---

### 4.4 Update `.agents/skills/kevrast/SKILL.md`

#### Section: Sequence & Diagnostic Tooling
Update lines 18–30 to include `--explain`:
```markdown
### 2. Automated Fact & Link Audit
Stage 2 (draft verification against the dossier):
```bash
python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --file "$FILE_PATH" --dossier "$DOSSIER_JSON"
```
When diagnosing failures or reviewing paragraph-level provenance, run with `--explain`:
```bash
python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --file "$FILE_PATH" --dossier "$DOSSIER_JSON" --explain
```
Emits paragraph-by-paragraph diagnostics (`[P1]..[Pn]`), pinpointing unlinked attribution phrases, missing numeric matches, and multi-link collisions with actionable remedies.

Stage 1 (dossier pre-verification, before drafting):
```bash
python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --dossier "$DOSSIER_JSON"
```
```

#### Section: 3. Verification Criteria Checklist
Update lines 34–40:
```markdown
1. **Link Provenance Verification (Zero Fabricated, Shortened, or Broken URLs)**:
   - **R1 Liveness & Hardened Transport**: Every embedded link (`<a href="...">`) MUST be fetched with a full GET over verified TLS via `editorial_http.py` (SSRF guard on all redirects, `Retry-After` bounded retry for 429/503, and 10-minute cache). Soft-404 pages, redirect-to-home, and 4xx/5xx all count as FAILED.
   - **R2 Deep-Link Precision & Vendor Pages**: Citation href MUST be the content page carrying cited data. Domain roots and rubric fronts ALWAYS FAIL. Defined single-segment vendor download/release pages (`/download`, `/releases`, `/changelog`) are permitted under strict version/anchor matching.
   - **R3 Claim Anchoring & Morphology**: Distinctive figures in citing paragraphs must match the target page. Features exact normalized numeric matching (removing loose substring matches), Turkish case-folding (`tr_fold()`), institutional suffix stemming (`Belediyesine` -> `Belediyesi`), and cross-language anchoring via `source_lang` and `source_anchors` in the dossier.
   - **R4 Citation Completeness & Speech Separation**: Distinctively separates `OUTLET_ATTRIBUTION_RE` (e.g. `haberine göre`, `analize göre`, `sürüm notlarına göre`, `aktarıldığı üzere`, which strictly require a citation link in the same paragraph) from `SPEECH_ATTRIBUTION_RE` (bare speech verbs `dedi`, `söyledi`, `ifade etti`, which allow continuing a quote from an immediately preceding linked paragraph).
   - **R5 Dossier Modes**: `--dossier` alone audits dossier URLs (Stage 1); `--dossier` with `--file` audits draft against dossier (Stage 2).
```

---

### 4.5 Update `.agents/skills/kevseo/SKILL.md`

#### Section: Operating Rule
Replace lines 10–12 with:
```markdown
Keep this skill as a short ordered execution checklist. Follow the editorial pipeline:
`Stage 0: kevtemen` -> `kevkolin` -> `Stage 2: kevtemen` -> `kevrast Stage 1` -> `kevanger (Drafting)` -> `kevseo (SEO & HTML Architecture)` -> `kevrast Stage 2` -> `tools/verify_news_pipeline.sh` -> Staging.
```

#### Section: 1. Semantic HTML Content Architecture Mandate
- Update line 22 (Heading Hierarchy):
  ```markdown
  - **Mandatory `<h2>` Subheadings**: Every thematic section (context, background, key developments, official statements, local impact) MUST begin with a descriptive, entity-rich `<h2>` tag. Must contain at least **three (3) `<h2>` subheadings** to achieve parity with `audit_content_seo.py --profile editorial`.
  ```
- Update line 27–28 (Paragraph Length):
  ```markdown
  3. **Paragraph Length & Chunking**:
     - Maximum 3 to 4 sentences per `<p>` tag to optimize for mobile viewports and Google News snippet extraction. A warning is triggered if any paragraph exceeds 5 sentences or 90 words. Avoid dense walls of text.
  ```
- Update line 36–38 (Image Attribution):
  ```markdown
  7. **Native Image Attribution & Alt Text**:
     - Native columns `image_alt` and `image_credit` in `mod_news` automatically render `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>` under `<figure class="detail-hero">` and inject into JSON-LD `ImageObject.creditText`.
     - **DO NOT** inject image attribution paragraphs (such as `<p><strong>Fotoğraf: ...</strong></p>`) into `content` prose.
  ```
- **Line 39 Rule Citation Fix**:
  ```markdown
  8. **Strict Code Separation Mandate (AGENTS.md Rule 1)**:
     - Zero inline styles (`style="..."`, `<style>`), zero inline scripts, and zero inline event handlers (`onclick="..."`). Only clean semantic HTML tags.
  ```

#### Section: 3. Headline & Deck Summary Standards
Update lines 59–61:
```markdown
2. **Deck Summary (`summary`) & Meta Description (`description`)**:
   - Strictly 90–120 characters (enforced by schema and `McpCrudToolsBase::assertSummaryLength()`).
   - `description` MUST be identical to `summary` (`description == summary`, 90–120 characters) for `audit_content_seo.py` parity (`Meta Description Sync`).
```

#### Section: 6. Automated Audit Execution
Replace lines 74–79 with:
```markdown
### 6. Automated Audit Execution
Execute the automated SEO auditor script against the drafted article:
```bash
python3 .agents/skills/kevseo/scripts/audit_seo.py \
  --content "$DRAFT_HTML" \
  --summary "$SUMMARY" \
  --headline "$HEADLINE" \
  --description "$DESCRIPTION" \
  --seo "$SEO_SLUG" \
  --strict-editorial
```
Ensure the audit returns `Status: PASSED` before proceeding to `kevrast` (Stage 2).
```

---

### 4.6 Rule Citation Fixes in Code & Documentation

#### 1. `tools/code_separation_guard.py`
- **Line 6**:
  ```python
  # Target (current):
    Enforces AGENTS.md Rule 2 (Strict Code Separation Mandate):
  # Replacement:
    Enforces AGENTS.md Rule 1 (Strict Code Separation Mandate):
  ```

#### 2. `docs/CODE_QUALITY_AGENT_STRATEGY.md`
- **Line 16**:
  ```markdown
  <!-- Target (current): -->
  ## Zero Non-English Tolerance Rule (`AGENTS.md` Rule 7)
  <!-- Replacement: -->
  ## Zero Non-English Tolerance Rule (`AGENTS.md` Rule 6)
  ```
- **Line 20**:
  ```markdown
  <!-- Target (current): -->
  If non-English code or comments are encountered in legacy or touched files during development or refactoring, they must be rewritten in clean English immediately (`AGENTS.md` Rule 7):
  <!-- Replacement: -->
  If non-English code or comments are encountered in legacy or touched files during development or refactoring, they must be rewritten in clean English immediately (`AGENTS.md` Rule 6):
  ```

#### 3. `.agents/skills/kevseo/scripts/audit_seo.py`
- **Line 63**:
  ```python
  # Target (current):
          # 3. Check for inline styles and scripts (AGENTS.md Rule 2)
  # Replacement:
          # 3. Check for inline styles and scripts (AGENTS.md Rule 1)
  ```

#### 4. `mod/mod_news/validator/add.validator.php` & `mod/mod_news/validator/edit.validator.php`
- `mod/mod_news/validator/add.validator.php` line 141:
  ```php
  // Target:
          // Normalize SEO slug (Rule 10)
  // Replacement:
          // Normalize SEO slug (AGENTS.md Rule 9)
  ```
- `mod/mod_news/validator/edit.validator.php` line 140:
  ```php
  // Target:
          // Normalize SEO slug (Rule 10)
  // Replacement:
          // Normalize SEO slug (AGENTS.md Rule 9)
  ```

---

## 5. Verification Method

Once Worker implements these changes, verify using the following commands:

1. **Verify Python Syntax**:
   ```bash
   python3 -m py_compile tools/code_separation_guard.py
   python3 -m py_compile .agents/skills/kevseo/scripts/audit_seo.py
   ```
   *Expected: Exit code 0, no syntax errors.*

2. **Verify PHP Syntax**:
   ```bash
   php -l mod/mod_news/validator/add.validator.php
   php -l mod/mod_news/validator/edit.validator.php
   ```
   *Expected: `No syntax errors detected`.*

3. **Verify Stale Rule Citations Are Gone**:
   ```bash
   grep -rn "AGENTS\.md Rule 2" .agents/skills/ tools/code_separation_guard.py
   # Expected: 0 matches (or only historical plan docs)
   grep -rn "AGENTS\.md Rule 7" docs/CODE_QUALITY_AGENT_STRATEGY.md
   # Expected: 0 matches
   grep -rn "Rule 10" mod/mod_news/validator/
   # Expected: 0 matches
   ```

4. **Verify Quality Guards**:
   ```bash
   python3 tools/code_separation_guard.py
   python3 tools/module_boundary_guard.py --all
   python3 tools/i18n_guard.py
   ```
   *Expected: All guards report 100% clean (exit code 0).*
