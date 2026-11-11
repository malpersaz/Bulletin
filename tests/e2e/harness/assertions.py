"""
Domain-specific assertion utilities for Amed Framework Bulletin E2E testing.
Covers exit codes, JSON-RPC envelopes, HTML semantics, SEO rules, and code separation.
"""

from __future__ import annotations

import json
import re
from datetime import datetime
from typing import Any, Dict, List, Optional, Union
try:
    from zoneinfo import ZoneInfo
except ImportError:
    # Python 3.8 backport compatibility if needed
    ZoneInfo = None  # type: ignore


class E2EAssertionError(AssertionError):
    """Specific assertion error with contextual diagnostic messaging."""
    pass


def assert_exit_code(
    result: Any,
    expected_code: Union[int, List[int]] = 0,
    msg: Optional[str] = None
) -> None:
    """
    Assert that a ProcessResult or exit code matches the expected code(s).
    """
    actual_code = getattr(result, "exit_code", result)
    if isinstance(expected_code, int):
        expected_list = [expected_code]
    else:
        expected_list = list(expected_code)

    if actual_code not in expected_list:
        stdout = getattr(result, "stdout", "")
        stderr = getattr(result, "stderr", "")
        detail = f"Expected exit code in {expected_list}, got {actual_code}."
        if stdout:
            detail += f"\n--- STDOUT ---\n{stdout[:1000]}"
        if stderr:
            detail += f"\n--- STDERR ---\n{stderr[:1000]}"
        if msg:
            detail = f"{msg}\n{detail}"
        raise E2EAssertionError(detail)


def assert_json_envelope(
    data: Union[str, bytes, Dict[str, Any]],
    required_keys: Optional[List[str]] = None,
    msg: Optional[str] = None
) -> Dict[str, Any]:
    """
    Assert that the payload is valid JSON and contains required keys.
    Returns the parsed dictionary.
    """
    if isinstance(data, (str, bytes)):
        try:
            parsed = json.loads(data)
        except Exception as e:
            detail = f"Failed to parse JSON: {e}. Raw data: {str(data)[:500]}"
            if msg:
                detail = f"{msg}: {detail}"
            raise E2EAssertionError(detail) from e
    elif isinstance(data, dict):
        parsed = data
    else:
        raise E2EAssertionError(f"Expected str, bytes, or dict, got {type(data)}")

    if required_keys:
        missing = [k for k in required_keys if k not in parsed]
        if missing:
            detail = f"JSON payload missing required keys: {missing}. Present: {list(parsed.keys())}"
            if msg:
                detail = f"{msg}: {detail}"
            raise E2EAssertionError(detail)

    return parsed


def assert_mcp_success(
    response: Union[str, bytes, Dict[str, Any]],
    msg: Optional[str] = None
) -> Dict[str, Any]:
    """
    Assert that an MCP JSON-RPC response represents success (contains 'result', no 'error').
    Returns the 'result' object.
    """
    parsed = assert_json_envelope(response, msg=msg)
    if "error" in parsed and parsed["error"] is not None:
        detail = f"MCP response returned error: {parsed['error']}"
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)

    if "result" not in parsed:
        detail = f"MCP response missing 'result' field: {parsed}"
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)

    return parsed["result"]


def assert_mcp_error(
    response: Union[str, bytes, Dict[str, Any]],
    expected_code: Optional[int] = None,
    message_contains: Optional[str] = None,
    msg: Optional[str] = None
) -> Dict[str, Any]:
    """
    Assert that an MCP JSON-RPC response represents an error.
    Optionally checks error code and message substring.
    Returns the 'error' dictionary.
    """
    parsed = assert_json_envelope(response, msg=msg)
    if "error" not in parsed or not parsed["error"]:
        detail = f"Expected MCP error response, got success: {parsed}"
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)

    error = parsed["error"]
    if expected_code is not None and error.get("code") != expected_code:
        detail = f"Expected MCP error code {expected_code}, got {error.get('code')} ({error})"
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)

    if message_contains is not None:
        err_msg = str(error.get("message", ""))
        if message_contains.lower() not in err_msg.lower():
            detail = f"Expected MCP error message containing '{message_contains}', got '{err_msg}'"
            if msg:
                detail = f"{msg}: {detail}"
            raise E2EAssertionError(detail)

    return error


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


def assert_html_tag_absent(
    html: str,
    tag: str,
    attrs: Optional[Dict[str, str]] = None,
    msg: Optional[str] = None
) -> None:
    """
    Assert that an HTML snippet does NOT contain a tag with optional attribute values.
    """
    try:
        assert_html_tag_present(html, tag, attrs=attrs)
        tag_desc = f"<{tag} {attrs or ''}>"
        detail = f"Tag {tag_desc} was found in HTML, but was expected to be absent."
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)
    except E2EAssertionError:
        pass


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


def assert_wikipedia_slug(slug: str, msg: Optional[str] = None) -> None:
    """
    Assert slug satisfies Wikipedia-style URL generation parity (AGENTS.md Rule 9):
    - Spaces replaced with underscores (not hyphens).
    - Preserves Kurdish / Unicode characters (ç, ê, î, ş, û).
    - Dot-above U+0307 stripped.
    """
    if "\u0307" in slug:
        detail = f"Slug contains forbidden combining dot above U+0307: '{slug}'"
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)

    if " " in slug:
        detail = f"Slug contains unreplaced whitespace: '{slug}'"
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)

    if slug.startswith("/") or slug.endswith("/"):
        detail = f"Slug has leading/trailing slashes: '{slug}'"
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)


def assert_normalized_number(text: str, number: Union[int, float, str], msg: Optional[str] = None) -> None:
    """
    Assert that the number appears as a distinct word/token in the text,
    not merely as an arbitrary substring inside another larger number or string.
    """
    num_str = str(number).strip()
    pattern = rf'(?<!\d){re.escape(num_str)}(?!\d)'
    if not re.search(pattern, text):
        detail = f"Number '{num_str}' not found as standalone token in text: '{text[:200]}...'"
        if msg:
            detail = f"{msg}: {detail}"
        raise E2EAssertionError(detail)


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
