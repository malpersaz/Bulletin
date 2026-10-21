---
name: kevkolin
description: Curate, investigate, cross-reference, and compile multi-source research dossiers for Diyarbakır (Amed), regional, national, and international news events. Serves as the upstream research engine feeding Kevrast (Stage 1) and Kevanger.
---

# Kevkolin Investigation & Research Engine

## Operating Rule

Keep this skill as a short ordered execution checklist. Follow multi-source investigation protocols strictly and output a structured Research Dossier for downstream validation and Turkish journalistic drafting.

## Sequence

### 1. Topic Ingestion & Scope Definition
- Identify the news event from user instruction or autonomous breaking news scan.
- Determine the scope: Diyarbakır (Amed) local/municipal/civic coverage, regional coverage, international diplomacy, technology, conflicts, or economics.

### 2. Multi-Source Investigation against Authoritative Registry
Search and cross-reference verified outlets using `.agents/skills/kevkolin/scripts/source_catalog.py`:

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
Compile the verified findings into a standardized Research Dossier containing:
1. **Event Overview**: What happened, who was involved, when, and where.
2. **Verified Key Facts**:
   - Exact numbers, quantities, metrics, and units.
   - Exact dates, years, times, and timezones.
   - Verbatim official statements and quotes.
3. **Source Evidence & Live Links**:
   - Source names and deep URLs for each claim.
4. **Contradictions & Nuances**:
   - Explicitly listed discrepancies between sources.

### 5. Hand-off to Kevrast (Stage 1 Pre-Verification)
- Pass the compiled Research Dossier to `kevrast` for Stage 1 pre-draft verification.
- Once `kevrast` confirms all links are live (HTTP 200) and claims are groundable, the verified dossier proceeds to `kevanger` for Turkish journalistic drafting.
