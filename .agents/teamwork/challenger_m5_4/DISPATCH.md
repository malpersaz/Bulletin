# Dispatch for Challenger 4 (Milestone 5 Iteration 2)

## Working Directory
`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`

## State Folder
`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m5_4/`

## Mandatory Reading
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m5/SCOPE.md`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m5_2/handoff.md`

## Task
Empirically challenge the quality guards and full regression suite:
1. Run `python3 tools/i18n_guard.py --path mod/mod_news`
2. Run `python3 tools/code_separation_guard.py --path templates/amed/index/mod_news/news.twig`
3. Run `python3 tools/module_boundary_guard.py --all`
4. Run `python3 tools/min_css_guard.py`
5. Run `docker exec bulletin_app php tasks/mcp_smoke.php`
6. Run `python3 tests/e2e/run_e2e_tests.py`
7. Write structured handoff at `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m5_4/handoff.md` with explicit verdict: `APPROVE` or `REQUEST_CHANGES`.
Send completion message.
