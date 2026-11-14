# Engineering Plan: Antigravity v2.32 Agent Infrastructure & Workflow Modernization (v1.0.72)

**Target Platform Baseline:** Google Antigravity App `v2.32.0`, Antigravity CLI (`agy`) `v1.5.8`, Antigravity IDE `v2.7.0` (VS Code `v1.12.0`, JetBrains & Zed `v1.5.0`), Antigravity Python SDK `v0.1.23`, and `Orchestrator` multi-model execution (`Gemini 4 Carbon` + `Gemini 4 Hydrogen`)  
**Target Application Environment:** Local Docker stack (`bulletin_app`, `bulletin_web`, `bulletin_db`) & Host Linux; production MCP bridge `https://amedbulten.com/mcp.php`  
**Repository:** `AF/Bulletin` (`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`)  
**Date:** `2026-11-14`  
**Target Release:** `v1.0.72` (WP1 delivered on top of `v1.0.71`; WP2–WP5 deferred to `v1.0.73`)  
**Governing Standard:** `AGENTS.md` Priority Hierarchy (Rules 1–9), `docs/CODE_QUALITY_AGENT_STRATEGY.md`, `.agents/INDEX.md`  
**Status:** WP1 Implemented (`v1.0.72`) — hook contracts, portable plugin manifests, SVG logo, shared utilities, and unit tests delivered; WP2–WP5 deferred to `v1.0.73`.

---

## 0. Governance & Policy Alignment Matrix

Every work package in `v1.0.72` is designed to enforce the repository rules in `AGENTS.md` (Priority 1–9) deterministically through platform hooks, workflows, and configuration rather than relying solely on prompt compliance.

| Rule | Repository Mandate | `v1.0.72` Enforcement Mechanism |
| :--- | :--- | :--- |
| **Rule 1** | Strict Code Separation (`<style>`, `style="..."`, `on*=`) | `WP1` updates `PreToolUse` & `PostToolUse` hooks to intercept `write_file`, `edit_file`, and `multi_replace_file_content`; `WP2` integrates `tools/code_separation_guard.py` into `quality_audit_fanout.py`. |
| **Rule 2** | English-only `/docs/`, `SKILL.md`, and Agent rules | All plan documents, workflow files, skill checklists, and `.agents/` manifests remain strictly English. |
| **Rule 3** | Workspace Isolation, Zero Autonomous Cross-Repo Sync & Token Protection | `WP1` adds a `PreToolUse` workspace-boundary guard on `Path` and `run_command`; `WP4` adopts `Orchestrator` (~24% lower quota) and strict `"Re-plan if:"` handbacks; `WP5` locks `Non-Workspace File Access` to `ask`/`deny` in Project Settings. |
| **Rule 4** | Versioning & Production Parity (`docs/VERSION.md` + `config/site.config.php`) | Enforced during implementation release prep per `.agents/skills/github-release/SKILL.md` and `.agents/skills/task-validation/SKILL.md`. |
| **Rule 5** | Phone-Only Admin Surface (`templates/admin/`) | `WP2` includes `admin-mobile-check` in parallel quality fan-out; `WP5` scopes the browser allowlist to `http://localhost:8080` and `tools/mobile_simulator.html` phone viewports. |
| **Rule 6** | English-only Code Identifiers/Comments & 11-Locale i18n Keys | `WP2` runs `tools/i18n_guard.py` in Phase 1 before parallel module audits; `WP1` ensures `php -l` runs in both `PostToolUse` and `Stop` hooks. |
| **Rule 7** | Kurmanji Kurdish Dialogue & Approval Prompts | User reporting in `editorial_news_pipeline.py` (`WP2`) and scheduled digest outputs (`WP5`) remain Kurmanji Kurdish (Latin alphabet) while CMS content stays in `record.lang`. |
| **Rule 8** | Short, Ordered Operational `SKILL.md` Files | Skill updates in `WP2` add only concise 3–5 line workflow invocation pointers without inflating `SKILL.md` into knowledge bases. |
| **Rule 9** | Wikipedia-Style Native UTF-8 URL Slugs | `WP2` editorial workflow validates `seo` slug parity (underscores, preserved Unicode diacritics/casing) via `tools/verify_news_pipeline.sh`. |

---

## 1. Executive Summary & Audit Findings Table

A technical audit of `AF/Bulletin` against the November 2026 Google Antigravity platform release (`App v2.32.0`, `CLI v1.5.8`, `IDE v2.7.0`, `SDK v0.1.23`, and `Orchestrator`) identified five high-leverage gaps (`WP1`–`WP5`). Most critically, the repository's existing lifecycle hooks in `.agents/plugins/af-cms-synchronizer/` match only legacy 1.x tool names and ignore `Stop` hook loop-prevention fields, leaving deterministic code-separation and module-boundary gates bypassed on file writes and edits.

| Work Package | Priority | Layer | Current Gap Observed in `AF/Bulletin` | Target `v1.0.72` Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **WP1** (`v1.0.72` Done) | `P0` Critical | In-Repo Plugin & Configs | `hooks.json` & `pre_tool_guard.py` matched only `write_to_file`/`replace_file_content`; `stop_quality_gate.py` ignored `stdin` (`terminationReason`, `continuationActive`) and skipped `php -l`; `skills.json` used a hardcoded machine path; `plugins.json` used legacy schema; `plugin.json` lacked a logo. | Full `v2.32.0` camelCase hook contract compliance (`write_file`, `edit_file`, `multi_replace_file_content`, `write_to_file`, `replace_file_content`, `run_command`), shared `hook_utils.py`, bounded single-retry diff-scoped `Stop` gate, portable JSON manifests, SVG logo, and `tests/test_agent_hooks_v2.py`. |
| **WP2** (`v1.0.73`) | `P1` High | In-Repo Workflows & Skills | The 8-stage `kevanger` Turkish editorial pipeline and multi-module quality checks execute via manual ad-hoc prompts, wasting tokens when freshness/link gates fail late. | Two deterministic `run_workflow` Python pipelines under `tools/workflows/` (`editorial_news_pipeline.py` + `.workflow.json` and `quality_audit_fanout.py`) with upfront Python gates and strict JSON schemas. |
| **WP3** (`v1.0.73`) | `P1` High | In-Repo `.agents/` Hygiene | ~50 legacy Git-tracked ephemeral run directories (`orchestrator_*`, `teamwork/`, `victory_auditor_*`, `m1_*`, etc.) clutter `.agents/`; `.gitignore` only covers `.agents/worker_*/`. | Legacy run folders removed from Git index (`git rm -r`), `.gitignore` updated, and native `/swarm` (`workspace: "share"` + read-only `verifier`) codified as the sole parallel team standard. |
| **WP4** (`v1.0.73`) | `P2` Medium | In-Repo Agent Docs | `.agents/README.md` and `docs/PILOT_AGENT_REVIEW_FLOW.md` do not document `Orchestrator` (`Carbon` + `Hydrogen`) handback triggers, mode selection, or Antigravity's 24 KB / 20k-token rules budget. | Clear `Orchestrator` vs `run_workflow` vs `/swarm` decision matrix, standardized `"Re-plan if:"` triggers, and rule-budget ceiling tracking in `.agents/README.md`. |
| **WP5** (`v1.0.73`) | `P2` Medium | Host Settings & Runbook | `AGENTS.md` Rule 3 (no cross-repo access) and phone-only browser checks rely on prompt instructions rather than Antigravity App `v2.32.0` sandbox/allowlist controls. | Project-level `Non-Workspace File Access = ask/deny` lock, Browser Allowlist for `localhost:8080` & `amedbulten.com`, and optional read-only `/schedule` daily Amed news radar + MCP health check. |

---

## 2. Work Package 1 (P0): Lifecycle Hooks & Customization Manifest Modernization

### 2.1 Evidence & Root Cause

1. **Tool Matcher Drift (`.agents/plugins/af-cms-synchronizer/hooks.json:3-26`)**:
   - `PreToolUse` currently sets `"matcher": "write_to_file|replace_file_content|run_command"`.
   - `PostToolUse` currently sets `"matcher": "write_to_file|replace_file_content"`.
   - Antigravity App `v2.32.0` and CLI `v1.5.8` dispatch `write_file` and `edit_file` (and IDE integrations may dispatch `multi_replace_file_content`). Because neither `write_file` nor `edit_file` matches the regex, neither `pre_tool_guard.py` nor `post_tool_verifier.py` executes during normal editing turns.
2. **Argument Payload Drift & Missing Boundary Check (`.agents/plugins/af-cms-synchronizer/scripts/pre_tool_guard.py:95-124`)**:
   - `main()` inspects `if tool_name in ("write_to_file", "replace_file_content")` and reads `args.get("TargetFile")` with `args.get("CodeContent")` / `args.get("ReplacementContent")`.
   - `write_file` passes `{"Path": ..., "Content": ...}` and `edit_file` passes `{"Path": ..., "Target": ..., "Replacement": ...}`.
   - Additionally, `pre_tool_guard.py` does not verify whether a file write or `run_command` targets a path outside `AF/Bulletin` (such as sibling `AF/Business`), leaving `AGENTS.md` Rule 3 unguarded at the tool layer.
3. **Unbounded Loop Risk & Missing PHP Syntax Check in `Stop` Hook (`.agents/plugins/af-cms-synchronizer/scripts/stop_quality_gate.py:21-77`)**:
   - `stop_quality_gate.py` reads zero bytes from `stdin`. Under Antigravity `v2.32.0`, the `Stop` payload delivers `terminationReason` (`NO_TOOL_CALL`, `USER_CANCELED`, `ERROR`, `MAX_INVOCATIONS`, `MAX_FORCED_INVOCATIONS`, `MAX_TOKEN_BUDGET_EXCEEDED`, `TERMINAL_STEP_TYPE`, `TERMINAL_CUSTOM_HOOK`, `INJECTED_RESPONSE`) and `continuationActive` (`bool`).
   - Returning `{"decision": "continue", ...}` when `terminationReason != "NO_TOOL_CALL"` or when `continuationActive` is already `true` risks overriding user cancellation or trapping the agent in repeated forced continuations.
   - Furthermore, `stop_quality_gate.py` only runs `code_separation_guard.py` on modified `.twig`/`.html` files (lines 52–54), omitting `php -l` syntax checks on modified `.php` files and omitting `.php` files from `code_separation_guard.py`.
   - In `scripts/post_tool_verifier.py:57,71,93`, failure paths write to `stderr` and `sys.exit(1)` without checking hook JSON expectations cleanly, and clean paths print `{}`.
4. **Subagent Tool Schema & Hardcoded Manifest Paths**:
   - `.agents/plugins/af-cms-synchronizer/agents/sync-auditor.md:4-9` lists `view_file`, `run_command`, `grep_search`, `find_by_name`, `list_dir`, omitting `read_file`.
   - `.agents/skills.json:3` points to an absolute machine-specific path `"/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/skills"`, breaking portability across clones and `workspace: "share"` / `"branch"` worktrees.
   - `.agents/plugins.json:1-5` uses `{"plugins": ["./plugins/af-cms-synchronizer"]}` instead of the documented Antigravity 2.x `{"entries": [{"path": "..."}]}` configuration schema.

### 2.2 Target Design (Implementation in `v1.0.72`)

1. **Update `.agents/plugins/af-cms-synchronizer/hooks.json`**:
   - Set `PreToolUse` matcher to:
     `"write_file|edit_file|multi_replace_file_content|write_to_file|replace_file_content|run_command"`
   - Set `PostToolUse` matcher to:
     `"write_file|edit_file|multi_replace_file_content|write_to_file|replace_file_content"`
   - Keep `Stop` hook configured with `timeout: 30`.
2. **Harden `.agents/plugins/af-cms-synchronizer/scripts/pre_tool_guard.py`**:
   - Extract normalized file target via `target_file = args.get("Path") or args.get("TargetFile") or ""`.
   - Extract all candidate content strings uniformly across tool schemas:
     - `write_file`: `args.get("Content", "")`
     - `edit_file`: `args.get("Replacement", "")`
     - `write_to_file` / `replace_file_content`: `args.get("CodeContent") or args.get("ReplacementContent") or ""`
     - `multi_replace_file_content`: concatenated `chunk.get("ReplacementContent", "")` across `args.get("ReplacementChunks", [])`
   - Enforce `check_file_path(target_file)` (forbidden modules) and `check_code_content(target_file, content)` (inline CSS/JS rules) on every matched write/edit tool.
   - Enforce workspace isolation (`AGENTS.md` Rule 3): resolve `os.path.realpath(target_file)` and deny writes targeting sibling workspaces outside `repo_root` (explicitly permitting Antigravity's own artifact/scratch directory passed in `payload.get("artifactDirectoryPath")` and `/tmp/`).
   - For `run_command`, inspect `args.get("CommandLine", "")` against high-risk sibling-repo write/sync patterns targeting `AF/Business` (unless running read-only `tools/cross_repo_sync.py`) and return `{"decision": "deny", "reason": "..."}` when a cross-repo mutation attempt is detected.
3. **Make `.agents/plugins/af-cms-synchronizer/scripts/stop_quality_gate.py` Loop-Safe**:
   - Read JSON payload from `sys.stdin` safely (defaulting to `{}` on parse failure).
   - If `payload.get("continuationActive") is True`, immediately print `{"decision": "allow"}` (or `{}`) and exit `0` so the gate triggers at most once per turn.
   - Inspect `termination_reason = payload.get("terminationReason", "NO_TOOL_CALL")`. If `termination_reason != "NO_TOOL_CALL"` (e.g., `USER_CANCELED`, `ERROR`, `MAX_TOKEN_BUDGET_EXCEEDED`), allow stopping immediately without blocking the user.
   - On normal `NO_TOOL_CALL` completion, run:
     1. `python3 tools/module_boundary_guard.py`
     2. `php -l` on all modified `.php` files reported by `git status --porcelain`
     3. Diff-scoped code separation via shared `scripts/hook_utils.py` on added lines of tracked `.twig`, `.html`, `.htm`, and `.php` files (or full content of `-uall`-expanded untracked files outside `docs/`, `tools/`, `.agents/`, `tests/`, `lib/`, `vendor/`), preventing false positives on untouched legacy lines.
   - If violations exist, emit `{"decision": "continue", "reason": "..."}` with exact file paths and remediation hints; otherwise emit `{"decision": "allow"}`.
4. **Normalize `post_tool_verifier.py`, `plugin.json`, and `.agents/*.json`**:
   - Ensure `post_tool_verifier.py` reads `stdin` cleanly, scopes checks to the touched file when provided, prints `{}` with exit `0` in hook mode (logging diagnostics only if `logs/` exists), and supports `--strict` for CI/unit testing.
   - Keep `.agents/plugins/af-cms-synchronizer/agents/sync-auditor.md` untouched (passes `agy plugin validate --strict` cleanly) and add `assets/logo.svg` + `"logo": "assets/logo.svg"` in `plugin.json` (`version: "1.0.72"`, dropping unsupported `keywords`).
   - Update `.agents/skills.json` to `{"entries": [{"path": ".agents/skills", "exclude": ["_shared"]}]}` (portable across worktrees).
   - Update `.agents/plugins.json` to `{"entries": [{"path": ".agents/plugins"}]}` and validate the plugin with `agy plugin validate .agents/plugins/af-cms-synchronizer --strict`.

### 2.3 Acceptance Criteria

- New Python `unittest` suite `tests/test_agent_hooks_v2.py` exercises `pre_tool_guard.py`, `post_tool_verifier.py`, and `stop_quality_gate.py` via `subprocess` using realistic camelCase payloads:
  - `write_file` / `edit_file` touching `mod/mod_product/index.php` -> `decision == "deny"`.
  - `write_file` / `edit_file` injecting `style="color:red"` or `onclick="fn()"` into a `.twig` or `.php` file -> `decision == "deny"`.
  - `write_file` targeting `../Business/lib/Helper.php` -> `decision == "deny"`.
  - `write_file` writing a valid `.php` / `.twig` file under `AF/Bulletin` -> `decision == "allow"`.
  - `stop_quality_gate.py` receiving `{"terminationReason": "USER_CANCELED", "continuationActive": false}` -> allows stop even if workspace has dirty files.
  - `stop_quality_gate.py` receiving `{"terminationReason": "NO_TOOL_CALL", "continuationActive": true}` -> allows stop (zero infinite loops).
- Running `agy plugin validate .agents/plugins/af-cms-synchronizer` succeeds cleanly (when `agy` CLI `>= v1.5.8` is installed on host).

---

## 3. Work Package 2 (P1): Dynamic Workflows (`run_workflow`) for Editorial & Quality Pipelines

Antigravity App `v2.32.0` and SDK `v0.1.23` introduce Python-based **Dynamic Workflows** (`run_workflow`), providing `phase()`, `agent()`, `parallel()`, `pipeline()`, `call_tool()`, `args`, and `workflow.json` argument schema validation. Converting our two highest-frequency multi-step processes into versioned workflow scripts eliminates manual orchestration drift and prevents wasted frontier model tokens on failing pre-checks.

### 3.1 Workflow 2.1 — Editorial News Pipeline (`tools/workflows/editorial_news_pipeline.py`)

Create `tools/workflows/editorial_news_pipeline.py` alongside `tools/workflows/editorial_news_pipeline.workflow.json`:

1. **Validated Input Schema (`editorial_news_pipeline.workflow.json`)**:
   - `seed_urls` (`array` of `string`, optional) or `topic` (`string`, optional) — at least one required by preflight validation.
   - `category_hint` (`string`, optional)
   - `target_lang` (`string`, default `"tr"`, enum `["tr"]` for `kevanger` in `AF/Bulletin`)
   - `dry_run` (`boolean`, default `false`) — runs full verification without calling `create_news`.
2. **Phased Execution Architecture**:
   - **Phase 1 — Zero-Cost Preflight & Freshness Gate (`call_tool` / Python deterministic checks, zero LLM spawn on failure)**:
     - Execute `python3 tools/mcp_health_check.py --preflight`. Halt immediately if the MCP bridge is unreachable or `lang_status != 'y'`.
     - If `seed_urls` are provided, execute `python3 .agents/skills/kevtemen/scripts/check_freshness.py "<url>"` for each URL. On near-midnight diagnostics (`is_yesterday_late_evening == true`), run `source_catalog.py --scan-today --category amed_diyarbakir`; otherwise halt cleanly and report the exact `BLOCKED` age to the user in Kurmanji.
   - **Phase 2 — Research & Anti-Laundering Dossier (`kevkolin` -> `kevtemen` Stage 2 -> `kevrast` Stage 1)**:
     - Run a structured `agent()` with JSON `schema` returning `{"dossier_path": ..., "primary_sources": [...], "contradictions": [...]}`.
     - Deterministically execute `check_freshness.py --dossier <dossier_path>` and `kevrast` Stage 1 before any prose drafting starts.
   - **Phase 3 — Turkish Drafting, Image Master Prep & SEO Audit (`kevanger` + `kevseo`)**:
     - Draft `tr` headline (`40–70` chars), `summary` (`90–120` chars), synchronized `description`, Wikipedia-style `seo` slug (`_` delimiters, authentic Turkish UTF-8 characters), and semantic HTML body (zero inline CSS/JS, double quotes `"..."`, zero photo-credit `<p>` tags).
     - Execute `.agents/skills/kevanger/scripts/process_news_image.py --mode master` to build the master asset and native `image_alt` / `image_credit` payload.
   - **Phase 4 — Verification Gate & Draft-Only Staging (`kevrast --explain` + `verify_news_pipeline.sh` + `mcp_cli.py`)**:
     - Execute `tools/verify_news_pipeline.sh` and `kevrast` Stage 2 `--explain`.
     - Unless `dry_run` is `true`, stage exclusively via `python3 tools/mcp_cli.py call create_news --args-file ...`, enforcing `status == "n"` and `username == "habermerkezi"` in Python before invoking `mcp_cli.py`.
     - Return a structured JSON summary so the parent coordinator presents the result in Kurmanji Kurdish (`AGENTS.md` Rule 7).

### 3.2 Workflow 2.2 — Parallel Quality Audit Fan-Out (`tools/workflows/quality_audit_fanout.py`)

Create `tools/workflows/quality_audit_fanout.py` (+ `quality_audit_fanout.workflow.json` with optional `paths` array and `audit_mode` enum `["diff", "module"]`):

1. **Phase 1 — Deterministic CLI Guards (Fast Local Run)**:
   - Collect touched files from `git status --porcelain` (or explicit `args["paths"]`).
   - Run `php -l` on touched `.php` files, `python3 tools/module_boundary_guard.py`, `python3 tools/code_separation_guard.py`, `python3 tools/i18n_guard.py`, and `python3 tools/min_css_guard.py`.
2. **Phase 2 — Parallel Module & Surface Audits (`parallel()`)**:
   - Group touched files by domain:
     - `mod/<module>/...` -> read-only agent checking `module-modernization-check` (`BaseModuleController`, `ImageService`, `DataTableService`, SQLite compatibility, English code/comments).
     - `templates/admin/...` -> read-only agent checking `admin-mobile-check` (phone-only viewport contract, bottom-sheet bounds, 44px+ touch targets).
     - `templates/<theme>/...` -> read-only agent checking `frontend-theme` / `taste-skill` and strict code separation.
   - Pass `model="flash"` on simple read-only checklist fan-out workers unless overridden by the user, reducing latency and quota consumption.
3. **Phase 3 — Policy Gate Consolidation**:
   - Aggregate Phase 1 tool results and Phase 2 `parallel()` JSON verdicts into a single `{"status": "PASSED"|"BLOCKED", "errors": [...], "warnings": [...]}` payload for `task-validation`.

### 3.3 Skill Entrypoint Wiring

- Add a short `## Automated Workflow Runner` pointer (3–5 lines, keeping skills short per `AGENTS.md` Rule 8) to:
  - `.agents/skills/kevanger/SKILL.md` referencing `tools/workflows/editorial_news_pipeline.py`
  - `.agents/skills/task-validation/SKILL.md` referencing `tools/workflows/quality_audit_fanout.py`

### 3.4 Acceptance Criteria

- Both Python scripts pass `python3 -m py_compile` and valid JSON schema checks on their `.workflow.json` manifests.
- Running `editorial_news_pipeline.py` with an intentionally stale seed URL halts in Phase 1 (`kevtemen` Stage 0) without spawning Phase 2/3 LLM agents.
- Running `quality_audit_fanout.py` on a clean or scoped diff runs all Phase 1 deterministic guards and aggregates module/admin checks cleanly.

---

## 4. Work Package 3 (P1): `.agents/` Repository Hygiene & Native `/swarm` Standard

### 4.1 Evidence

Git tracking inspection (`git ls-files .agents | cut -d/ -f1-2 | sort -u`) shows that over 40 legacy temporary multi-agent execution directories from historical runs are currently committed inside `.agents/`:
- `auditor_m1/`, `auditor_m2/`
- `challenger_m1_1/`, `challenger_m1_2/`, `challenger_m2_1/`, `challenger_m2_2/`
- `explorer_survey_1/`, `explorer_survey_2/`, `explorer_survey_3/`
- `m1_auditor/`, `m1_challenger_1/`, `m1_challenger_2/`, `m1_explorer_1/`, `m1_explorer_2/`, `m1_explorer_3/`, `m1_reviewer_1/`, `m1_reviewer_2/`, `m2_auditor/`
- `orchestrator_1/`, `orchestrator_2/`, `orchestrator_3/`
- `reviewer_m1_1/`, `reviewer_m1_2/`, `reviewer_m2_1/`, `reviewer_m2_2/`
- `sentinel_1/`, `sentinel_2/`, `sentinel_3/`
- `swe_1/`, `swe_2/`, `swe_3/`
- `teamwork/`, `teamwork_preview_implementer_1/`, `teamwork_preview_implementer_r0/`, `teamwork_preview_reviewer_1/`, `teamwork_preview_reviewer_2/`, `teamwork_preview_reviewer_3/`, `teamwork_preview_reviewer_r1/`, `teamwork_preview_reviewer_r2/`, `teamwork_preview_reviewer_r3/`, `teamwork_preview_victory_auditor/`
- `test_writer_1/`, `test_writer_e2e/`
- `victory_auditor_1/` .. `victory_auditor_6/`, `victory_auditor_swe_1/`
- `.agents/ORIGINAL_REQUEST.md`
(Meanwhile `.agents/worker_m1/`, `.agents/worker_m1_fix/`, and `.agents/worker_m2/` remain untracked/ignored on disk via `.gitignore:43` `.agents/worker_*/`.)

### 4.2 Canonical Tree vs Ephemeral Cleanup

1. **Preserve Canonical Configuration & Knowledge Assets (Untouched)**:
   - `.agents/INDEX.md`, `.agents/README.md`
   - `.agents/design/`, `.agents/plugins/`, `.agents/plugins.json`, `.agents/quality/`, `.agents/skills/`, `.agents/skills.json`, `.agents/theme/`
2. **Archive/Remove Ephemeral Run Logs**:
   - Remove the legacy temporary run directories and `ORIGINAL_REQUEST.md` from version control via `git rm -r` (leaving a clean historical commit in Git log if ever needed for archaeology).
3. **Update `.gitignore`**:
   - Expand line 43 of `.gitignore` from only `.agents/worker_*/` to cover all ephemeral orchestration patterns:
     `.agents/worker_*/`, `.agents/teamwork/`, `.agents/teamwork_preview_*/`, `.agents/orchestrator_*/`, `.agents/auditor_*/`, `.agents/challenger_*/`, `.agents/explorer_survey_*/`, `.agents/m1_*/`, `.agents/m2_*/`, `.agents/reviewer_*/`, `.agents/sentinel_*/`, `.agents/swe_*/`, `.agents/test_writer*/`, `.agents/victory_auditor_*/`.
4. **Codify Antigravity `v2.31.0+` Native `/swarm` Standard**:
   - Document in `.agents/README.md` that parallel multi-file engineering tasks (e.g., building a new public theme alongside admin settings wiring or refactoring independent `mod_*` modules) should use Antigravity's native **`/swarm`**:
     - Assign concurrent file authors with `workspace: "share"` so each worker operates in an isolated working copy without file collisions or repository pollution.
     - Assign read-only `verifier` workers with `workspace: "inherit"` (1 verifier per 2–4 authors) to run `php -l`, guards, and tests.
     - Merge/cherry-pick verified branch changes back into the primary workspace before calling `swarm{action: "kill"}`.

### 4.3 Acceptance Criteria

- `git ls-files .agents | cut -d/ -f1-2 | sort -u` returns exclusively the canonical configuration/skill/quality/theme/design/plugin directories and `INDEX.md` / `README.md`.
- `.gitignore` prevents accidental re-tracking of scratch agent run folders.

---

## 5. Work Package 4 (P2): `Orchestrator` Multi-Model Protocol & Rule-Budget Discipline

With the release of **Orchestrator (`Gemini 4 Carbon` + `Gemini 4 Hydrogen`)** on November 12, 2026, planning and execution split dynamically within a single conversation trajectory (~24% lower quota usage and ~19% faster completion on benchmarks while preserving Carbon-level planning).

### 5.1 Repository Alignment Changes

1. **Protect Antigravity's Context Rule Budget**:
   - Antigravity enforces a **24 KB (`24,000` bytes)** per-file cap and a **20,000-token** aggregate budget for always-on `AGENTS.md` / `GEMINI.md` rules; files exceeding budget risk truncation or demotion to on-demand pointers.
   - Current baseline in `AF/Bulletin` is lean (`AGENTS.md`: `2,744` bytes; `.agents/INDEX.md`: `7,978` bytes; `.agents/README.md`: `2,727` bytes). Add an explicit budget policy note in `.agents/README.md` prohibiting sprawling inline documentation inside root `AGENTS.md` and plugin `rules/AGENTS.md` files (keep detailed specs in `docs/`).
2. **Standardize `"Re-plan if:"` Handback Triggers for `AF/Bulletin`**:
   - Add an `Orchestrator Execution & Re-Plan Triggers` section to `.agents/README.md` (and cross-reference in `docs/PILOT_AGENT_REVIEW_FLOW.md`) defining when the execution model (`Hydrogen`) must immediately invoke `enter_plan_mode` rather than improvising:
     - A proposed change requires modifying shared-core classes under `lib/` that affect multiple modules or sibling-sync surfaces.
     - A task requires adding or altering SQL schema tables/columns under `install/` or runtime migrations.
     - A localization change adds new UI keys without an established target schema across all 11 repository language packs (`ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`).
     - A verification guard (`php -l`, `code_separation_guard.py`, `module_boundary_guard.py`, `i18n_guard.py`, or Tier 1 Playwright/E2E) fails twice on the same step.
3. **Orchestration Mode Decision Matrix (documented in `.agents/README.md`)**:
   - **Single-Session `Orchestrator` (`Carbon` + `Hydrogen`)**: Default for everyday feature development, bug fixes, single-module refactoring, and admin/frontend styling.
   - **Dynamic Workflows (`run_workflow`)**: Default for repeatable, deterministic multi-stage pipelines (`editorial_news_pipeline.py`) or multi-module compliance fan-outs (`quality_audit_fanout.py`).
   - **Native `/swarm`**: Reserved for explicit user-requested large parallel work across disjoint files/modules (`workspace: "share"` + `verifier`).

---

## 6. Work Package 5 (P2): Host Security Settings, Browser Allowlist & Scheduled Automation Runbook

Because Antigravity App `v2.30.0–v2.32.0` introduced per-project permission overrides, stronger built-in browser isolation, and `/schedule` / `/automation` background tasks, these host-side controls should be documented in a dedicated operational section (e.g., `docs/ANTIGRAVITY_SETUP.md` or `.agents/README.md`) for the project owner:

1. **Project-Level Sandbox & Cross-Repo Lock (`Settings > Projects > AF/Bulletin`)**:
   - Set **Non-Workspace File Access** to **`ask`** (or **`deny`**): hardens `AGENTS.md` Rule 3 (*Zero Autonomous Cross-Repo Sync*) at the host permission boundary so no agent can read or modify `AF/Business` or outside directories without explicit interactive approval.
   - Keep **Terminal Sandbox** enabled on Linux where supported, and add destructive operations (`git push --force`, `tools/deploy_production.sh`, `rm -rf`) to the **Command Denylist / Ask list**.
2. **Built-in Browser Security & Phone-Only Admin Testing (`v2.31.0–v2.32.0`)**:
   - Add local development URLs (`http://localhost:8080`, `http://127.0.0.1:8080`) and the production bridge host (`https://amedbulten.com`) to the **Browser Allowlist**.
   - Retain `v2.32.0` default download approval prompts and signed-out cross-site frame isolation when previewing news articles or testing `templates/admin/` inside `tools/mobile_simulator.html` (`390x844` mobile viewport per `AGENTS.md` Rule 5).
   - Monitor the installed plugin card header (`v2.32.0` MCP startup diagnostics) to spot `amed-bulletin` PHP/stdio bridge connection errors early.
3. **Optional Read-Only Scheduled Automation (`/schedule` / `/automation`)**:
   - Configure an optional recurring morning read-only automation (e.g., `0 8 * * *`, `Europe/Istanbul`) running:
     - `python3 tools/mcp_health_check.py --preflight`
     - `python3 .agents/skills/kevkolin/scripts/source_catalog.py --scan-today --category amed_diyarbakir`
   - Output constraint: strictly read-only summary in Kurmanji Kurdish of candidate same-day Diyarbakır (Amed) developments and MCP health status — never auto-creating or auto-publishing articles without user instruction.

---

## 7. Integrated Architecture & Implementation Sequence for `v1.0.72`

### 7.1 Enforcement & Orchestration Flow

```mermaid
flowchart TD
    USER["User Request (Kurmanji Dialogue)"] --> ORCH["Orchestrator Planning Model (Carbon)"]
    ORCH --> MODE{"Task Shape"}
    MODE -->|Single-session feature or fix| EXEC["Execution Model (Hydrogen)"]
    MODE -->|Repeatable pipeline or audit| WF["run_workflow Python Script"]
    MODE -->|Large parallel work| SWARM["Native /swarm (workspace: share)"]

    EXEC --> PRE["PreToolUse Hook (pre_tool_guard.py)"]
    WF --> PRE
    SWARM --> PRE

    PRE -->|Forbidden module, inline CSS/JS, or cross-repo write| DENY["Block Tool Call"]
    DENY --> ORCH
    PRE -->|Allowed| TOOL["write_file / edit_file / run_command"]
    TOOL --> POST["PostToolUse Hook (php -l + guards)"]
    POST --> STOP["Stop Hook (stop_quality_gate.py)"]

    STOP -->|terminationReason == NO_TOOL_CALL and !continuationActive| GATE{"Violations Found?"}
    GATE -->|Yes (single bounded retry)| EXEC
    GATE -->|No (or USER_CANCELED)| DONE["Complete & Sync VERSION.md + site.config.php"]
```

### 7.2 Ordered Execution Checklist for `v1.0.72` (WP1) and `v1.0.73` (WP2–WP5)

Execution is phased cleanly across releases:

1. **Step 1 (`WP1` — Delivered in `v1.0.72`)**: Updated `.agents/plugins/af-cms-synchronizer/hooks.json`, `plugin.json`, `assets/logo.svg`, added shared `scripts/hook_utils.py`, modernized `scripts/pre_tool_guard.py`, `scripts/post_tool_verifier.py`, `scripts/stop_quality_gate.py`, normalized `.agents/skills.json` & `.agents/plugins.json`, and verified with `tests/test_agent_hooks_v2.py` and `agy plugin validate --strict`.
2. **Step 2 (`WP2` — Deferred to `v1.0.73`)**: Implement `tools/workflows/editorial_news_pipeline.py` (+ `.workflow.json`) and `tools/workflows/quality_audit_fanout.py` (+ `.workflow.json`), add short pointers in `kevanger/SKILL.md` and `task-validation/SKILL.md`, and verify syntax plus dry-run preflight gate behavior.
3. **Step 3 (`WP3` — Deferred to `v1.0.73`)**: Remove tracked ephemeral run directories under `.agents/` via `git rm -r`, update `.gitignore`, and document native `/swarm` (`workspace: "share"` + `verifier`) rules in `.agents/README.md`.
4. **Step 4 (`WP4` & `WP5` — Deferred to `v1.0.73`)**: Update `.agents/README.md` and `docs/PILOT_AGENT_REVIEW_FLOW.md` with `Orchestrator` handback triggers and context budget limits; apply the host Project Settings checklist in Antigravity App `v2.32.0`.
5. **Step 5 (`v1.0.72` Quality & Version Parity Gates — Executed)**:
   - Run `python3 tools/module_boundary_guard.py`
   - Run `python3 tools/code_separation_guard.py`
   - Run `python3 tools/i18n_guard.py`
   - Run `python3 tools/min_css_guard.py`
   - Verify `AGENTS.md` and `.agents/` files remain under Antigravity's `24,000`-byte per-file rule cap.
   - Update `docs/VERSION.md` (`## [v1.0.72] - 2026-11-14`) and synchronize `$site['version'] = '1.0.72';` in `config/site.config.php:62` per `.agents/skills/github-release/SKILL.md`.
