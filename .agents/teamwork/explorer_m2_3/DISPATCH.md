# Dispatch: Explorer M2-3 (Fact-Checking & Link Provenance Hardening)
Task: Investigate `.agents/skills/kevrast/scripts/verify_facts_and_links.py` and produce complete implementation specification for Features 19-23.
Milestone: M2 (Features 19, 20, 21, 22, 23)
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_3/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
Original request: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Milestone scope: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
Project spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
Reference plan: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md
Reference survey: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_survey_pipeline_2/handoff.md
Output: handoff.md in /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_3/


## 2026-11-11T10:50:44Z
Sender: 5c7538a8-5eae-4af0-8169-9ca427353718
Priority: MESSAGE_PRIORITY_HIGH

You are Explorer M2-3 (Fact-Checking Specialist) for Milestone 2.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_3/

MANDATORY: First read:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_3/DISPATCH.md
5. Relevant sections of docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md (§B4)

Task:
Investigate `.agents/skills/kevrast/scripts/verify_facts_and_links.py` and `.agents/skills/kevkolin/scripts/dossier.schema.json` and formulate the implementation blueprint for Features 19-23.

Requirements to specify in detail:
1. Feature 19: All-paragraph verification in `audit_draft()`:
   - Inspect `_find_citing_block(u, text)` vs all `<p>`, `<blockquote>`, `<li>` blocks.
   - Implement `_find_all_citing_blocks(url, text) -> List[Tuple[int, str]]`.
   - Ensure `audit_draft()` validates R3 claims in every citing block across the entire draft, establishing full parity between `--explain` output and exit code.
2. Feature 20: Clean number & entity extraction:
   - Strip non-content tags (`<script>`, `<style>`, `<noscript>`, `svg`, `<nav>`, `<footer>`, `<aside>`) from `target_html` before extracting `target_nums` and `target_plain`.
   - Eliminate substring matching `or n in t` in line 224! Require exact normalized token equality.
   - Support number normalization: percentages, periods/commas in thousands (`80.000`, `80,000`, `80 bin`), Turkish number words (`bir..on`, `yirmi..doksan`, `yüz`, `bin`, `milyon`, `milyar`).
3. Feature 21: Vendor single-segment allowlist in R2:
   - In `classify_precision()`, keep news/media rubrics unconditionally `FAIL/section_front`.
   - Define `SINGLE_SEGMENT_PRODUCT_SLUGS = {"download", "downloads", "releases", "changelog", "security", "pricing", "status", "docs", "manifesto", "about"}`.
   - Allow single-segment URLs ONLY when: (a) dossier role is `supplementary` (or product), AND (b) post-fetch inspection confirms `<article>` count <= 2 and page contains semantic version string (`\bv?\d+\.\d+(?:\.\d+)?\b`) or declared `source_anchors` token.
4. Feature 22: Cross-language anchoring:
   - In `dossier.schema.json`, support `source_lang` and `source_anchors` (`source_term` <-> `tr_term`).
   - In `KevrastAuditor.anchoring()`, implement two-sided verification: `source_term` MUST exist on fetched source HTML AND `tr_term` (stemmed via `tr_fold`) MUST appear in the Turkish citing paragraph.
5. Feature 23: Turkish morphology & expanded attribution:
   - Implement `tr_fold(text: str) -> str` handling Turkish `İ/i` and `I/ı`.
   - Implement institutional suffix stemmer (`belediyesine` -> `belediyesi`, `bakanlığına` -> `bakanlığı`, etc.).
   - Expand `CITATION_PHRASE_RE` / `OUTLET_ATTRIBUTION_RE` and separate bare speech verbs (`SPEECH_ATTRIBUTION_RE`).
   - Integrate `editorial_http.py` for network fetching and caching.

Write your comprehensive findings and implementation blueprint to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_3/handoff.md
Follow the Handoff Protocol (Observation, Logic Chain, Caveats, Conclusion, Verification Method).
When done, message me with your status.
