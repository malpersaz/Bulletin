# Handoff Report: Explorer M4 1 — Kevkolin News Radar & Source Catalog Specification

## 1. Observation

### 1.1 Existing `source_catalog.py` Implementation
- **File**: `.agents/skills/kevkolin/scripts/source_catalog.py` (196 lines).
- **Line 14–127 (`VERIFIED_SOURCES`)**:
  - `amed_diyarbakir` (`lines 15–38`): Dictionary with `"quota": 19` and `"sources": [ {"name": "...", "url": "...", "type": "..."} ]`. Currently contains 19 local media and institutional entries (e.g., Amida Haber, Tigris Haber, Güneydoğu Ekspres, Diyarbakır Yenigün, Mücadele Gazetesi, CEGA Medya, Amed Times, Diyarbakır Söz, Özgür Haber Gazetesi, Güneydoğu Güncel, Amed Haber, Diyarbakır Büyükşehir Belediyesi, etc.).
  - `regional_and_middle_east` (`lines 39–47`): List of dictionaries containing `name` and `urls` list (e.g., Rûdaw Türkçe, Kurdistan24 Türkçe, 964media, Shafaq News, Bianet, Mezopotamya Ajansı, Official KRG Portals).
  - Other geographic entries (`turkey`, `united_states`, `continental_europe`, etc.) follow the dictionary format with quotas and `url` strings.
- **Line 129–153 (`create_dossier_template`)**:
  - Generates a basic dictionary skeleton for `--new-dossier`.
- **Line 155–194 (`main`)**:
  - Currently handles only `--list-sources`, `--category`, `--json`, `--new-dossier <topic>`, and `--date <date>`.
  - No network interaction, no feed discovery, no RSS/sitemap parsing, and no live article search exists.

### 1.2 Live Feed Availability Verification (Tested via `editorial_http.py`)
Using `.agents/skills/_shared/editorial_http.py`, direct network probes were executed against key regional and local outlets. The following live endpoints were verified:

| Outlet | Domain | Verified Live Endpoint | Format | Status | Notes |
| :--- | :--- | :--- | :--- | :---: | :--- |
| **Amida Haber** | `amidahaber.com` | `https://amidahaber.com/rss` | RSS 2.0 | `200` | 40 items, RFC 2822 `<pubDate>` |
| **Tigris Haber** | `tigrishaber.com` | `https://www.tigrishaber.com/rss` | RSS 2.0 | `200` | 40 items, RFC 2822 `<pubDate>` |
| **Güneydoğu Ekspres** | `guneydoguekspres.com` | `https://www.guneydoguekspres.com/rss` | RSS 2.0 | `200` | 40 items, RFC 2822 `<pubDate>` |
| **CEGA Medya** | `cegamedya.com` | `https://www.cegamedya.com/rss` | RSS 2.0 | `200` | 40 items, RFC 2822 `<pubDate>` |
| **Diyarbakır Yenigün**| `diyarbakiryenigun.com`| `https://diyarbakiryenigun.com/rss` | RSS 2.0 | `200` | RSS feed active |
| **Mücadele Gazetesi** | `mucadelegazetesi.com.tr` | `https://www.mucadelegazetesi.com.tr/rss` | RSS 2.0 | `200` | RSS feed active |
| **Diyarbakır Söz** | `diyarbakirsoz.com` | `https://diyarbakirsoz.com/rss` | RSS 2.0 | `200` | RSS feed active |
| **Güneydoğu Güncel** | `guneydoguguncel.com` | `https://www.guneydoguguncel.com/rss.xml` | RSS 2.0 | `200` | RSS feed active |
| **Amed Haber** | `amedhaber.net` | `https://www.amedhaber.net/rss` | RSS 2.0 | `200` | RSS feed active |
| **Diyarbakır BB** | `diyarbakir.bel.tr` | `https://diyarbakir.bel.tr/tr/sitemap.xml` | Sitemap XML | `200` | 1351 URLs with `<lastmod>` (ISO 8601) |
| **Bianet** | `bianet.org` | `https://bianet.org/sitemap/haberler` | News Sitemap | `200` | `<news:publication_date>` (ISO 8601) & `<news:title>` |
| **Anadolu Ajansı (AA)**| `aa.com.tr` | `https://www.aa.com.tr/tr/rss/default?cat=guncel` | RSS 2.0 | `200` | 30 items, RFC 2822 `<pubDate>` |
| **Gazete Duvar** | `gazeteduvar.com.tr` | `https://www.gazeteduvar.com.tr/rss` | RSS 2.0 | `200` | RSS feed active |
| **Rûdaw** | `rudaw.net` | `https://rudaw.net/sitemap.xml` | Sitemap XML | `200` | 9025 Turkish URLs with `<lastmod>` (ISO 8601) |

### 1.3 Live Feed Scanning Probe
A prototype scanner tested against 6 active feeds on `2026-11-11` (`Europe/Istanbul`):
- Retrieved **158 articles published today** across Amida Haber (28), Tigris Haber (40), Güneydoğu Ekspres (20), CEGA Medya (6), Bianet (30), and Diyarbakır Belediyesi (34).
- When filtered with `--query "balık"`, matched 3 independent local reports covering the same local story published today.
- When filtered with `--query "park"`, returned 0 matches, confirming that the A1 near-midnight recovery check correctly blocks false positives when no today municipal follow-up exists.

---

## 2. Logic Chain

1. **A1 Incident Context**:
   - `rudaw.net/turkish/categories/turkey/1287295` was published at `2026-11-10 23:13:00 +03:00` (47 minutes before midnight).
   - `check_freshness.py` legitimately halted execution.
   - However, manual research overhead is excessive, and agents lacked a systematic mechanism to detect whether same-day (`11.11.2026`) local statements or follow-ups existed on local outlets.
2. **Feed Integration**:
   - Adding verified feed endpoints to `VERIFIED_SOURCES` in `source_catalog.py` allows rapid local scans using `editorial_http.fetch_html()` (with verified TLS, SSRF guard, and 10-minute caching).
3. **Strict Timezone Normalization**:
   - RSS feeds use RFC 2822 `<pubDate>` (`Wed, 11 Nov 2026 15:14:21 +0300`), parsed via `email.utils.parsedate_to_datetime()`.
   - Atom/sitemap feeds use ISO 8601 `<lastmod>` or `<news:publication_date>` (`2026-11-11T12:24:03.086Z`), parsed via `datetime.fromisoformat()`.
   - Both convert to `Europe/Istanbul` (+03:00). Comparing `dt.date() == target_date` ensures strict calendar-day compliance with zero timezone drift.
4. **Keyword Matching**:
   - Employing `tr_fold()` (handling Turkish dotted/dotless `İ/i` and `I/ı`) across `title`, `description`, and URL ensures reliable search across Turkish news content without false negative drops due to case sensitivity.
5. **Dossier Integration & A1 Near-Midnight Recovery**:
   - `--scan-today --init-dossier /tmp/dossier.json` scaffolds a validated research dossier conforming to `dossier.schema.json`.
   - When `--seed-url` is provided alongside a successful scan, the discovered today article is assigned `role: "primary"` and the overnight seed URL is demoted to `role: "supplementary"`.
   - If no today articles match, `--scan-today` with `--seed-url` reports recovery failure and exits with code `1`, maintaining the fail-closed editorial invariant.

---

## 3. Caveats

- `rudaw.net/sitemap.xml` is approximately 7.9 MB and contains over 9,000 URLs. While `editorial_http.fetch_html` can load it if `max_bytes` is raised, scanning `amed_diyarbakir` RSS feeds (which are each under 100 KB and respond within 200 ms) is significantly faster and lower-overhead for local news discovery.
- A small number of institutional portals (e.g. `dicle.edu.tr`, `dtso.org.tr`) do not expose standard RSS feeds; their entries will retain `url` without a `feed` key and be skipped by `--scan-today` without affecting other feeds.
- No caveats regarding syntax or schema validation: all prototype outputs validated against `dossier.schema.json`.

---

## 4. Conclusion & Implementation Specification for Worker

### 4.1 Update `VERIFIED_SOURCES` in `.agents/skills/kevkolin/scripts/source_catalog.py`
Add `"feed"` and `"feed_type"` to entries under `amed_diyarbakir`, `regional_and_middle_east`, and `turkey`:

```python
VERIFIED_SOURCES = {
    "amed_diyarbakir": {
        "quota": 19,
        "sources": [
            {"name": "Amida Haber", "url": "https://amidahaber.com", "type": "local_media", "feed": "https://amidahaber.com/rss", "feed_type": "rss"},
            {"name": "Tigris Haber", "url": "https://www.tigrishaber.com", "type": "local_media", "feed": "https://www.tigrishaber.com/rss", "feed_type": "rss"},
            {"name": "Güneydoğu Ekspres", "url": "https://www.guneydoguekspres.com", "type": "local_media", "feed": "https://www.guneydoguekspres.com/rss", "feed_type": "rss"},
            {"name": "Diyarbakır Yenigün", "url": "https://diyarbakiryenigun.com", "type": "local_media", "feed": "https://diyarbakiryenigun.com/rss", "feed_type": "rss"},
            {"name": "Mücadele Gazetesi", "url": "https://www.mucadelegazetesi.com.tr", "type": "local_media", "feed": "https://www.mucadelegazetesi.com.tr/rss", "feed_type": "rss"},
            {"name": "CEGA Medya", "url": "https://www.cegamedya.com", "type": "local_media", "feed": "https://www.cegamedya.com/rss", "feed_type": "rss"},
            {"name": "Amed Times", "url": "https://www.amedtimes.com.tr", "type": "local_media"},
            {"name": "Diyarbakır Söz", "url": "https://diyarbakirsoz.com", "type": "local_media", "feed": "https://diyarbakirsoz.com/rss", "feed_type": "rss"},
            {"name": "Özgür Haber Gazetesi", "url": "https://www.ozgurhabergazetesi.com", "type": "local_media"},
            {"name": "Güneydoğu Güncel", "url": "https://www.guneydoguguncel.com", "type": "local_media", "feed": "https://www.guneydoguguncel.com/rss.xml", "feed_type": "rss"},
            {"name": "Amed Haber", "url": "https://www.amedhaber.net", "type": "local_media", "feed": "https://www.amedhaber.net/rss", "feed_type": "rss"},
            {"name": "Amed Radyo TV", "url": "http://www.amedradyotv.com", "type": "local_media"},
            {"name": "Diyarbakır Büyükşehir Belediyesi", "url": "https://www.diyarbakir.bel.tr", "type": "institutional", "feed": "https://diyarbakir.bel.tr/tr/sitemap.xml", "feed_type": "sitemap"},
            {"name": "Diyarbakır Ticaret ve Sanayi Odası (DTSO)", "url": "https://www.dtso.org.tr", "type": "institutional"},
            {"name": "Diyarbakır Ticaret Borsası (DTB)", "url": "https://www.diyarbakirtb.org.tr", "type": "institutional"},
            {"name": "Diyarbakır Barosu", "url": "https://diyarbakirbarosu.org.tr", "type": "institutional"},
            {"name": "Karacadağ Kalkınma Ajansı", "url": "https://www.karacadag.gov.tr", "type": "institutional"},
            {"name": "Dicle Üniversitesi", "url": "https://www.dicle.edu.tr", "type": "institutional"},
            {"name": "Amedspor (Resmi)", "url": "https://www.amedspor.com.tr", "type": "sports"}
        ]
    },
    "regional_and_middle_east": [
        {"name": "Rûdaw Türkçe", "urls": ["https://rudaw.net/turkish", "https://rudaw.net/english"], "feed": "https://rudaw.net/sitemap.xml", "feed_type": "sitemap"},
        {"name": "Kurdistan24 Türkçe", "urls": ["https://www.kurdistan24.net/tr", "https://www.kurdistan24.net/en"]},
        {"name": "964media", "urls": ["https://964media.com"]},
        {"name": "Shafaq News", "urls": ["https://shafaq.com"]},
        {"name": "Bianet", "urls": ["https://bianet.org"], "feed": "https://bianet.org/sitemap/haberler", "feed_type": "sitemap"},
        {"name": "Mezopotamya Ajansı (MA)", "urls": ["https://mezopotamyaajansi44.com"]},
        {"name": "Official KRG Portals", "urls": ["https://gov.krd", "https://presidency.gov.krd"]}
    ],
    ...
    "turkey": {
        "quota": 3,
        "sources": [
            {"name": "Anadolu Ajansı (AA)", "url": "https://www.aa.com.tr", "feed": "https://www.aa.com.tr/tr/rss/default?cat=guncel", "feed_type": "rss"},
            {"name": "T24", "url": "https://t24.com.tr"},
            {"name": "Gazete Duvar", "url": "https://www.gazeteduvar.com.tr", "feed": "https://www.gazeteduvar.com.tr/rss", "feed_type": "rss"}
        ]
    },
```

### 4.2 Core Scanning & Parsing Implementation
Implement the following helper functions in `source_catalog.py`:

```python
import email.utils
import unicodedata
import xml.etree.ElementTree as ET
from datetime import datetime, timezone, timedelta
from zoneinfo import ZoneInfo
from pathlib import Path

# Resolve shared directory
_SHARED_DIR = Path(__file__).resolve().parents[2] / "_shared"
if str(_SHARED_DIR) not in sys.path:
    sys.path.insert(0, str(_SHARED_DIR))

try:
    import editorial_http
except ImportError:
    editorial_http = None

try:
    ISTANBUL = ZoneInfo("Europe/Istanbul")
except Exception:
    ISTANBUL = timezone(timedelta(hours=3))

def tr_fold(text: str) -> str:
    """Case-fold handling Turkish dotted/dotless I."""
    res = text.replace("İ", "i").replace("I", "ı")
    res = unicodedata.normalize("NFC", res).lower()
    return res.replace("\u0307", "")

def parse_entry_datetime(val: str) -> Optional[datetime]:
    """Parse feed date string (RFC 2822 or ISO 8601) and normalize to Europe/Istanbul."""
    if not val:
        return None
    val = val.strip()
    # 1. RFC 2822
    try:
        dt = email.utils.parsedate_to_datetime(val)
        if dt.tzinfo is None:
            dt = dt.replace(tzinfo=ISTANBUL)
        return dt.astimezone(ISTANBUL)
    except Exception:
        pass
    # 2. ISO 8601
    try:
        iso_val = val.replace("Z", "+00:00")
        dt = datetime.fromisoformat(iso_val)
        if dt.tzinfo is None:
            dt = dt.replace(tzinfo=ISTANBUL)
        return dt.astimezone(ISTANBUL)
    except Exception:
        pass
    return None

def scan_single_feed(source_name: str, feed_url: str, target_date: datetime.date, query: str = "") -> List[Dict[str, Any]]:
    """Fetch and parse feed items published on target_date, optionally filtered by query."""
    if not editorial_http:
        return []
    status, body, _ = editorial_http.fetch_html(
        feed_url,
        timeout=10,
        max_bytes=3_000_000,
        use_cache=True,
        headers={"Accept": "application/rss+xml, application/atom+xml, application/xml, text/xml, */*;q=0.8"}
    )
    if status != 200 or not body:
        return []

    cleaned = body.lstrip("\ufeff").strip()
    try:
        root = ET.fromstring(cleaned)
    except ET.ParseError:
        try:
            sanitized = re.sub(r"&(?!(amp|lt|gt|quot|apos|#\d+|#x[0-9a-fA-F]+);)", "&amp;", cleaned)
            root = ET.fromstring(sanitized)
        except Exception:
            return []

    items = []
    q_folded = tr_fold(query) if query else ""

    # RSS items
    for node in root.findall(".//item"):
        title = (node.findtext("title") or "").strip()
        link = (node.findtext("link") or "").strip()
        pub_raw = (node.findtext("pubDate") or "").strip()
        desc = (node.findtext("description") or "").strip()

        dt = parse_entry_datetime(pub_raw)
        if not dt or dt.date() != target_date:
            continue
        if q_folded and q_folded not in tr_fold(f"{title} {desc} {link}"):
            continue
        items.append({
            "outlet": source_name,
            "title": title,
            "url": link,
            "pub_date": pub_raw,
            "pub_date_iso": dt.isoformat(),
            "role": "primary"
        })

    # Atom entries
    for node in root.findall(".//{*}entry"):
        title = (node.findtext("{*}title") or "").strip()
        link_elem = node.find("{*}link")
        link = (link_elem.get("href") or link_elem.text or "").strip() if link_elem is not None else ""
        pub_raw = (node.findtext("{*}published") or node.findtext("{*}updated") or "").strip()
        summary = (node.findtext("{*}summary") or node.findtext("{*}content") or "").strip()

        dt = parse_entry_datetime(pub_raw)
        if not dt or dt.date() != target_date:
            continue
        if q_folded and q_folded not in tr_fold(f"{title} {summary} {link}"):
            continue
        items.append({
            "outlet": source_name,
            "title": title,
            "url": link,
            "pub_date": pub_raw,
            "pub_date_iso": dt.isoformat(),
            "role": "primary"
        })

    # Sitemap URLs
    for node in root.findall(".//{*}url"):
        loc = (node.findtext("{*}loc") or "").strip()
        lastmod = (node.findtext("{*}lastmod") or "").strip()
        news_node = node.find("{*}news")

        title = ""
        pub_raw = lastmod
        if news_node is not None:
            t = news_node.findtext("{*}title")
            d = news_node.findtext("{*}publication_date")
            if t: title = t.strip()
            if d: pub_raw = d.strip()
        elif lastmod:
            title = loc.rstrip("/").split("/")[-1].replace("-", " ").title()

        dt = parse_entry_datetime(pub_raw)
        if not dt or dt.date() != target_date:
            continue
        if q_folded and q_folded not in tr_fold(f"{title} {loc}"):
            continue
        items.append({
            "outlet": source_name,
            "title": title,
            "url": loc,
            "pub_date": pub_raw,
            "pub_date_iso": dt.isoformat(),
            "role": "primary"
        })

    return items
```

### 4.3 CLI Arguments & Handler Design
In `source_catalog.py`:
- Support arguments:
  - `--scan-today`: Scans verified feeds for same-day articles.
  - `--category`: Category filter (default: `amed_diyarbakir` if unspecified or empty).
  - `--query`, `-q`: Keyword/entity filter.
  - `--date`: Date in `YYYY-MM-DD` (defaults to today in `Europe/Istanbul`).
  - `--seed-url`: Overnight seed URL to be demoted to `role: "supplementary"`.
  - `--init-dossier PATH`: Path to write a structured research dossier skeleton.
  - `--topic`: Dossier topic title.
  - `--force`: Force overwrite existing file.
  - `--json`: Output in structured JSON format.

---

## 5. Verification Method

### 5.1 Independent Test Commands
1. **Compilation Check**:
   ```bash
   python3 -m py_compile .agents/skills/kevkolin/scripts/source_catalog.py
   ```
2. **List Sources Verification**:
   ```bash
   python3 .agents/skills/kevkolin/scripts/source_catalog.py --list-sources --category amed_diyarbakir
   ```
3. **Radar Scan Verification (Live `amed_diyarbakir`)**:
   ```bash
   python3 .agents/skills/kevkolin/scripts/source_catalog.py --scan-today --category amed_diyarbakir --json
   ```
   *Expected outcome*: Exits `0`, returns JSON with `total_items_found > 0` matching today's Istanbul date.
4. **Radar Scan with Query**:
   ```bash
   python3 .agents/skills/kevkolin/scripts/source_catalog.py --scan-today --query "balık" --json
   ```
   *Expected outcome*: Exits `0`, finds matching same-day articles from Amida/Tigris/Ekspres.
5. **Dossier Initialization & Near-Midnight Recovery Check**:
   ```bash
   python3 .agents/skills/kevkolin/scripts/source_catalog.py --scan-today --query "balık" \
     --seed-url "https://rudaw.net/turkish/categories/turkey/1287295" \
     --init-dossier /tmp/test_dossier.json --force
   python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --validate-dossier /tmp/test_dossier.json
   ```
   *Expected outcome*: `/tmp/test_dossier.json` contains 1+ `primary` sources and 1 `supplementary` source (`seed-url`), successfully passing schema validation (`PASSED: Dossier is valid`).
6. **Near-Midnight Recovery Failure Check (Negative Test)**:
   ```bash
   python3 .agents/skills/kevkolin/scripts/source_catalog.py --scan-today --query "nonexistent_term_xyz_123" \
     --seed-url "https://rudaw.net/turkish/categories/turkey/1287295"
   ```
   *Expected outcome*: Exits `1`, cleanly reporting that no same-day primary sources exist for the topic, enforcing the fail-closed halt.

### 5.2 Invalidation Conditions
- Any feed parsing failure that raises unhandled exceptions instead of skipping gracefully.
- Date comparisons using system-local or naive UTC time instead of `Europe/Istanbul`.
- Schema mismatch between generated dossier and `dossier.schema.json`.
