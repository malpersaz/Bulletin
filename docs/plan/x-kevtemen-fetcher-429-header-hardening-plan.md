# X — Kevtemen Fetcher 429 Header Hardening Plan

**Status:** Implemented
**Version:** Unassigned (tooling hotfix; version numbering deferred to release planning)
**Date:** 2026-11-10
**Scope:** `.agents/skills/kevtemen/scripts/check_freshness.py` (`fetch_html`)

## 1. Issue

While running the kevanger editorial pipeline for the Gemini 4 Carbon same-day
story, the kevtemen same-day freshness gate hard-blocked a verified same-day
primary source:

```
Status: BLOCKED | Today: 10.11.2026
  [!] https://venturebeat.com/technology/google-gemini-4-carbon-is-now-publicly-available-...
      -> Target unreachable (HTTP Error 429: Too Many Requests)
```

The block was reproducible across three attempts (initial + 20s and 75s
backoffs). The same URL passed kevrast Stage 1 (R1 liveness, HTTP 200) in the
same pipeline run, minutes apart.

## 2. Evidence (A/B reproduction, 2026-11-10)

Direct curl against the same VentureBeat URL:

| Fetch header set | Used by | Result |
|---|---|---|
| `User-Agent` only | kevtemen `fetch_html` | HTTP 429 |
| `User-Agent` + `Accept: text/html,application/xhtml+xml,*/*;q=0.8` + `Accept-Language: tr,en;q=0.8` | kevrast `fetch_page` | HTTP 200 |

VentureBeat edge throttling rejects bare-UA requests; the richer header set is
served normally. Same User-Agent string in both scripts.

## 3. Root Cause

`check_freshness.py::fetch_html()` sends only `User-Agent`, while
`verify_facts_and_links.py::fetch_page()` (kevrast) sends `Accept` and
`Accept-Language` as well. The two pipeline gates therefore disagree on the
same source: kevtemen fails closed on a transport-layer artifact (429) and
blocks a genuinely same-day, live source that kevrast verifies. The sentinel
itself is working as designed (fail-closed); its fetcher is under-headered
relative to its sibling gate.

Secondary gaps observed in the same fetcher (not fixed here, tracked as
follow-ups): no response cache (kevrast caches by normalized URL), no
retry/`Retry-After` handling for transient 429/503.

## 4. Fix (applied)

`fetch_html()` now mirrors the kevrast header set exactly:

```python
req = urllib.request.Request(url, headers={
    "User-Agent": USER_AGENT,
    "Accept": "text/html,application/xhtml+xml,*/*;q=0.8",
    "Accept-Language": "tr,en;q=0.8",
})
```

No behavioral change to date extraction, role tagging, or the fail-closed
policy. The gate remains fail-closed; only the transport header set changed.

## 5. Acceptance

1. `check_freshness.py --dossier /tmp/dossier_gemini4carbon.json` returns
   `PASSED (Same-Day)` with the VentureBeat primary source resolved to
   10.11.2026 via live fetch (verified below in the pipeline run of this date).
2. Header parity between kevtemen `fetch_html` and kevrast `fetch_page`
   (diff-level).
3. No other call sites of `fetch_html` (single internal consumer).

## 6. Follow-ups (not in this hotfix)

- Add `Retry-After`-aware retry (max 2) for 429/503 in both gates.
- Consider a shared fetch helper module for kevtemen/kevrast to prevent header
  drift recurring.

## 7. Addendum — mod_faq staging friction (2026-11-10, later task)

While attaching 6 FAQ items (IDs 28-33) to the staged news draft #128 via the
`amed-bulletin` MCP, the following `mod_faq` tool issues were observed.

### 7.1 `list_faqs` drops the `vid` filter when combined (bug)

A/B evidence from live calls:

- `{"vid": 128}` alone -> correct: 6 items, total 6 (only vid 128).
- `{"mod_name": "mod_news", "vid": 128, "lang": "tr", "limit": 50}` -> `vid`
  predicate silently dropped: 18 items returned, spanning host records
  vid 5, 10, 24, 81, 82 and 128 (all mod_news / tr).

Impact: callers using combined filters silently receive a listing that is not
filtered by host record. Workaround used here: query with `vid` alone.
Suggested fix: build the `mod_faq` list WHERE clause so every supplied filter
is applied conjunctively, and add a regression test for combined
`mod_name`+`vid`+`lang` filtering.

### 7.2 Discoverability and batching gaps (UX, no bug)

- `create_faq` is single-item: staging 6 questions cost 6 MCP round-trips.
  Atomic batch precedent exists (`batch_update_menu_items`).
- No `list_faq_modules` counterpart to `list_gallery_modules`; valid
  `mod_faq_mod` host identifiers are only discoverable from doc examples.
- `list_faqs` omits the answer body by design; verifying stored answers
  requires one `get_faq` call per item.

---

**Signature:** `omp/glm-5.3/2026-11-10/x-kevtemen-fetcher-429-header-hardening-plan`
**Addendum signature:** `omp/glm-5.3/2026-11-10/x-kevtemen-fetcher-429-header-hardening-plan#faq-addendum`
