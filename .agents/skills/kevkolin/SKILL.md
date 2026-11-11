---
name: kevkolin
description: Curate, investigate, cross-reference, and compile multi-source research dossiers for Diyarbakır (Amed), regional, national, and international news events. Serves as the upstream research engine feeding Kevrast (Stage 1) and Kevanger.
---

# Kevkolin Investigation & Research Engine

## Operating Rule

Keep this skill as a short ordered execution checklist. Follow multi-source investigation protocols strictly and output a structured Research Dossier for downstream validation and Turkish journalistic drafting.
- **Upstream Gate**: Only initiate research after Stage 0 `kevtemen` passes (or via Near-Midnight Recovery Protocol).
- **Downstream Hand-off**: Always validate the completed dossier via Stage 2 `kevtemen` before triggering `kevrast` Stage 1.

## Sequence

### 1. Topic Ingestion & Content Classification
- Identify the news or cultural topic from user instruction or autonomous scan.
- Determine content classification:
  - `type: "news"` (default): breaking or daily reporting requiring Europe/Istanbul same-day freshness.
  - `type: "evergreen"`: historical, cultural, civic, or analytical guides without a breaking-news peg. Requires `evergreen_rationale` (minimum 20 characters).
- Initialize or scaffold the dossier template:
  ```bash
  python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --init-dossier /tmp/dossier_<slug>.json
  # or generate template from source catalog:
  python3 .agents/skills/kevkolin/scripts/source_catalog.py --new-dossier "<Topic Title>" > /tmp/dossier_<slug>.json
  ```

### 2. Multi-Source Investigation against Authoritative Registry & Local Radar
Search and cross-reference verified outlets using `.agents/skills/kevkolin/scripts/source_catalog.py`:

#### Local News Radar (`--scan-today`) & Near-Midnight Recovery
Scan live feeds of verified outlets to discover same-day primary stories or follow-ups:
```bash
python3 .agents/skills/kevkolin/scripts/source_catalog.py --scan-today [--category amed_diyarbakir] [--query "topic"] [--json]
```
- Fetches live RSS, Atom, and Google News sitemaps of verified outlets (e.g. Amida Haber, Tigris Haber, Güneydoğu Ekspres, CEGA Medya, Diyarbakır BB) via shared `editorial_http.py`.
- Filters entries strictly to today's date in `Europe/Istanbul` (+03:00).
- **Near-Midnight Recovery Protocol**: When dispatched to investigate a near-midnight yesterday lead from Stage 0, execute `--scan-today --category amed_diyarbakir` to find today's primary follow-up reports. If found, mark the today-dated article as `role: "primary"` and yesterday's seed as `role: "supplementary"`. If none exist, halt.

1. **Diyarbakır (Amed) Local Media & Institutional Registry (`amed_diyarbakir`)**:
   - **Local News Portals & Newspapers**:
     - Amida Haber (`amidahaber.com`)
     - Tigris Haber (`tigrishaber.com`)
     - Güneydoğu Ekspres (`guneydoguekspres.com`)
     - Diyarbakır Yenigün (`diyarbakiryenigun.com`)
     - Mücadele Gazetesi (`mucadelegazetesi.com.tr`)
     - CEGA Medya (`cegamedya.com`)
     - Amed Times (`amedtimes.com.tr`)
     - Diyarbakır Söz (`diyarbakirsoz.com`)
     - Özgür Haber Gazetesi (`ozgurhabergazetesi.com`)
     - Güneydoğu Güncel (`guneydoguguncel.com`)
     - Amed Haber (`amedhaber.net`), Amed Radyo TV (`amedradyotv.com`)
   - **Civic, Municipal, Economic, Academic & Sports Portals**:
     - Diyarbakır Büyükşehir Belediyesi (`diyarbakir.bel.tr`)
     - Diyarbakır Ticaret ve Sanayi Odası - DTSO (`dtso.org.tr`)
     - Diyarbakır Ticaret Borsası - DTB (`diyarbakirtb.org.tr`)
     - Diyarbakır Barosu (`diyarbakirbarosu.org.tr`)
     - Karacadağ Kalkınma Ajansı (`karacadag.gov.tr`)
     - Dicle Üniversitesi (`dicle.edu.tr`)
     - Amedspor Official Portal (`amedspor.com.tr`)
2. **Regional & Middle East Media (`regional_and_middle_east`)**:
   - Rûdaw Türkçe (`rudaw.net/turkish`, `rudaw.net/english`)
   - Kurdistan24 Türkçe (`kurdistan24.net/tr`, `kurdistan24.net/en`)
   - 964media (`964media.com`), Shafaq News (`shafaq.com`)
   - Bianet (`bianet.org`), Mezopotamya Ajansı (`mezopotamyaajansi44.com`)
   - Official KRG Portals (`gov.krd`, `presidency.gov.krd`)
3. **International & National Sources by Quotas**:
   - **Turkey (3)**: Anadolu Ajansı (AA), T24, Gazete Duvar.
   - **United States (10)**: NYT, Washington Post, WSJ, AP, Bloomberg, Foreign Policy, Foreign Affairs, Al-Monitor, Politico US, CNN.
   - **Continental Europe (11)**: DW, Der Spiegel, AFP, Le Monde, France 24, Euronews, Politico Europe, SWI swissinfo, SVT, El País, NOS.
   - **United Kingdom (3)**: BBC News, Reuters, The Guardian.
   - **Israel (5)**: The Jerusalem Post, Haaretz, The Times of Israel, Ynetnews, i24NEWS.
   - **Russia (3)**: TASS, RIA Novosti, Kommersant.
   - **Ukraine (3)**: Ukrinform, The Kyiv Independent, Ukrainska Pravda.
   - **Iran (1)**: IRNA.

### 3. Contradiction & Variance Detection
- Cross-reference figures, dates, casualties, and official statements across editions.
- If verified outlets report conflicting data:
  - Document the conflict explicitly in the variance log:
    `Source A reports [X], whereas Source B reports [Y].`
  - Never select one arbitrary figure over another.

### 4. Compile Structured Research Dossier
Compile the verified findings into a standardized Research Dossier conforming to `dossier.schema.json`:
1. **Event Overview**: What happened, who was involved, when, and where.
2. **Verified Key Facts**:
   - Exact numbers, quantities, metrics, and units.
   - Exact dates, years, times, and timezones.
   - Verbatim official statements and quotes.
3. **Source Evidence & Live Links (Role-Tagged JSON Dossier Contract)**:
   - Every claim MUST carry a deep content URL that was actually fetched and read during research — URLs recalled from memory are strictly prohibited.
   - Persist the dossier as a JSON file (e.g. `/tmp/dossier_<slug>.json`). This file is the single source of truth for URLs in the pipeline; `kevtemen`, `kevrast`, and `kevanger` consume it via `--dossier`.
   - Each source entry carries a role tag:
     - `primary`: the same-day source(s) the news exists for (kevtemen same-day target).
     - `supplementary`: additional verification or data for the same event (polls, research, official pages; any date).
     - `historical`: past-event attribution ("X previously said ..."; any date).
   - Dossier schema:
     ```json
     {
       "topic": "...",
       "date": "2026-11-10",
       "type": "news",
       "sources": [
         {"url": "https://...", "outlet": "BBC", "role": "primary", "pub_date": "2026-11-10", "claims": ["..."]},
         {"url": "https://...", "outlet": "Gallup", "role": "supplementary", "claims": ["..."]}
       ],
       "contradictions": []
     }
     ```
4. **Offline Schema Validation**:
   ```bash
   python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --validate-dossier /tmp/dossier_<slug>.json
   ```
5. **Cross-Language Source Anchoring**:
   For foreign-language or non-Turkish sources (e.g. English, Kurdish), declare `"source_lang": "en"` and `"source_anchors": ["...", "..."]` inside the source entry in `dossier.schema.json`. `kevrast` uses these anchors to evaluate cross-language numeric and entity equivalence in Turkish drafts.

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
