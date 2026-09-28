---
name: kevanger
description: Curate, verify, cross-reference, and draft news articles for Rojname's mod_news via MCP. Triggered whenever the user mentions "Kevanger" when requesting a news article.
---

# Kevanger News Drafting Skill

## Operating Rule

Keep this skill as a short ordered execution checklist. Follow all orthographic, verification, and MCP drafting rules strictly in order.

## Sequence

### 1. Pre-Flight Verification of MCP Connectivity
- Verify that `amidakurd.net/mcp.php` bridge is functional and authenticated.
- Ensure the MCP API key is present and active (via `amidakurd` MCP server).
- Perform a lightweight liveness check (e.g., call `list_news_categories` or `get_site_settings`).
- If connection fails or credentials are missing, report immediately and halt before drafting.

### 2. Multi-Source Investigation & Contradiction Detection
- Search and cross-reference verified Kurdish and regional media sources for the requested subject:
  - **Rûdaw**:
    - `https://rudaw.net/kurmanci`
    - `https://rudaw.net/english`
    - `https://rudaw.net/turkish`
    - `https://rudaw.net/sorani`
    - `https://www.rudawarabia.net/arabic`
  - **Kurdistan24**:
    - `https://www.kurdistan24.net/kmr`
    - `https://www.kurdistan24.net/ckb`
    - `https://www.kurdistan24.net/en`
    - `https://www.kurdistan24.net/tr`
    - `https://www.kurdistan24.net/ar`
  - **Dengê Amerîka (VOA Kurdî)**:
    - `https://www.dengiamerika.com`
  - **964media**:
    - `https://964media.com`
  - **NRT TV**:
    - `https://nrttv.com`
  - **Shafaq News**:
    - `https://shafaq.com`
  - **North Press Agency (NPA)**:
    - `https://npasyria.com`
  - **Hawar News (ANHA)**:
    - `https://hawarnews.com`
  - **Bianet Kurdî**:
    - `https://bianet.org/kurdi`
  - **Hengaw Human Rights Organization**:
    - `https://hengaw.net`
  - **Mezopotamya Ajansı (MA)**:
    - `https://mezopotamyaajansi44.com/kr` (and dynamic resolution of the latest active MA domain if blocked)
  - **Official KRG Portals**:
    - `https://gov.krd`
    - `https://presidency.gov.krd`
- **International Verification Sources (by Regional Quotas)**:
  - **United States (DYE - 10 Sources)**:
    - The New York Times (`https://www.nytimes.com`)
    - The Washington Post (`https://www.washingtonpost.com`)
    - The Wall Street Journal (`https://www.wsj.com`)
    - Associated Press - AP (`https://apnews.com`)
    - Bloomberg News (`https://www.bloomberg.com`)
    - Foreign Policy (`https://foreignpolicy.com`)
    - Foreign Affairs (`https://www.foreignaffairs.com`)
    - Al-Monitor (`https://www.al-monitor.com`)
    - Politico US (`https://www.politico.com`)
    - CNN International (`https://edition.cnn.com`)
  - **Continental Europe (11 Sources)**:
    - Deutsche Welle - DW [Germany] (`https://www.dw.com`)
    - Der Spiegel [Germany] (`https://www.spiegel.de`)
    - Agence France-Presse - AFP [France] (`https://www.afp.com`)
    - Le Monde [France] (`https://www.lemonde.fr`)
    - France 24 [France] (`https://www.france24.com`)
    - Euronews [EU] (`https://www.euronews.com`)
    - Politico Europe [EU] (`https://www.politico.eu`)
    - SWI swissinfo.ch [Switzerland] (`https://www.swissinfo.ch`)
    - SVT Nyheter [Sweden] (`https://www.svt.se`)
    - El País [Spain] (`https://elpais.com`)
    - NOS [Netherlands] (`https://nos.nl`)
  - **United Kingdom (Îngiltere - Exactly 3 Sources)**:
    - BBC News (`https://www.bbc.com`)
    - Reuters (`https://www.reuters.com`)
    - The Guardian (`https://www.theguardian.com`)
  - **Israel (Israyîl - Exactly 5 Sources)**:
    - The Jerusalem Post (`https://www.jpost.com`)
    - Haaretz (`https://www.haaretz.com`)
    - The Times of Israel (`https://www.timesofisrael.com`)
    - Ynetnews (`https://www.ynetnews.com`)
    - i24NEWS (`https://www.i24news.tv`)
  - **Russia (Ûrisya - Exactly 3 Sources)**:
    - TASS (`https://tass.com`)
    - RIA Novosti (`https://ria.ru`)
    - Kommersant (`https://www.kommersant.ru`)
  - **Turkey (Turkî - Exactly 3 Sources)**:
    - Anadolu Ajansı - AA (`https://www.aa.com.tr`)
    - T24 (`https://t24.com.tr`)
    - Gazete Duvar (`https://www.gazeteduvar.com.tr`)
  - **Iran (Îran - Exactly 1 Source)**:
    - IRNA - Islamic Republic News Agency (`https://en.irna.ir`)
- Synthesize facts, dates, figures, and quotes accurately across language editions.
- **Contradiction Detection**: If verified outlets report conflicting data (e.g., numbers, dates, locations), explicitly reflect the variance rather than choosing one arbitrarily:
  - Format: *"Li gor çavkaniya $A weşandiye ..., lê belê li gor çavkaniya $B ... "*
- Embed standard in-text source citations directly within the sentences:
  - Format: `"li gor çavkaniya $name weşandiye, ... "` (e.g., *"li gor çavkaniya Rûdaw weşandiye, ..."* or *"li gor çavkaniya Kurdistan24 weşandiye, ..."*).

### 3. Automated Linguistic & Orthographic Gate (Kurmanji)
If the news is requested in Kurmanji (`ku`):
- Run automated text validation against `Desktop/projects/ocr/`:
  - `KURDISH_VOCABULARY_RULES.md`
  - `KURMANJI_GRAMMAR_AND_VOCABULARY_RULES.md`
  - Lexicon check using `kurmanji_lexicon.py` and `ferheng_kurmanci.db`
- Enforce mandatory Kurmanji conventions:
  - Strict Latin alphabet with authentic diacritics (`ç`, `ê`, `î`, `ş`, `û`). Strictly reject Turkish letters (`ı`, `ğ`, `ö`, `ü`).
  - Strict conjunction `û` (never `u`).
  - Apostrophe (`'`) for numbers and acronyms (`2026'an de`, `PDK'ê`, `YNK'ê`, `DYA'yê`).
  - Separated prepositions/adverbs: `der barê ... de` (never `derbarê`), `rû bi rû`.
  - Authentic vocabulary preferences:
    - `nûjenkirin` (not update/nûve)
    - `sazkirin` / `avakirin` (not generic damezrandin)
    - `pasevan` (with `v`, not `w`)
    - `hevahengî` (with `v`, not `m`)
    - `wesayet` (not `wesayît`)
    - `werçerxan` (feminine / mê)
    - `pêvajo` (not `prose`)
    - `ne ya partiyan` (avoid `hizbî`)
    - `fikar` (authentic)
- News summary constraint: Exactly **90 to 120 characters**.

### 4. Ethical Image Labeling & Provenance Attribution
- **News Outlet Image**: If an existing image from verified sources is attached or referenced:
  - Add clear attribution text at the very bottom of the article:
    `Wêne: $Source` (e.g., `Wêne: Rûdaw` or `Wêne: Kurdistan24`).
- **Synthetic/Symbolic Image**: If an image is AI-generated:
  - Add clear attribution text at the very bottom:
    `Wêne: Sembolîk` (or `Wêne: Amîda Kurd / Sembolîk`).
  - Follow editorial photojournalism style, optimize as WebP, and attach via MCP.

### 5. Provenance Tracking, Categorization & Draft Staging
- **Provenance Tracking**: Include verified source URLs, query terms, and verification timestamps in the article's meta description or audit trail.
- **Categorization**: Map the article to the most relevant existing category in `mod_news` (e.g., Rojev, Polîtîka, Cîhan, etc.) matching the requested language.
- **Author Attribution**: Always attribute the drafted article to **Derya Welat** (`user_id: 535` / `username: "deryawelat"`).
- **Draft Staging (Human-in-the-Loop)**:
  - Call `create_news` on the `amidakurd` MCP server with:
    - `user_id: 535` (or `username: "deryawelat"`).
    - `status: "n"` (**STRICT REQUIREMENT: Save as draft, DO NOT publish**).
    - Valid `summary` (90–120 characters).
    - Full HTML formatted `content` with in-text citations and image credits.
    - SEO slug, meta description, and 3–5 relevant keywords for search indexing completeness.
- Report completion to the user in Kurmanji, providing:
  - Generated News ID
  - Author name (*Derya Welat @deryawelat*)
  - Category name
  - Headline and summary
  - List of verified sources and detected contradictions (if any)
  - Confirmation that the article is staged as a draft awaiting human editorial approval.
