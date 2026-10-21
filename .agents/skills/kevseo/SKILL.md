---
name: kevseo
description: Google News, Discover, and Semantic HTML SEO optimizer for Turkish news drafts. Decides semantic HTML architecture, content length, heading hierarchies, entity-first headlines, and Discover-ready visual assets.
---

# Kevseo Google News & Semantic HTML SEO Optimizer

## Operating Rule

Keep this skill as a short ordered execution checklist. Follow the editorial pipeline:
`kevkolin` (Research) -> `kevrast` (Stage 1) -> `kevanger` (Drafting in Turkish) -> `kevseo` (SEO & HTML Architecture) -> `kevrast` (Stage 2) -> Staging.

`kevseo` directly governs and decides the **semantic HTML architecture** of the news `content`, content depth thresholds, heading hierarchies, Google News E-E-A-T signals, and Google Discover readiness.

## Sequence

### 1. Semantic HTML Content Architecture Mandate
The news article body (`content`) MUST adhere to strict semantic HTML standards:

1. **Heading Hierarchy (Strict Level 2 & 3 Only)**:
   - **No `<h1>` inside content**: The article headline is rendered by the system template as `<h1>`. Content body must NEVER contain `<h1>`.
   - **Mandatory `<h2>` Subheadings**: Every thematic section (context, background, key developments, official statements, local impact) MUST begin with a descriptive, entity-rich `<h2>` tag.
   - **Nested `<h3>` Only**: If a subsection requires further breakdown, use `<h3>`. Never skip heading levels (e.g. no `<h4>` directly under `<h2>`).
2. **Lead Paragraph (`<p>`)**:
   - The opening paragraph must answer the primary 5N1K (Kim, Ne, Nerede, Ne Zaman, Neden, Nasıl) within the first 30–45 words without filler phrases.
3. **Paragraph Length & Chunking**:
   - Maximum 3 to 4 sentences per `<p>` tag to optimize for mobile viewports and Google News snippet extraction. Avoid dense walls of text.
4. **Semantic Blockquotes & Excerpts (`<blockquote>`)**:
   - Substantial official statements, quotes, and excerpts must be enclosed within `<blockquote><p>"..."</p></blockquote>` or clean quotation paragraphs using standard double quotes `"..."`. French guillemets (`«...»`) are strictly forbidden.
5. **Structured Lists (`<ul>` / `<ol>` and `<li>`)**:
   - Key takeaways, bulleted facts, statistics, or chronological developments should be structured using semantic `<ul>` or `<ol>` elements.
6. **Outbound Authority Citations (`<a href="...">`)**:
   - In-text citations must use clean descriptive anchors (e.g. `<a href="$URL">$Name</a>`).
   - Major stories must include at least 2 verified, live outbound authority links.
7. **Image Attribution Block**:
   - At the bottom of the article: `<p><strong>Fotoğraf: $Source</strong></p>`.
8. **Strict Code Separation Mandate (AGENTS.md Rule 2)**:
   - Zero inline styles (`style="..."`, `<style>`), zero inline scripts, and zero inline event handlers (`onclick="..."`). Only clean semantic HTML tags.

### 2. Content Length & Depth Thresholds
`kevseo` evaluates article length based on the news classification:

1. **Daily / Breaking News (Hard News)**:
   - Minimum: 2,500 characters (~400 words).
   - Ideal: 3,000 – 4,500 characters (~450 – 700 words).
2. **Major / Regional Events (Top Stories & Google Discover)**:
   - Minimum: 4,000 characters (~600 words).
   - Ideal: 4,500 – 7,000+ characters (~700 – 1,100+ words) with at least 3 to 5 `<h2>` sections.
3. **Thin Content Rejection Threshold**:
   - Any draft under 1,500 characters (< 250 words) MUST be rejected as "thin content" and expanded before staging.

### 3. Headline & Deck Summary Standards
1. **Headline (`name`)**:
   - Recommended length: 55–75 characters (maximum 80).
   - Entity-first and keyword-frontloaded (place the person, institution, or city/event at the front).
   - Non-clickbait: Directly communicates the factual essence.
2. **Deck Summary (`summary`)**:
   - Strictly 90–120 characters (enforced by schema and Google News snippet preview).

### 4. Visual Asset Requirements (Google Discover Ready)
1. **Minimum Dimensions**: Width must be at least 1200px (16:9 aspect ratio preferred).
2. **Format & Optimization**: WebP format, optimized to under 100 KB.
3. **Attribution**: Transparent source credit attached in prose (`Fotoğraf: ...`) and CMS metadata.

### 5. SEO Slug & Keywords
1. **Wikipedia-Style Slug (`seo`)**:
   - Underscores (`_`), zero hyphens (`-`).
   - Original Turkish Unicode characters preserved (`ç, ğ, ı, İ, ö, ş, ü`).
2. **Keywords (`keywords`)**:
   - 3 to 6 focused, comma-separated Turkish search tokens.

### 6. Automated Audit Execution
Execute the automated SEO auditor script against the drafted article:
```bash
python3 .agents/skills/kevseo/scripts/audit_seo.py --content "$DRAFT_HTML" --summary "$SUMMARY" --headline "$HEADLINE" --seo "$SEO_SLUG"
```
Ensure the audit returns `Status: PASSED` before proceeding to `kevrast` (Stage 2).
