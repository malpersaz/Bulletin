# Handoff Report: Kevseo & Content SEO Alignment, Rubric Governance & Rule Citations (Milestone 4)

## 1. Observation

### 1.1 Pre-Staging (`audit_seo.py`) vs Post-Staging (`audit_content_seo.py`) Rubric Divergence
Direct inspection of `.agents/skills/kevseo/scripts/audit_seo.py` (224 lines) and `tools/audit_content_seo.py` (647 lines) revealed major rubric drift and silent bypasses:

1. **Empty Headline and Summary Silently Pass (`audit_seo.py:152-154`)**:
   ```python
   has_failed_deck = (not meta_res.get("deck_valid") if summary else False)
   has_failed_headline = (meta_res.get("headline_status") == "FAILED" if headline else False)
   ```
   When `--headline` or `--summary` is omitted on the CLI, `has_failed_deck` and `has_failed_headline` evaluate to `False`. A draft with 350 words of body content, 0 `<h2>` subheadings, no citations, and no headline/summary exits with return code `0` and `"status": "PASSED"`:
   ```bash
   $ python3 .agents/skills/kevseo/scripts/audit_seo.py --content "<p>$(python3 -c 'print("word " * 350)')</p>" --json
   # Output: exit code 0, status: PASSED, headline_status: EMPTY, deck_status: FAILED
   ```

2. **Complete Absence of Meta Description & Dual-Field Synchronization**:
   - `tools/audit_content_seo.py:409-418` explicitly grades dual-field synchronization (`description == summary`, 90–160 chars in curator mode, 90–120 in editorial mode):
     ```python
     description = str(record.get("description") or "").strip()
     desc_len = len(description)
     desc_synced = (90 <= desc_len <= 160) and (description == summary)
     desc_pts = 5 if desc_synced else (3 if 90 <= desc_len <= 160 else 0)
     ```
   - In `.agents/skills/kevseo/scripts/audit_seo.py`, there is zero check for `description`, no `--description` CLI argument, and no dual-field synchronization logic.

3. **Insufficient `<h2>` Subheading Enforcement (`audit_seo.py:55-61`)**:
   - In `audit_seo.py`:
     ```python
     h2_matches = re.findall(r'<h2[^>]*>(.*?)</h2>', html_text, re.IGNORECASE | re.DOTALL)
     if len(h2_matches) == 0:
         issues.append({
             "type": "missing_h2",
             "severity": "WARNING",
             "message": "Content lacks <h2> thematic subheadings..."
         })
     ```
     `severity` is only `"WARNING"` (non-blocking). If a draft has 1 or 2 `<h2>` subheadings, it generates zero issues.
   - In `tools/audit_content_seo.py:343` (`--profile editorial`):
     ```python
     heading_pts = 7 if len(h2_matches) >= 3 and len(h1_matches) == 0 else (4 if len(h2_matches) >= 2 and len(h1_matches) == 0 else 0)
     ```
     Full points strictly require `>= 3 <h2>` headings.

4. **Missing Heading Level Jump Validation**:
   - Neither `audit_seo.py` nor `audit_content_seo.py` validates heading outline jumps. A draft transitioning directly from `<h2>` to `<h4>` (skipping `<h3>`) or starting its body headings at `<h3>` is ignored.

5. **Missing Paragraph Length & Chunking Limits**:
   - `kevseo/SKILL.md:27-28` mandates:
     *"Maximum 3 to 4 sentences per <p> tag to optimize for mobile viewports and Google News snippet extraction. Avoid dense walls of text."*
   - Neither script currently computes sentence or word counts per `<p>` block, nor warns when a paragraph exceeds 5 sentences or 90 words.

### 1.2 Stale AGENTS.md Rule Number Citations
Grep across the workspace identified five locations citing outdated `AGENTS.md` rule numbers:
1. `.agents/skills/kevseo/scripts/audit_seo.py:63`: `# 3. Check for inline styles and scripts (AGENTS.md Rule 2)` — actually **`AGENTS.md Rule 1`** (Strict Code Separation Mandate).
2. `.agents/skills/kevseo/SKILL.md:39`: `8. **Strict Code Separation Mandate (AGENTS.md Rule 2)**:` — actually **`AGENTS.md Rule 1`**.
3. `tools/code_separation_guard.py:6`: `Enforces AGENTS.md Rule 2 (Strict Code Separation Mandate):` — actually **`AGENTS.md Rule 1`**.
4. `docs/CODE_QUALITY_AGENT_STRATEGY.md:16, 20`: `(AGENTS.md Rule 7)` for non-English code/comments — actually **`AGENTS.md Rule 6`** (`Module Internal Quality & i18n Rule`).
5. `mod/mod_news/validator/add.validator.php:141` and `edit.validator.php:140`: `// Normalize SEO slug (Rule 10)` — actually **`AGENTS.md Rule 9`** (`URL Generation Parity (Wikipedia Style)`).

---

## 2. Logic Chain

1. **Premise 1**: In the editorial pipeline, `tools/verify_news_pipeline.sh` Stage 2 executes `.agents/skills/kevseo/scripts/audit_seo.py` as a pre-staging quality gate before a story is committed to the database.
2. **Premise 2**: Stage 4 of `verify_news_pipeline.sh` (or post-staging auditing) executes `tools/audit_content_seo.py --profile editorial`. If pre-staging allows relaxed criteria that fail post-staging, drafts will be rejected after database insertion or fail deployment gates.
3. **Premise 3**: To ensure zero false-passes and complete rubric alignment:
   - Headline length must be strictly `40–70` characters (enforced by `McpCrudToolsBase::assertHeadlineLength()` and `kevanger/SKILL.md:46`).
   - Summary length must be strictly `90–120` characters (enforced by `McpCrudToolsBase::assertSummaryLength()` and `audit_content_seo.py:397`).
   - Meta description must equal summary (`description == summary`, 90–120 characters, matching `audit_content_seo.py:411`).
   - Subheadings must contain `>= 3 <h2>` headings for full editorial credit (`audit_content_seo.py:343`).
   - Heading hierarchy must forbid skipping levels (e.g. `<h2>` -> `<h4>` skipping `<h3>`), and content must not start with `<h3..h6>` without a preceding `<h2>`.
   - Paragraphs exceeding 5 sentences or 90 words degrade mobile readability (`kevseo/SKILL.md:27-28`) and must trigger a structured `WARNING`.
4. **Premise 4**: A dedicated `--strict-editorial` flag allows `audit_seo.py` to be invoked by `verify_news_pipeline.sh` to enforce all mandatory inputs (`--headline`, `--summary`, `--description`), while preserving backwards compatibility for standalone snippet testing when flags are omitted.
5. **Premise 5**: Updating rule citations in `audit_seo.py`, `kevseo/SKILL.md`, `code_separation_guard.py`, `CODE_QUALITY_AGENT_STRATEGY.md`, and `mod_news` validators restores 100% adherence to the priority hierarchy in `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/AGENTS.md`.

---

## 3. Caveats

1. **Backwards Compatibility**:
   - In non-strict mode (`audit_seo.py` without `--strict-editorial`), omitting `--headline`, `--summary`, or `--description` remains non-blocking to allow developers/agents to audit raw HTML snippets for inline style/script violations.
   - However, if `--headline` or `--summary` or `--description` *is* provided in non-strict mode, its length and sync validity are strictly checked (invalid input will fail).
2. **Sentence Segmentation in Turkish News**:
   - In Turkish journalistic prose, punctuation such as dots in abbreviations (`Dr.`, `Prof.`, `Av.`, `vb.`, `No.`) and decimals (`15.5 milyon`, `%12.5`, `2026.05`) must not trigger false sentence splits. The regex sanitizer must normalize these before splitting on sentence boundaries `[.!?…]+`.
3. **Scope Discipline**:
   - The Explorer operates in read-only mode; source changes must be implemented by the assigned Worker.

---

## 4. Conclusion & Ready-to-Implement Specification

### 4.1 Specification for `.agents/skills/kevseo/scripts/audit_seo.py`

#### A. Updated `audit_headline_and_deck` (supporting `description` and `strict`)
```python
    def audit_headline_and_deck(
        self,
        headline: str,
        summary: str,
        description: str = "",
        strict: bool = False
    ) -> Dict[str, Any]:
        h_len = len(headline.strip()) if headline else 0
        s_len = len(summary.strip()) if summary else 0
        d_len = len(description.strip()) if description else 0

        h_valid = (40 <= h_len <= 70)
        s_valid = (90 <= s_len <= 120)

        desc_provided = bool(description and description.strip())
        desc_synced = (description.strip() == summary.strip()) if (desc_provided and summary) else False
        d_valid = (90 <= d_len <= 120) and desc_synced

        if headline:
            h_status = "PASSED" if h_valid else "FAILED"
        else:
            h_status = "FAILED" if strict else "EMPTY"

        if summary:
            s_status = "PASSED" if s_valid else "FAILED"
        else:
            s_status = "FAILED" if strict else "EMPTY"

        if desc_provided:
            d_status = "PASSED" if d_valid else "FAILED"
        else:
            d_status = "FAILED" if strict else "EMPTY"

        if strict:
            meta_valid = h_valid and s_valid and d_valid
        else:
            fail_h = bool(headline and not h_valid)
            fail_s = bool(summary and not s_valid)
            fail_d = bool(desc_provided and not d_valid)
            meta_valid = not (fail_h or fail_s or fail_d)

        return {
            "headline_length": h_len,
            "headline_status": h_status,
            "summary_length": s_len,
            "deck_valid": s_valid,
            "deck_status": s_status,
            "description_length": d_len,
            "description_status": d_status,
            "description_synced": desc_synced,
            "meta_valid": meta_valid
        }
```

#### B. Updated `audit_html_architecture` (Heading Hierarchy, Level Jumps, Paragraph Limits, Rule 1)
```python
    def audit_html_architecture(self, html_text: str, strict: bool = False) -> List[Dict[str, Any]]:
        issues = []

        # 1. No <h1> inside content
        if re.search(r'<h1[^>]*>', html_text, re.IGNORECASE):
            issues.append({
                "type": "disallowed_h1",
                "severity": "CRITICAL",
                "message": "Content must not contain <h1> tags. Article title is already rendered as <h1> by the template."
            })

        # 2. Heading Hierarchy & Minimum <h2> subheadings
        h2_matches = re.findall(r'<h2[^>]*>(.*?)</h2>', html_text, re.IGNORECASE | re.DOTALL)
        h2_count = len(h2_matches)

        if strict:
            if h2_count < 3:
                issues.append({
                    "type": "insufficient_h2",
                    "severity": "CRITICAL",
                    "message": f"Editorial standards require at least 3 <h2> thematic subheadings (found {h2_count})."
                })
        else:
            if h2_count == 0:
                issues.append({
                    "type": "missing_h2",
                    "severity": "WARNING",
                    "message": "Content lacks <h2> thematic subheadings. Google News heavily rewards structured sections with descriptive subheadings."
                })
            elif h2_count < 3:
                issues.append({
                    "type": "insufficient_h2",
                    "severity": "WARNING",
                    "message": f"Content has only {h2_count} <h2> subheading(s). At least 3 <h2> thematic subheadings are recommended for Google News / Discover."
                })

        # Heading Level Jumps (No skipping heading levels: e.g. <h2> -> <h4>)
        headings = [int(m.group(1)) for m in re.finditer(r'<h([1-6])\b[^>]*>', html_text, re.IGNORECASE)]
        if headings:
            if headings[0] > 2:
                issues.append({
                    "type": "heading_level_jump",
                    "severity": "CRITICAL",
                    "message": f"First heading in content is <h{headings[0]}>. News content must begin with <h2> before nested subsections."
                })
            for i in range(1, len(headings)):
                prev = headings[i - 1]
                curr = headings[i]
                if curr > prev + 1:
                    issues.append({
                        "type": "heading_level_jump",
                        "severity": "CRITICAL",
                        "message": f"Invalid heading hierarchy: jumped from <h{prev}> to <h{curr}> without intermediate <h{prev + 1}>."
                    })

        # 3. Paragraph Limits & Chunking (warning if > 5 sentences or > 90 words)
        p_matches = re.finditer(r'<p\b[^>]*>(.*?)</p>', html_text, re.IGNORECASE | re.DOTALL)
        for idx, match in enumerate(p_matches, start=1):
            p_html = match.group(1)
            plain_p = re.sub(r'<[^>]+>', ' ', p_html)
            plain_p = re.sub(r'\s+', ' ', plain_p).strip()
            if not plain_p:
                continue
            words = plain_p.split()
            word_count = len(words)

            cleaned_p = re.sub(r'\b\d+[\.,]\d+\b', '', plain_p)
            cleaned_p = re.sub(r'\b(?:Dr|Prof|Doç|Av|vb|vs|bkz|ör|m|km|sn|dk|sa|No|st|cad|mah)\.', '', cleaned_p, flags=re.IGNORECASE)
            sentences = [s.strip() for s in re.split(r'[.!?…]+(?:\s+|$)', cleaned_p) if s.strip()]
            sentence_count = len(sentences)

            if sentence_count > 5 or word_count > 90:
                issues.append({
                    "type": "dense_paragraph",
                    "severity": "WARNING",
                    "message": f"Paragraph #{idx} is too dense ({word_count} words, {sentence_count} sentences). Keep paragraphs under 5 sentences and 90 words for mobile readability."
                })

        # 4. Check for inline styles and scripts (AGENTS.md Rule 1)
        if re.search(r'\sstyle=[\'"][^\'"]*[\'"]', html_text, re.IGNORECASE):
            issues.append({
                "type": "inline_style_violation",
                "severity": "CRITICAL",
                "message": "Strict Code Separation (AGENTS.md Rule 1): Inline style='...' attributes are forbidden in news HTML."
            })

        if re.search(r'<script[^>]*>', html_text, re.IGNORECASE):
            issues.append({
                "type": "inline_script_violation",
                "severity": "CRITICAL",
                "message": "Strict Code Separation (AGENTS.md Rule 1): Inline <script> tags are forbidden in news HTML."
            })

        if re.search(r'\son[a-z]+=[\'"][^\'"]*[\'"]', html_text, re.IGNORECASE):
            issues.append({
                "type": "inline_event_handler",
                "severity": "CRITICAL",
                "message": "Strict Code Separation (AGENTS.md Rule 1): Inline event handlers (e.g. onclick) are forbidden."
            })

        # 5. Outbound links / citations
        links = re.findall(r'<a\s+[^>]*href=[\'"]([^\'"]+)[\'"][^>]*>(.*?)</a>', html_text, re.IGNORECASE)
        if len(links) < 1:
            issues.append({
                "type": "missing_citations",
                "severity": "WARNING",
                "message": "Content has no outbound citations. At least 1-2 primary source authority links are required for E-E-A-T."
            })

        # 6. Image attribution (Turkish: Fotoğraf: ... / Görsel: ..., Kurdish: Wêne: ...)
        if not re.search(r'<strong>\s*(?:Fotoğraf|Görsel|Wêne)\s*:\s*[^<]+</strong>', html_text, re.IGNORECASE):
            issues.append({
                "type": "missing_image_attribution",
                "severity": "NOTICE",
                "message": "Image attribution line ('Wêne: ...' or 'Fotoğraf: ...') not found at the bottom of the article."
            })

        # 7. Disallowed French guillemets
        if re.search(r'[«»]', html_text):
            issues.append({
                "type": "disallowed_guillemets",
                "severity": "CRITICAL",
                "message": "French guillemets («...») are forbidden. Use standard double quotes (\"...\")."
            })

        return issues
```

#### C. Updated `run_full_audit`
```python
    def run_full_audit(
        self,
        html_text: str,
        summary: str = "",
        headline: str = "",
        seo: str = "",
        description: str = "",
        strict: bool = False
    ) -> Dict[str, Any]:
        length_res = self.audit_content_length(html_text)
        arch_issues = self.audit_html_architecture(html_text, strict=strict) + self.audit_seo_slug(seo)
        meta_res = self.audit_headline_and_deck(headline, summary, description=description, strict=strict)

        critical_count = sum(1 for i in arch_issues if i.get("severity") == "CRITICAL")
        has_failed_length = (length_res.get("status") == "FAILED")
        has_failed_meta = not meta_res.get("meta_valid")

        passed = (critical_count == 0 and not has_failed_length and not has_failed_meta)

        return {
            "status": "PASSED" if passed else "FAILED",
            "length_analysis": length_res,
            "architecture_issues": arch_issues,
            "meta_analysis": meta_res
        }
```

#### D. Updated CLI Arguments in `main()`
```python
    parser = argparse.ArgumentParser(description="Kevseo Google SEO & Semantic HTML Auditor")
    parser.add_argument("--content", type=str, help="HTML content string")
    parser.add_argument("--file", type=str, help="Path to file containing HTML content")
    parser.add_argument("--summary", type=str, default="", help="Deck summary text")
    parser.add_argument("--headline", type=str, default="", help="News headline")
    parser.add_argument("--description", type=str, default="", help="Meta description text (synchronized with summary)")
    parser.add_argument("--seo", type=str, default="", help="Optional Wikipedia-style SEO slug")
    parser.add_argument("--strict-editorial", action="store_true", help="Enforce strict editorial rubric parity (required headline, summary, description sync, >=3 h2)")
    parser.add_argument("--json", action="store_true", help="Output raw JSON")
```

---

### 4.2 Integration with `tools/verify_news_pipeline.sh`
Worker must update `tools/verify_news_pipeline.sh`:
1. Add `--description` parameter handling (defaulting to `$SUMMARY`):
   ```bash
   --description)
       DESCRIPTION="$2"
       shift 2
       ;;
   ```
2. Pass `--strict-editorial` and `--description` in Stage 2:
   ```bash
   KEVSEO_CMD=(python3 "$WORKSPACE_ROOT/.agents/skills/kevseo/scripts/audit_seo.py" \
       --file "$DRAFT_FILE" \
       --headline "$HEADLINE" \
       --summary "$SUMMARY" \
       --description "${DESCRIPTION:-$SUMMARY}" \
       --strict-editorial)
   if [[ -n "$SEO_SLUG" ]]; then
       KEVSEO_CMD+=(--seo "$SEO_SLUG")
   fi
   "${KEVSEO_CMD[@]}"
   ```

---

### 4.3 Stale Rule Citation Fixes
Worker must apply these exact textual updates:
1. `.agents/skills/kevseo/scripts/audit_seo.py:63`: Change `AGENTS.md Rule 2` to `AGENTS.md Rule 1`.
2. `.agents/skills/kevseo/SKILL.md:39`: Change `(AGENTS.md Rule 2)` to `(AGENTS.md Rule 1)`.
3. `tools/code_separation_guard.py:6`: Change `AGENTS.md Rule 2` to `AGENTS.md Rule 1`.
4. `docs/CODE_QUALITY_AGENT_STRATEGY.md:16, 20`: Change `AGENTS.md Rule 7` to `AGENTS.md Rule 6`.
5. `mod/mod_news/validator/add.validator.php:141`: Change `// Normalize SEO slug (Rule 10)` to `// Normalize SEO slug (Rule 9)`.
6. `mod/mod_news/validator/edit.validator.php:140`: Change `// Normalize SEO slug (Rule 10)` to `// Normalize SEO slug (Rule 9)`.

---

## 5. Verification Method

### 5.1 Verification Commands for Worker & Tester
1. **Python Compilation**:
   ```bash
   python3 -m py_compile .agents/skills/kevseo/scripts/audit_seo.py tools/code_separation_guard.py
   ```
2. **Quality Guards**:
   ```bash
   python3 tools/code_separation_guard.py
   python3 tools/i18n_guard.py --path .agents/skills/kevseo/scripts/audit_seo.py tools/code_separation_guard.py
   ```
3. **Behavioral Test Suite (`audit_seo.py`)**:
   - **Test 1: Valid Editorial Draft Passes**:
     ```bash
     python3 .agents/skills/kevseo/scripts/audit_seo.py \
       --headline "Diyarbakır Sur Bölgesinde Kapsamlı Restorasyon Projesi Başlatıldı" \
       --summary "Diyarbakır Sur ilçesinde tarihi yapıların korunması amacıyla 12 etaplık kapsamlı restorasyon projesi başlatıldı." \
       --description "Diyarbakır Sur ilçesinde tarihi yapıların korunması amacıyla 12 etaplık kapsamlı restorasyon projesi başlatıldı." \
       --strict-editorial \
       --content "<p>Lead paragraph...</p><h2>Tarihi Yapılar Korunuyor</h2><p>Paragraph 1...</p><h2>Proje Aşamaları</h2><p>Paragraph 2...</p><h2>Bölgesel Etki</h2><p>Paragraph 3...</p><p><a href='https://example.com/source'>Kaynak</a></p><p><strong>Fotoğraf: AA</strong></p>"
     # Expected: exit code 0, Status: PASSED
     ```
   - **Test 2: Short Headline (< 40 Chars) Fails in Strict Mode**:
     ```bash
     python3 .agents/skills/kevseo/scripts/audit_seo.py \
       --headline "Kısa Başlık" \
       --summary "Diyarbakır Sur ilçesinde tarihi yapıların korunması amacıyla 12 etaplık kapsamlı restorasyon projesi başlatıldı." \
       --description "Diyarbakır Sur ilçesinde tarihi yapıların korunması amacıyla 12 etaplık kapsamlı restorasyon projesi başlatıldı." \
       --strict-editorial \
       --content "<p>Body...</p><h2>Bölüm 1</h2><p>P1</p><h2>Bölüm 2</h2><p>P2</p><h2>Bölüm 3</h2><p>P3</p><p><a href='https://example.com'>Link</a></p><p><strong>Fotoğraf: AA</strong></p>"
     # Expected: exit code 1, Status: FAILED
     ```
   - **Test 3: Unsynced Description Fails in Strict Mode**:
     ```bash
     python3 .agents/skills/kevseo/scripts/audit_seo.py \
       --headline "Diyarbakır Sur Bölgesinde Kapsamlı Restorasyon Projesi Başlatıldı" \
       --summary "Diyarbakır Sur ilçesinde tarihi yapıların korunması amacıyla 12 etaplık kapsamlı restorasyon projesi başlatıldı." \
       --description "Farklı bir açıklama metni girildiğinde senkronizasyon hatası vermeli ve doğrulamayı kesmelidir." \
       --strict-editorial \
       --content "<p>Body...</p><h2>Bölüm 1</h2><p>P1</p><h2>Bölüm 2</h2><p>P2</p><h2>Bölüm 3</h2><p>P3</p><p><a href='https://example.com'>Link</a></p><p><strong>Fotoğraf: AA</strong></p>"
     # Expected: exit code 1, Status: FAILED
     ```
   - **Test 4: Less Than 3 `<h2>` Headings Fails in Strict Mode**:
     ```bash
     python3 .agents/skills/kevseo/scripts/audit_seo.py \
       --headline "Diyarbakır Sur Bölgesinde Kapsamlı Restorasyon Projesi Başlatıldı" \
       --summary "Diyarbakır Sur ilçesinde tarihi yapıların korunması amacıyla 12 etaplık kapsamlı restorasyon projesi başlatıldı." \
       --description "Diyarbakır Sur ilçesinde tarihi yapıların korunması amacıyla 12 etaplık kapsamlı restorasyon projesi başlatıldı." \
       --strict-editorial \
       --content "<p>Body...</p><h2>Tek Başlık</h2><p>P1</p><p><a href='https://example.com'>Link</a></p><p><strong>Fotoğraf: AA</strong></p>"
     # Expected: exit code 1, Status: FAILED (insufficient_h2 CRITICAL)
     ```
   - **Test 5: Heading Level Jump (`<h2>` -> `<h4>`) Fails**:
     ```bash
     python3 .agents/skills/kevseo/scripts/audit_seo.py \
       --content "<h2>Bölüm 1</h2><h4>Doğrudan Dört Seviyesine Atlama</h4><h2>Bölüm 2</h2><h2>Bölüm 3</h2>" \
       --json
     # Expected: exit code 1, contains "type": "heading_level_jump"
     ```
   - **Test 6: Dense Paragraph Emits WARNING but Does Not Cause Exit Code 1**:
     ```bash
     python3 .agents/skills/kevseo/scripts/audit_seo.py \
       --headline "Diyarbakır Sur Bölgesinde Kapsamlı Restorasyon Projesi Başlatıldı" \
       --summary "Diyarbakır Sur ilçesinde tarihi yapıların korunması amacıyla 12 etaplık kapsamlı restorasyon projesi başlatıldı." \
       --description "Diyarbakır Sur ilçesinde tarihi yapıların korunması amacıyla 12 etaplık kapsamlı restorasyon projesi başlatıldı." \
       --strict-editorial \
       --content "<p>Cümle bir. Cümle iki. Cümle üç. Cümle dört. Cümle beş. Cümle altı burada yer alıyor ve uyarı vermeli.</p><h2>Bölüm 1</h2><p>P1</p><h2>Bölüm 2</h2><p>P2</p><h2>Bölüm 3</h2><p>P3</p><p><a href='https://example.com'>Link</a></p><p><strong>Fotoğraf: AA</strong></p>" \
       --json
     # Expected: exit code 0, Status: PASSED, contains "type": "dense_paragraph", "severity": "WARNING"
     ```
   - **Test 7: Stale Rule Citations Clean**:
     ```bash
     ! grep -rn "AGENTS.md Rule 2" .agents/skills/ tools/
     ! grep -rn "AGENTS.md Rule 7" docs/
     ! grep -rn "Rule 10" mod/mod_news/validator/
     ```

### 5.2 Invalidation Conditions
- If `audit_seo.py` with `--strict-editorial` allows any draft with `< 3 <h2>` to exit 0.
- If `audit_seo.py` allows a draft with `description != summary` to exit 0 in `--strict-editorial` mode.
- If `audit_seo.py` fails when a dense paragraph (> 5 sentences) is encountered in an otherwise clean draft (it must remain a non-blocking `WARNING`).
- If any stale rule number (`Rule 2` for code separation, `Rule 7` for English, `Rule 10` for Wikipedia slug) remains in modified files.
