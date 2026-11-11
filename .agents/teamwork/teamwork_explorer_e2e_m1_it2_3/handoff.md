# Investigation & Fix Strategy Report: Domain Assertion Utilities Hardening (`assertions.py`)

## 1. Observation

Direct empirical observations from inspecting `tests/e2e/harness/assertions.py`, `tests/e2e/test_harness_self_verification.py`, and evaluating adversarial test cases reported by Challenger 2:

### Observation 1: `assert_no_inline_styles_or_scripts` Blanket-Rejects Valid JSON-LD and External Scripts
- **File & Lines**: `tests/e2e/harness/assertions.py:229-230`
  ```python
  if re.search(r'<\s*script\b[^>]*>', content, flags=re.IGNORECASE):
      violations.append('Inline <script> tag found')
  ```
- **Direct Test Failure**:
  ```python
  # Valid Feature 28 Google NewsArticle JSON-LD:
  assert_no_inline_styles_or_scripts('<script type="application/ld+json">{"@context":"https://schema.org"}</script>')
  # External script tag:
  assert_no_inline_styles_or_scripts('<script src="/static/js/video-embed.js" defer></script>')
  ```
  **Verbatim Error**:
  ```text
  E2EAssertionError: Code Separation violation(s):
    - Inline <script> tag found
  ```
- **Context**: Feature 28 explicitly requires rendering `<script type="application/ld+json">` for Google News Article metadata (`templates/amed/index/mod_news/news.twig:122, 241, 256`). Public templates also legitimately include external script bundles via `<script src="...">` (`templates/amed/index/mod_news/news.twig:661`). The current regex unconditionally flags every `<script>` tag regardless of whether it is non-executable structured data (`application/ld+json`) or an external file reference (`src=...`).

---

### Observation 2: `assert_html_tag_present` Regex Truncation on `>` Inside Attribute Quotes
- **File & Lines**: `tests/e2e/harness/assertions.py:154-155`
  ```python
  pattern = rf"<{re.escape(tag)}\b([^>]*)>"
  matches = list(re.finditer(pattern, html, flags=re.IGNORECASE))
  ```
- **Direct Test Failure**:
  ```python
  html = '<img alt="Comparison: A > B" src="hero.jpg" class="detail-hero-image">'
  assert_html_tag_present(html, "img", {"class": "detail-hero-image"})
  ```
  **Verbatim Error**:
  ```text
  E2EAssertionError: Tag <img> found, but matching attributes {'class': 'detail-hero-image'} were not found in matches: ['<img alt="Comparison: A >']
  ```
- **Detail**: The character class `[^>]*` encounters the literal `>` inside the quoted string `alt="Comparison: A > B"`, prematurely terminating the match at `<img alt="Comparison: A >`. All subsequent attributes (`src="hero.jpg" class="detail-hero-image"`) are discarded, causing false assertion failures.

---

### Observation 3: `assert_html_tag_present` False Positives via Loose Substring Attribute Matching
- **File & Lines**: `tests/e2e/harness/assertions.py:182-184`
  ```python
  elif actual_val != v and v not in actual_val:
      all_match = False
      break
  ```
- **Direct Test Failure (False Positive Pass)**:
  ```python
  html = '<div id="superhero" class="container">'
  # Asserting id="hero" incorrectly passes:
  assert_html_tag_present(html, "div", {"id": "hero"})  # Does NOT raise E2EAssertionError!
  ```
- **Detail**: In line 182, the condition `and v not in actual_val` checks whether the expected value `v` is a substring of `actual_val`. Because `"hero" in "superhero"` evaluates to `True`, `v not in actual_val` is `False`, preventing `all_match` from being set to `False`. This allows partial substring matches on `id`, `name`, `href`, and other strict identifiers to pass silently.

---

### Observation 4: `assert_date_is_today` Ignores Timezone Offsets in ISO Strings and Datetimes
- **File & Lines**: `tests/e2e/harness/assertions.py:287-300`
  ```python
  if ZoneInfo:
      tz = ZoneInfo(tz_name)
      today = datetime.now(tz).date()
  else:
      today = datetime.utcnow().date()

  if isinstance(date_val, datetime):
      test_date = date_val.date()
  elif isinstance(date_val, str):
      # Match YYYY-MM-DD
      m = re.search(r'\b(\d{4})-(\d{2})-(\d{2})\b', date_val)
      if not m:
          raise E2EAssertionError(f"Could not parse date from string: '{date_val}'")
      test_date = datetime.strptime(m.group(0), "%Y-%m-%d").date()
  ```
- **Direct Test Failure**:
  When current Istanbul time is `2026-11-11 00:30:00+03:00` (today is `2026-11-11`), a valid UTC timestamp for this article is `"2026-11-10T21:30:00Z"`, or a tz-aware object `datetime(2026, 11, 10, 21, 30, tzinfo=timezone.utc)`:
  ```python
  assert_date_is_today("2026-11-10T21:30:00Z")
  ```
  **Verbatim Error**:
  ```text
  E2EAssertionError: Date 2026-11-10 is not today (2026-11-11) in Europe/Istanbul
  ```
- **Detail**:
  1. The regex `r'\b(\d{4})-(\d{2})-(\d{2})\b'` blindly extracts the raw calendar digits (`2026-11-10`) from the string without interpreting the trailing UTC timezone designator (`Z` / `+00:00`).
  2. For `datetime` instances, `date_val.date()` extracts the date in the object's current timezone (UTC) rather than converting to `tz_name` (`Europe/Istanbul`).
  3. Tests running near midnight (between 21:00 and 23:59 UTC / 00:00 and 02:59 Istanbul) fail falsely.

---

## 2. Logic Chain

1. **Code Separation Intent vs. Implementation Gap (Observation 1)**:
   - `AGENTS.md` Rule 1 mandates strict code separation: no inline CSS (`style="..."` or `<style>`) and no inline JavaScript (`<script>` logic or inline event handlers).
   - Structured metadata (`<script type="application/ld+json">`) is JSON data, not executable JavaScript logic. It is mandatory for Google NewsArticle schema under Feature 28.
   - External script loading (`<script src="...">`) is standard asset inclusion and compliant with code separation, provided the `<script>` tag contains no inline JavaScript body.
   - By matching any `<script\b[^>]*>`, `assert_no_inline_styles_or_scripts` conflates data/external references with inline script logic.
   - Therefore, the assertion must inspect tag attributes: allow `type="application/ld+json"` and `src="..."`, while strictly flagging any `<script>` tag lacking both or containing an inline executable body.

2. **Regex Attribute Parsing Defect (Observation 2 & Observation 3)**:
   - HTML attributes can contain character `>` within quoted string literals (e.g. `alt="A > B"`, `title="Home > News"`).
   - Naive character-negation `[^>]*` stops at the first `>` character regardless of quoting context.
   - A quote-safe regex pattern `(?:[^>"']|"[^"]*"|'[^']*')*` properly balances quotes before terminating at the outer `>`.
   - For attribute key matching, `\b` alone does not prevent matching `data-id` when searching for `id` because `-` is a non-word boundary. Checking `(?:^|\s)` guarantees an isolated attribute key.
   - For attribute value checking, `class` represents a space-separated set of tokens where subset containment (`expected_classes.issubset(actual_classes)`) is appropriate. All other attributes (`id`, `href`, `name`, `alt`, etc.) require exact equality (`actual_val == v`). Substring check (`v not in actual_val`) is an anti-pattern.

3. **Timezone Conversion Parity (Observation 4)**:
   - The newsroom domain and editorial sentinel (`check_freshness.py`) operate under `Europe/Istanbul` time (`UTC+03:00`).
   - ISO-8601 strings emitted by external feeds, APIs, and databases frequently carry UTC designators (`Z`, `+00:00`) or fractional seconds (`.123` or `,123`).
   - In ISO-8601, `"2026-11-10T21:30:00Z"` is precisely `2026-11-11 00:30:00+03:00`.
   - To determine whether a timestamp is "today in Europe/Istanbul", the timestamp must first be converted into the target timezone (`dt.astimezone(target_tz)`), and only then evaluated via `.date() == today`.

---

## 3. Caveats

- **HTML Parsing Scope**: `assert_html_tag_present` uses enhanced regular expressions rather than a full DOM parser (e.g. `BeautifulSoup` or `lxml`). This keeps the test harness lightweight and zero-dependency (standard library only), which is an explicit architectural invariant of the E2E test harness. The quote-aware regex handles all valid HTML5 opening tag structures with single or double quotes.
- **Python Version Compatibility**: Python 3.11+ supports `"Z"` in `datetime.fromisoformat()`. For compatibility with Python 3.9/3.10, `"Z"` is normalized to `"+00:00"`, and 4-digit offsets (e.g. `+0300`) are normalized to `+03:00`.
- **Read-Only Scope**: This report defines the exact fix strategy and tested replacement code without modifying `tests/e2e/harness/assertions.py` directly, adhering to the Explorer read-only constraint.

---

## 4. Conclusion & Actionable Fix Strategy

The domain assertion utilities in `tests/e2e/harness/assertions.py` require targeted replacement of three functions:

### Fix 1: `assert_no_inline_styles_or_scripts` (`tests/e2e/harness/assertions.py:216-237`)

**Logic**:
1. Scan for `style="..."`, `<style>`, and inline `on[a-z]+=` handlers as before.
2. Scan for opening `<script ...>` tags using quote-aware matching: `r'<\s*script\b((?:[^>"\'/]|"[^"]*"|\'[^\']*\')*)/?>'`.
3. Check if the tag is JSON-LD (`type="application/ld+json"`). If so, permit.
4. Check if the tag is an external script (`src="..."`). If so, permit, but verify that any closing `</script>` does not encapsulate non-empty inline JavaScript body.
5. If neither JSON-LD nor external src, flag as `'Inline <script> tag found'`.

**Proposed Replacement Code**:
```python
def assert_no_inline_styles_or_scripts(content: str, msg: Optional[str] = None) -> None:
    """
    Strict Code Separation Mandate (AGENTS.md Rule 1):
    Assert that content contains no inline CSS (style="..." or <style>)
    and no inline JS (onclick="..." or inline <script> logic).

    Permits:
    - Structured data metadata: <script type="application/ld+json">...</script>
    - External script tags: <script src="..." ...></script> (with empty body)
    """
    violations = []
    if re.search(r'\bstyle\s*=\s*["\'][^"\']+["\']', content, flags=re.IGNORECASE):
        violations.append('Inline style attribute found (style="...")')
    if re.search(r'<\s*style\b[^>]*>', content, flags=re.IGNORECASE):
        violations.append('Inline <style> tag found')
    if re.search(r'\bon[a-z]+\s*=\s*["\'][^"\']+["\']', content, flags=re.IGNORECASE):
        violations.append('Inline event handler found (onclick="...", etc.)')

    # Match opening <script ...> tags while respecting quotes in attributes
    script_open_pattern = re.compile(
        r'<\s*script\b((?:[^>"\'/]|"[^"]*"|\'[^\']*\')*)/?>',
        re.IGNORECASE
    )
    for m in script_open_pattern.finditer(content):
        attrs = m.group(1)
        # Check if structured data (JSON-LD)
        is_json_ld = bool(re.search(
            r'\btype\s*=\s*["\']\s*application/ld\+json(?:\s*;[^"\']*)?\s*["\']',
            attrs,
            re.IGNORECASE
        ))
        # Check if external script with src attribute
        has_src = bool(re.search(
            r'\bsrc\s*=\s*["\']\s*[^"\'\s]+\s*["\']',
            attrs,
            re.IGNORECASE
        ))

        if not (is_json_ld or has_src):
            violations.append('Inline <script> tag found')
        elif has_src:
            # External script: ensure it does not contain inline executable JS body
            end_pos = m.end()
            close_m = re.search(r'<\s*/\s*script\s*>', content[end_pos:], flags=re.IGNORECASE)
            if close_m:
                body = content[end_pos : end_pos + close_m.start()].strip()
                body_clean = re.sub(r'<!--[\s\S]*?-->', '', body).strip()
                if body_clean:
                    violations.append('Inline JavaScript body found in external <script src="..."> tag')

    if violations:
        detail = "Code Separation violation(s):\n  - " + "\n  - ".join(violations)
        if msg:
            detail = f"{msg}\n{detail}"
        raise E2EAssertionError(detail)
```

---

### Fix 2: `assert_html_tag_present` (`tests/e2e/harness/assertions.py:144-195`)

**Logic**:
1. Replace `pattern = rf"<{re.escape(tag)}\b([^>]*)>"` with quote-aware pattern:
   `rf"<{re.escape(tag)}\b((?:[^>\"']|\"[^\"]*\"|'[^']*')*?)\s*/?>"`
2. Replace loose attribute search with boundary-safe pattern:
   `rf'(?:^|\s){re.escape(k)}\s*=\s*(?:"([^"]*)"|\'([^\']*)\')'`
3. Extract `actual_val` cleanly from either double or single quotes.
4. If `k.lower() == "class"`, split into sets and verify `set(v.split()).issubset(set(actual_val.split()))`.
5. For all other attributes, enforce strict equality `if actual_val != v: all_match = False; break`.

**Proposed Replacement Code**:
```python
def assert_html_tag_present(
    html: str,
    tag: str,
    attrs: Optional[Dict[str, str]] = None,
    msg: Optional[str] = None
) -> None:
    """
    Assert that an HTML snippet contains an opening tag with optional attribute values.
    Supports attributes containing '>' inside quotes and strict attribute value matching.
    """
    pattern = rf"<{re.escape(tag)}\b((?:[^>\"']|\"[^\"]*\"|'[^']*')*?)\s*/?>"
    matches = list(re.finditer(pattern, html, flags=re.IGNORECASE))
    if not matches:
        detail = f"Tag <{tag}> not found in HTML output."
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)

    if attrs:
        found_attrs = False
        for m in matches:
            attr_str = m.group(1)
            all_match = True
            for k, v in attrs.items():
                attr_pattern = rf'(?:^|\s){re.escape(k)}\s*=\s*(?:"([^"]*)"|\x27([^\x27]*)\x27)'
                attr_match = re.search(attr_pattern, attr_str, flags=re.IGNORECASE)
                if not attr_match:
                    all_match = False
                    break
                actual_val = attr_match.group(1) if attr_match.group(1) is not None else attr_match.group(2)
                if k.lower() == "class":
                    actual_classes = set(actual_val.split())
                    expected_classes = set(v.split())
                    if not expected_classes.issubset(actual_classes):
                        all_match = False
                        break
                else:
                    if actual_val != v:
                        all_match = False
                        break
            if all_match:
                found_attrs = True
                break

        if not found_attrs:
            detail = f"Tag <{tag}> found, but matching attributes {attrs} were not found in matches: {[m.group(0) for m in matches]}"
            if msg:
                detail = f"{msg}: {detail}"
            raise E2EAssertionError(detail)
```

---

### Fix 3: `assert_date_is_today` (`tests/e2e/harness/assertions.py:279-308`)

**Logic**:
1. If `date_val` is `datetime`:
   - If tz-aware (`tzinfo` present), convert to target timezone (`date_val.astimezone(target_tz).date()`).
   - If naive, extract `.date()`.
2. If `date_val` is `str`:
   - Use comprehensive ISO pattern matching `YYYY-MM-DD` with optional time `HH:MM:SS` (optional fractional seconds `.` or `,`) and optional timezone offset (`Z`, `+03:00`, `+0300`, `-05:00`).
   - If time and timezone offset are present, normalize `Z` -> `+00:00`, normalize `,` -> `.`, parse via `datetime.fromisoformat()`, and convert to `target_tz` via `.astimezone(target_tz).date()`.
   - If plain date string or naive timestamp without timezone, parse calendar date via `datetime.strptime(date_part, "%Y-%m-%d").date()`.
3. Compare resolved `test_date` against `today` in `Europe/Istanbul`.

**Proposed Replacement Code**:
```python
def assert_date_is_today(
    date_val: Union[str, datetime],
    tz_name: str = "Europe/Istanbul",
    msg: Optional[str] = None
) -> None:
    """
    Assert that date_val corresponds to today's calendar date in the given timezone.
    Supports timezone offsets on ISO date strings (+03:00, Z), fractional seconds,
    and tz-aware datetime objects, converting them to target timezone before comparison.
    """
    if ZoneInfo:
        target_tz = ZoneInfo(tz_name)
        today = datetime.now(target_tz).date()
    else:
        target_tz = None
        today = datetime.utcnow().date()

    if isinstance(date_val, datetime):
        if date_val.tzinfo is not None and target_tz is not None:
            test_date = date_val.astimezone(target_tz).date()
        else:
            test_date = date_val.date()
    elif isinstance(date_val, str):
        s = date_val.strip()
        iso_pattern = re.compile(
            r'(\d{4}-\d{2}-\d{2})'
            r'(?:[T\s](\d{2}:\d{2}:\d{2}(?:[.,]\d+)?))?'
            r'(?:(Z|[+-]\d{2}:?\d{2}))?',
            re.IGNORECASE
        )
        m = iso_pattern.search(s)
        if not m:
            detail = f"Could not parse date from string: '{date_val}'"
            if msg:
                detail = f"{msg}: {detail}"
            raise E2EAssertionError(detail)

        date_part = m.group(1)
        time_part = m.group(2)
        tz_part = m.group(3)

        if time_part and tz_part:
            time_part = time_part.replace(",", ".")
            iso_str = f"{date_part}T{time_part}"
            if tz_part.upper() == "Z":
                iso_str += "+00:00"
            elif len(tz_part) == 5 and (tz_part[0] in "+-") and ":" not in tz_part:
                iso_str += f"{tz_part[:3]}:{tz_part[3:]}"
            else:
                iso_str += tz_part

            try:
                dt = datetime.fromisoformat(iso_str)
                if target_tz is not None:
                    test_date = dt.astimezone(target_tz).date()
                else:
                    test_date = dt.date()
            except Exception as e:
                detail = f"Failed to parse ISO datetime '{iso_str}': {e}"
                if msg:
                    detail = f"{msg}: {detail}"
                raise E2EAssertionError(detail) from e
        else:
            try:
                test_date = datetime.strptime(date_part, "%Y-%m-%d").date()
            except Exception as e:
                detail = f"Failed to parse date string '{date_part}': {e}"
                if msg:
                    detail = f"{msg}: {detail}"
                raise E2EAssertionError(detail) from e
    else:
        detail = f"Unsupported date type: {type(date_val)}"
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)

    if test_date != today:
        detail = f"Date {test_date} is not today ({today}) in {tz_name}"
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)
```

---

## 5. Verification Method

To independently verify the proposed fixes against both existing tests and Challenger 2's adversarial findings:

```bash
python3 - << 'EOF'
import sys
import unittest
from datetime import datetime, timezone
from zoneinfo import ZoneInfo
import re

# Import target module and inject updated methods
import tests.e2e.harness.assertions as assertions
from tests.e2e.harness.assertions import E2EAssertionError

# [Apply the proposed replacement functions to assertions.*]
# (See Conclusion section for function bodies)

# 1. Verify JSON-LD Schema Acceptance (Feature 28)
assertions.assert_no_inline_styles_or_scripts(
    '<script type="application/ld+json">{"@context":"https://schema.org","@type":"NewsArticle"}</script>'
)
print("[PASS] JSON-LD metadata accepted without false positive")

# 2. Verify External Script Acceptance
assertions.assert_no_inline_styles_or_scripts(
    '<script src="/static/js/video-embed.js" defer></script>'
)
print("[PASS] External script accepted without false positive")

# 3. Verify Inline Script Rejection
try:
    assertions.assert_no_inline_styles_or_scripts('<script>alert("xss")</script>')
    raise AssertionError("Should have rejected inline script")
except E2EAssertionError:
    print("[PASS] Inline <script> rejected")

# 4. Verify External Script with Body Rejection
try:
    assertions.assert_no_inline_styles_or_scripts('<script src="app.js">alert(1)</script>')
    raise AssertionError("Should have rejected external script with body")
except E2EAssertionError:
    print("[PASS] External script with inline body rejected")

# 5. Verify Tag Parsing with '>' Inside Attribute Quotes
assertions.assert_html_tag_present(
    '<img alt="Comparison: A > B" src="hero.jpg" class="detail-hero-image">',
    "img",
    {"class": "detail-hero-image", "alt": "Comparison: A > B"}
)
print("[PASS] '>' inside attribute quotes handled correctly")

# 6. Verify Exact Attribute Matching (Rejection of Loose Substrings)
try:
    assertions.assert_html_tag_present('<div id="superhero">', "div", {"id": "hero"})
    raise AssertionError("Should have rejected loose substring match id='hero' against 'superhero'")
except E2EAssertionError:
    print("[PASS] Loose substring attribute match rejected")

# 7. Verify Timezone-Aware ISO Parsing and Datetime Conversion
ist_now = datetime.now(ZoneInfo("Europe/Istanbul"))
utc_now = ist_now.astimezone(timezone.utc)
assertions.assert_date_is_today(utc_now)
assertions.assert_date_is_today(utc_now.strftime("%Y-%m-%dT%H:%M:%SZ"))
print("[PASS] Timezone-aware datetime and ISO strings verified against Europe/Istanbul")

# 8. Run Full Existing Test Suite
from tests.e2e.test_harness_self_verification import TestHarnessAssertions
suite = unittest.TestLoader().loadTestsFromTestCase(TestHarnessAssertions)
runner = unittest.TextTestRunner(verbosity=1)
result = runner.run(suite)
assert result.wasSuccessful(), "Existing tests failed!"
print("[PASS] All 8 existing assertion tests in TestHarnessAssertions passed")
EOF
```

### Invalidation Conditions
- Any change that permits inline `<script>` tags without `src` or without `type="application/ld+json"` invalidates the code separation contract.
- Any change that alters the exact attribute matching behavior on non-class attributes (`actual_val != v`) back to substring containment (`v in actual_val`) invalidates the fix.
- Any change that fails to convert UTC timestamps to `Europe/Istanbul` prior to calendar date extraction invalidates timezone safety.
