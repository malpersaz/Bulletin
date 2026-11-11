# Dispatch: Explorer M2-1 (Shared HTTP Client)
Task: Investigate existing HTTP/SSRF logic and produce complete implementation specification for `.agents/skills/_shared/editorial_http.py`.
Milestone: M2 (Features 13, 14)
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_1/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
Original request: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Milestone scope: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
Project spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
Reference plan: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md
Reference survey: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_survey_pipeline_2/handoff.md
Existing SSRF reference: `.agents/skills/kevanger/scripts/process_news_image.py:70-115`
Output: handoff.md in /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_1/


## 2026-11-11T10:50:27Z
Sender: 5c7538a8-5eae-4af0-8169-9ca427353718
Priority: MESSAGE_PRIORITY_HIGH
Content: You are Explorer M2-1 (Shared HTTP Specialist) for Milestone 2.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_1/

MANDATORY: First read:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_1/DISPATCH.md
5. Relevant sections of docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md (§B4.7, §A4.1)

Task:
Investigate and design the complete blueprint for `.agents/skills/_shared/editorial_http.py` (Feature 13 & 14 integration).
Inspect existing SSRF and network logic in `.agents/skills/kevanger/scripts/process_news_image.py:70-115`, `.agents/skills/kevtemen/scripts/check_freshness.py:34-36, 219-223`, and `.agents/skills/kevrast/scripts/verify_facts_and_links.py:100-117`.

Requirements to specify in detail:
1. `validate_public_url(url: str) -> Tuple[bool, str]`:
   - URL parsing, protocol validation (http/https only).
   - Hostname resolution, blocking localhost (127.0.0.0/8, ::1), private IPv4 ranges (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 169.254.0.0/16, carrier-grade NAT 100.64.0.0/10), multicast, broadcast, loopback, IPv6 private/link-local/unique-local (fc00::/7, fe80::/10).
2. `SSRFSafeRedirectHandler`:
   - Hook into urllib.request redirects (301, 302, 303, 307, 308) to validate new location via `validate_public_url()` on every hop. Max redirect limit (e.g. 5).
3. Standard Browser Headers:
   - User-Agent, Accept: `text/html,application/xhtml+xml,*/*;q=0.8`, Accept-Language: `tr,en;q=0.8`.
   - Separate binary/image headers when appropriate.
4. Bounded `Retry-After` retry logic:
   - For HTTP 429, 502, 503, 504.
   - Max retries = 2.
   - Parse `Retry-After` header (integer seconds or HTTP-date RFC 7231). Cap sleep at 15s. Default fallback backoff (2s, 5s) if header absent.
5. Two-Tier Response Cache:
   - In-memory cache + disk cache at `/tmp/af_editorial_http_cache/`.
   - Key: sha256 of normalized URL.
   - TTL: 600s (10 min).
   - Handle concurrent writes safely.
6. Public API Functions:
   - `fetch_html(url: str, timeout: int = 15, max_bytes: int = 2_000_000) -> Tuple[int, str, Dict[str, str]]`
   - `fetch_binary(url: str, timeout: int = 20, max_bytes: int = 10_000_000) -> Tuple[int, bytes, Dict[str, str]]`
   - `validate_public_url(url: str) -> Tuple[bool, str]`

Write your comprehensive findings and implementation blueprint to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_1/handoff.md
Follow the Handoff Protocol (Observation, Logic Chain, Caveats, Conclusion, Verification Method).
When done, message me with your status.
