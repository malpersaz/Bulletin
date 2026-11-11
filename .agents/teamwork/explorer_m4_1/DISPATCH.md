## 2026-11-11T12:24:50Z

You are Explorer 1 for Milestone 4 (Radar, SEO Alignment, Skill Docs & Rule Fixes).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m4_1/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin

MANDATORY FIRST STEP:
Read ORIGINAL_REQUEST.md at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Also read:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m4/SCOPE.md
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
and docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md (§B6, §A1).

Your specific investigation area:
Focus on `.agents/skills/kevkolin/scripts/source_catalog.py`:
1. Inspect the existing `source_catalog.py` implementation, data structures, and CLI arguments.
2. Inspect how RSS/Atom/sitemap feeds should be configured in `VERIFIED_SOURCES` (especially `amed_diyarbakir` and `regional_and_middle_east`: `amidahaber.com`, `tigrishaber.com`, `guneydoguekspres.com`, `cegamedya.com`, `rudaw.net/turkish`, `bianet.org`, `aa.com.tr`, `diyarbakir.bel.tr`).
3. Design `--scan-today [--category amed_diyarbakir] [--query "park"] [--json]`:
   - Fetching live feeds via `.agents/skills/_shared/editorial_http.py`.
   - Filtering entries strictly to today's date in `Europe/Istanbul` (+03:00).
   - Parsing standard RSS `<pubDate>`, Atom `<updated>/<published>`, and sitemap `<lastmod>/<news:publication_date>`.
   - Output format and integration with `--init-dossier` and near-midnight recovery check.
4. Provide a concrete, ready-to-implement specification for Worker. Write your report to `handoff.md` in your state folder.
