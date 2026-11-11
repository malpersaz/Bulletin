# Handoff Report: Python Editorial Pipeline, CLI Tools, Scripts, and Quality Guards Audit

**Date:** 2026-11-11  
**Author:** explorer_survey_pipeline_2 (Teamwork Explorer)  
**Target Plan:** `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md`  
**Workspace:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  

---

## 1. Observation

Direct investigation of the workspace, Python scripts, CLI tools, skill documentation, and quality guards yielded the following factual observations:

### 1.1 Shared HTTP Infrastructure (`.agents/skills/_shared/editorial_http.py`)
- **Status:** File and parent directory do not exist (`ls -la .agents/skills/` confirms `.agents/skills/_shared` is absent).
- **Current Fragmentation:**
  - `check_freshness.py:34-36`: Disables TLS validation (`_SSL_CTX = ssl.create_default_context(); _SSL_CTX.check_hostname = False; _SSL_CTX.verify_mode = ssl.CERT_NONE`), lacks IP/SSRF validation (`validate_public_url()`), lacks `Retry-After` backoff handling.
  - `verify_facts_and_links.py:103, 113-117`: Uses verified TLS (`ssl.create_default_context()`), includes in-memory `_page_cache`, but lacks cross-process disk caching (forcing duplicate HTTP GETs across pipeline stages) and lacks `Retry-After` bounded backoff for HTTP 429/503.
  - `process_news_image.py:70-115`: Implements `validate_public_url()` and `SSRFSafeRedirectHandler` on verified TLS, but lacks browser-grade `Accept` / `Accept-Language` headers when fetching article HTML, risking hotlink/WAF rejections.
  - `source_catalog.py`: Completely static without any HTTP fetcher.

### 1.2 `kevtemen` Freshness Sentinel (`.agents/skills/kevtemen/scripts/check_freshness.py`)
- **ISO-8601 Fractional Seconds Timezone Drop:** Lines 50-52 define:
  ```python
  RE_ISO = re.compile(
      r"(\d{4})-(\d{2})-(\d{2})"
      r"(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?(Z|[+-]\d{2}:?\d{2})?)?")
  ```
  Timestamps with milliseconds (`2026-11-10T23:13:00.000Z`) match up to `:00`, leaving `.000Z` unmatched (`m.group(7) is None`). `_from_iso_match()` (lines 100-106) falls back to naive Istanbul time (`+03:00`), turning a same-day UTC story (`2026-11-11 02:13+03:00`) into a stale timestamp (`2026-11-10 23:13+03:00`), triggering a false `BLOCKED`.
- **Non-Content Tag Pollution:** Line 64 (`TAG_RE = re.compile(r"<[^>]+>")`) and line 160 (`text = TAG_RE.sub(" ", html)`) strip angle brackets without removing `<script>`, `<style>`, `<aside>`, `<nav>`, or `<footer>` inner text. Next.js Flight JSON payloads (`"date":"11-11-2026"`) and sidebar articles pollute date scanning.
- **Priority Tiering & Newest Selection:** `extract_pub_date()` (lines 148-158, 214) groups `<time datetime="...">` into Priority 1 alongside `<meta property="article:published_time">`, and line 214 sorts by `min(candidates, key=lambda c: (c[0], -c[1].timestamp()))`. This explicitly picks the **newest** timestamp among equal priorities, causing stale articles with today's sidebar `<time>` to fail open (`PASSED`).
- **Bare Navigation Word False-Pass:** `RE_TODAY = re.compile(r"\bbugün\b", re.I)` (lines 62, 201-202) matches unanchored "Bugün" anywhere in header/nav menus.
- **Repeated GETs & Duplicate Audit Lines:** `draft_hrefs = HREF_RE.findall(draft)` (lines 406-433) lacks URL deduplication. Multiple anchor tags targeting the same primary URL emit repeated `[OK]` audit lines and execute repeated network GET requests.
- **Near-Midnight Diagnostics:** Lines 248-250 output only `pub_date` strings; structured attributes (`age_hours`, `is_yesterday_late_evening`) are missing.

### 1.3 `kevrast` Verification Gate (`.agents/skills/kevrast/scripts/verify_facts_and_links.py`)
- **First-Paragraph-Only Verdict Defect:** In `audit_draft()` (lines 346-350):
  ```python
  for u in hrefs:
      role = role_map.get(self.norm_url(u))
      block = self._find_citing_block(u, text)
      url_audit.append(self.audit_url(u, block, role, skip_network))
  ```
  `_find_citing_block()` (lines 270-277) returns only the first `<p>` block containing `u`. If a draft cites `u` cleanly in Paragraph 1 and again in Paragraph 4 with fabricated figures (`%95`), `url_audit` only contains Paragraph 1. `passed` (lines 382-397) is evaluated strictly against `url_audit` and exits `0` (`PASSED`), while `--explain` (lines 400-498) evaluates all paragraphs and prints `[R3_MISSING_NUMBER] ERROR`.
- **Script Number Pollution & Substring Matching:**
  - `strip_tags()` (lines 90-91) does not strip `<script>` or `<style>` blocks. JavaScript bundle numbers pollute `target_nums`.
  - Line 224: `if not any(n == t or n in t for t in target_nums):` uses `n in t`, allowing any 2-digit hallucinated number to pass if it is a substring of an article ID, timestamp, or telephone number (e.g., `12 in 1287295`).
- **R2 Single-Segment Over-Rejection:** `classify_precision()` (lines 178-183) hard-rejects all single-segment URLs (`len(segments) <= 1`) lacking query IDs or numeric article extensions as `section_front`, rejecting valid vendor release/download endpoints (e.g., `https://antigravity.google/download`).
- **Turkish Agglutinative Morphology & Unicode Case Folding:**
  - `extract_entities()` (line 212) only strips apostrophes (`tok.split("'")[0]`). Institutional proper nouns without apostrophes (*Diyarbakır Büyükşehir Belediyesine*) fail R3 entity matching.
  - Line 236: `re.search(..., re.I)` fails on Turkish dotted/dotless `İ/i` and `I/ı`.
- **Cross-Language Anchoring Gap:** Non-Turkish primary documentation (e.g. English docs with terms like `Coordinator`, `Workers`) fails R3 entity anchoring against Turkish prose (*Koordinatör*, *İşçiler*). `dossier.schema.json` currently lacks `source_lang` and `source_anchors` declarations.
- **Attribution Lexicon Gaps:** `CITATION_PHRASE_RE` (lines 47-53) omits common journalistic phrases: `paylaşılan bilgilere göre`, `analize göre`, `sürüm notlarına göre`, `aktarıldığı üzere`, `kaynağında`.

### 1.4 `kevanger` Image Intake (`.agents/skills/kevanger/scripts/process_news_image.py`)
- **Query Stripping Next.js Collision:** Line 303 executes:
  ```python
  u = c["url"].split("?")[0].rstrip("/")
  ```
  On Next.js (`/_next/image?url=...`), this reduces all 54 candidates on a page to `https://rudaw.net/_next/image`, collapsing the entire list into one candidate.
- **`<main>` Sidebar Inclusion & `3840w` Descriptor Inflation:**
  - Lines 148-150 treat `<main>` identically to `<article>`, awarding a `+500` bonus to sidebar and ticker images.
  - Next.js responsive `srcset` descriptors (`3840w`) award `score = 3840 + 500 = 4340`, defeating the authentic hero `og:image` (score 1024).
- **Double Lossy Compression & Upscaling:** Lines 56-57 crop to `1200×675` WebP (`< 100 KB`). CMS `ImageService::_resize()` then upscales this pre-compressed image to `1280×720` and `1200×1200`, causing visual artifacting. Needs `--mode master` outputting clean unflattened `>= 1200px` masters.

### 1.5 `kevkolin` Source Catalog (`.agents/skills/kevkolin/scripts/source_catalog.py`)
- **Current State:** A static dictionary of URLs and quotas (`VERIFIED_SOURCES`).
- **Gap:** Completely lacks `--scan-today` RSS/Atom/sitemap scanning, requiring manual search queries to find same-day local stories.

### 1.6 SEO Auditor Alignment (`kevseo/scripts/audit_seo.py` vs `tools/audit_content_seo.py`)
- `audit_seo.py`:
  - Treats 0 `<h2>` tags as a non-blocking `WARNING` (line 59).
  - Skips `--headline` and `--summary` checks if arguments are omitted (lines 152-153).
  - Completely omits `description` validation and summary-description sync (`description == summary`).
- `tools/audit_content_seo.py --profile editorial`:
  - Strictly requires `summary` length 90–120 characters (lines 397-405).
  - Strictly requires `description == summary` (lines 411-418).
  - Rewards `>= 3 <h2>` headings for full zone points (line 382).

### 1.7 Swarm CLI Wrapper (`tools/mcp_cli.py`)
- **Status:** File does not exist in `tools/`.
- **Constraint:** Swarm worker subagents face `server amed-bulletin is not allowed in this context` when invoking `call_mcp_tool`. Direct token extraction from `amed_bulletin_mcp.py` is forbidden.
- **Bridge Reality:** `~/.gemini/antigravity/bin/amed_bulletin_mcp.py` is present and functional as an executable JSON-RPC stdio bridge. Spawning it via subprocess allows clean, secret-opaque MCP tool execution with editorial policy validation.

### 1.8 Skill Documentation & Rule Citations
- **Stale Rule Citations Identified:**
  1. `.agents/skills/kevseo/SKILL.md:39`: Cites `"AGENTS.md Rule 2"` for Strict Code Separation (actually **`AGENTS.md Rule 1`**).
  2. `.agents/skills/kevseo/scripts/audit_seo.py:63`: Cites `"AGENTS.md Rule 2"` (actually **`AGENTS.md Rule 1`**).
  3. `tools/code_separation_guard.py:6`: Cites `"AGENTS.md Rule 2"` (actually **`AGENTS.md Rule 1`**).
  4. `mod/mod_news/validator/add.validator.php:141`: Cites `"Rule 10"` for Wikipedia-style SEO slugs (actually **`AGENTS.md Rule 9`**).
  5. `mod/mod_news/validator/edit.validator.php:140`: Cites `"Rule 10"` (actually **`AGENTS.md Rule 9`**).
  6. `docs/CODE_QUALITY_AGENT_STRATEGY.md:16, 20`: Cites `"AGENTS.md Rule 7"` for English code/comments (actually **`AGENTS.md Rule 6`**; Rule 7 is Kurmanji Kurdish user communication protocol).
- **Workflow Order Contradiction:**
  - `kevanger/SKILL.md:30-33` places `check_freshness.py --dossier "$DOSSIER_JSON"` at Step 2, before Step 3 (`kevkolin` research) has generated the dossier.
  - `kevtemen/SKILL.md:34` orders agents to `STOP EVERYTHING` on near-midnight yesterday stories, precluding a bounded check for same-day official municipal follow-ups.

### 1.9 Test Suite & Quality Guards
- `tools/verify_news_pipeline.sh`: Exists, is syntactically valid (`bash -n` exit 0), and runs Stages 1–3 and optional Stage 4.
- `i18n_guard.py`: Clean on clean working tree.
- `module_boundary_guard.py --all`: Clean across 9,672 files (0 violations).
- `min_css_guard.py`: Clean on all 4 `.min.css` bundles.
- `code_separation_guard.py`: Passed cleanly on `templates/amed/index/mod_news/news.twig`.
- All Python scripts pass `python3 -m py_compile`.

---

## 2. Logic Chain

1. **Shared Transport Logic:**
   - *Observation:* `check_freshness.py` disables TLS verification, while `verify_facts_and_links.py` verifies TLS; `process_news_image.py` has SSRF checks, while `check_freshness.py` does not; no script has `Retry-After` backoff or shared caching.
   - *Inference:* Divergent HTTP implementations create security vulnerabilities (MITM on freshness checks) and waste round-trips (up to 4 identical GETs per URL across the pipeline), risking edge rate-limiting (HTTP 429).
   - *Conclusion:* A centralized `.agents/skills/_shared/editorial_http.py` module enforcing verified TLS, SSRF guards, standard browser headers, `Retry-After` backoff, and a short-TTL URL cache is required.

2. **Freshness Sentinel Reliability:**
   - *Observation:* `RE_ISO` drops fractional seconds, `min(candidates, ...)` picks the newest timestamp, `TAG_RE` does not strip `<script>` bodies, and URLs in `--file` mode are not deduplicated.
   - *Inference:* Legitimate same-day stories in UTC with `.000Z` are falsely blocked, stale articles with sidebar `<time>` or inline JSON timestamps falsely pass, and duplicate draft links waste network calls.
   - *Conclusion:* `check_freshness.py` must parse fractional seconds, strip non-content tags before scanning, prioritize earliest article `datePublished`, and deduplicate target URLs before fetching.

3. **Anti-Hallucination & Provenance Rigor:**
   - *Observation:* `audit_draft()` evaluates only the first citing block in `url_audit`, while `--explain` evaluates every paragraph; `strip_tags()` leaves `<script>` numbers; `n in t` allows partial digit matches; single-segment vendor URLs fail precision; Turkish institutional suffixes lack apostrophe splitting.
   - *Inference:* A draft can pass `kevrast` with exit 0 even if paragraphs 2+ contain hallucinated figures; genuine vendor pages like `/download` and non-Turkish docs are blocked falsely; and unlinked attributions like `analize göre` evade R4.
   - *Conclusion:* `audit_draft()` must evaluate all citing blocks (`_find_all_citing_blocks()`), exact normalized numeric equality must replace `n in t`, single-segment vendor pages must be allowed under tight anti-laundering conditions, and two-sided `source_lang`/`source_anchors` must be supported.

4. **Image Pipeline Accuracy:**
   - *Observation:* Next.js URLs are query-stripped to `/_next/image`, collapsing 54 candidates into 1; `<main>` container grants `+500` bonus to sidebar thumbnails; Next.js descriptor inflation (`3840w`) trumps `og:image`.
   - *Inference:* Unrelated sidebar photos hijack the hero image selection, and downsampling to `1200×675` before CMS upscaling causes double-compression degradation.
   - *Conclusion:* `process_news_image.py` must unwrap Next.js origin URLs before deduplication, restrict hero bonuses strictly to `<article>`, cap descriptor inflation, and export high-res masters (`--mode master`).

5. **Subagent Execution & Stdio Bridge:**
   - *Observation:* Subagents lack `call_mcp_tool` access in their sandbox; direct token access to `mcp.php` is strictly forbidden.
   - *Inference:* Multi-agent workflows are deadlocked from staging drafts or auditing staged records.
   - *Conclusion:* `tools/mcp_cli.py` is needed as a safe CLI wrapper around the existing stdio bridge `amed_bulletin_mcp.py` with built-in `--editorial-safe` draft protections.

6. **Documentation & Rubric Parity:**
   - *Observation:* Stale references cite Rule 2 instead of Rule 1 for code separation, Rule 10 instead of Rule 9 for slugs, and Rule 7 instead of Rule 6 for English code; `audit_seo.py` permits conditions that fail `audit_content_seo.py`.
   - *Inference:* Agents and scripts operating under divergent rubrics create false passes in pre-staging that fail in post-staging.
   - *Conclusion:* Synchronize all rule citations and align `audit_seo.py` strictly with `audit_content_seo.py --profile editorial`.

---

## 3. Caveats

1. **Read-Only Scope:** In accordance with the explorer archetype, no source files, skill scripts, or configuration files were modified during this investigation.
2. **Network Sandbox:** Network requests to live external news sources (e.g. `rudaw.net`, `tigrishaber.com`) during testing were constrained or skipped via `--no-network` to prevent side effects and edge throttling.
3. **Local Docker DB vs Production Bridge:** The local Docker container (`bulletin_app`) runs an isolated local database and does not receive writes from the production MCP bridge (`amedbulten.com`). Staged draft verifications must query the production bridge via `get_news`.

---

## 4. Conclusion

The editorial pipeline analysis confirms that all 12 defects and capability gaps documented in `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md` (specifically **A1–A5** and **B4–B6**) are verified against the actual codebase:
- High-risk defects (Next.js image query collision, first-paragraph-only verdict bypass, disabled TLS in `check_freshness.py`, ISO fractional seconds timezone loss) are confirmed by exact code citations and behavior.
- Missing components (`editorial_http.py`, `tools/mcp_cli.py`, `--scan-today` in `source_catalog.py`, `source_anchors` in `dossier.schema.json`) are confirmed absent and ready for implementation.
- Stale rule citations across `kevseo/SKILL.md`, `audit_seo.py`, `code_separation_guard.py`, `add.validator.php`, `edit.validator.php`, and `CODE_QUALITY_AGENT_STRATEGY.md` have been fully inventoried.
- All existing quality guards (`i18n_guard.py`, `module_boundary_guard.py`, `min_css_guard.py`, `code_separation_guard.py`) are fully operational and ready to serve as regression validation gates during the implementation phase.

---

## 5. Verification Method

To independently verify the observations and findings in this report, execute the following commands in the workspace root:

1. **Verify Missing Files:**
   ```bash
   test ! -f .agents/skills/_shared/editorial_http.py && echo "editorial_http.py missing as observed"
   test ! -f tools/mcp_cli.py && echo "mcp_cli.py missing as observed"
   ```

2. **Verify `check_freshness.py` ISO Fractional Second & TLS Flaws:**
   ```bash
   grep -n "_SSL_CTX.check_hostname = False" .agents/skills/kevtemen/scripts/check_freshness.py
   grep -n -A 3 "RE_ISO = re.compile" .agents/skills/kevtemen/scripts/check_freshness.py
   ```

3. **Verify `verify_facts_and_links.py` First-Paragraph Verdict & Substring Flaws:**
   ```bash
   grep -n -C 5 "def _find_citing_block" .agents/skills/kevrast/scripts/verify_facts_and_links.py
   grep -n "n == t or n in t" .agents/skills/kevrast/scripts/verify_facts_and_links.py
   grep -n -C 5 "classify_precision" .agents/skills/kevrast/scripts/verify_facts_and_links.py
   ```

4. **Verify `process_news_image.py` Next.js Query Stripping & `<main>` Inclusion:**
   ```bash
   grep -n 'c\["url"\].split("?")\[0\]' .agents/skills/kevanger/scripts/process_news_image.py
   grep -n 'tag_lower in ("article", "main")' .agents/skills/kevanger/scripts/process_news_image.py
   ```

5. **Verify Stale Rule References:**
   ```bash
   grep -n "AGENTS.md Rule 2" .agents/skills/kevseo/SKILL.md .agents/skills/kevseo/scripts/audit_seo.py tools/code_separation_guard.py
   grep -n "Rule 10" mod/mod_news/validator/add.validator.php mod/mod_news/validator/edit.validator.php
   grep -n "AGENTS.md Rule 7" docs/CODE_QUALITY_AGENT_STRATEGY.md
   ```

6. **Verify Quality Guards Integrity:**
   ```bash
   python3 tools/i18n_guard.py
   python3 tools/module_boundary_guard.py --all
   python3 tools/min_css_guard.py
   python3 tools/code_separation_guard.py --path templates/amed/index/mod_news/news.twig
   bash -n tools/verify_news_pipeline.sh
   ```
