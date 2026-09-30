---
name: kevkolin
description: Curate, investigate, cross-reference, and compile multi-source research dossiers for Kurdish and international news events. Serves as the upstream research engine feeding Kevrast (Stage 1) and Kevanger.
---

# Kevkolin Investigation & Research Engine

## Operating Rule

Keep this skill as a short ordered execution checklist. Follow multi-source investigation protocols strictly and output a structured Research Dossier for downstream validation and drafting.

## Sequence

### 1. Topic Ingestion & Scope Definition
- Identify the news event from user instruction or autonomous breaking news scan.
- Determine the scope: regional Kurdish coverage, international diplomacy, technology, conflicts, or economics.

### 2. Multi-Source Investigation against Authoritative Registry
Search and cross-reference verified outlets using `.agents/skills/kevkolin/scripts/source_catalog.py`:

1. **Kurdish & Regional Media**:
   - Rûdaw (`rudaw.net/kurmanci`, `rudaw.net/english`, `rudaw.net/sorani`, `rudaw.net/turkish`)
   - Kurdistan24 (`kurdistan24.net/kmr`, `kurdistan24.net/ckb`, `kurdistan24.net/en`)
   - Dengê Amerîka - VOA Kurdî (`dengiamerika.com`)
   - 964media (`964media.com`), NRT TV (`nrttv.com`), Shafaq News (`shafaq.com`)
   - North Press Agency (`npasyria.com`), Hawar News (`hawarnews.com`)
   - Bianet Kurdî (`bianet.org/kurdi`), Hengaw (`hengaw.net`), Mezopotamya Ajansı (`mezopotamyaajansi44.com/kr`)
   - Official KRG Portals (`gov.krd`, `presidency.gov.krd`)
2. **International Sources by Regional Quotas**:
   - **United States (10)**: NYT, Washington Post, WSJ, AP, Bloomberg, Foreign Policy, Foreign Affairs, Al-Monitor, Politico US, CNN.
   - **Continental Europe (11)**: DW, Der Spiegel, AFP, Le Monde, France 24, Euronews, Politico Europe, SWI swissinfo, SVT, El País, NOS.
   - **United Kingdom (3)**: BBC News, Reuters, The Guardian.
   - **Israel (5)**: The Jerusalem Post, Haaretz, The Times of Israel, Ynetnews, i24NEWS.
   - **Russia (3)**: TASS, RIA Novosti, Kommersant.
   - **Ukraine (3)**: Ukrinform, The Kyiv Independent, Ukrainska Pravda.
   - **Turkey (3)**: Anadolu Ajansı (AA), T24, Gazete Duvar.
   - **Iran (1)**: IRNA.

### 3. Contradiction & Variance Detection
- Cross-reference figures, dates, casualties, casualty claims, and official statements across editions.
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
- Once `kevrast` confirms all links are live (HTTP 200) and claims are groundable, the verified dossier proceeds to `kevanger` for journalistic drafting.
