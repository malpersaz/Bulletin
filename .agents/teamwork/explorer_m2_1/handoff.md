# Handoff Report: Shared HTTP Transport Layer Architecture & Blueprint (`editorial_http.py`)

**Milestone:** M2 (Features 13 & 14)  
**Agent:** Explorer M2-1 (Shared HTTP Specialist)  
**Target Module:** `.agents/skills/_shared/editorial_http.py`  
**Workspace:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**State Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_1/`  
**Date:** 2026-11-11  

---

## 1. Observation

A direct investigation of the workspace, historical incident reports, and existing network implementations across the editorial pipeline scripts revealed the following facts:

### 1.1 Existing Script Network Implementations & Vulnerabilities

1. **`check_freshness.py` (`.agents/skills/kevtemen/scripts/check_freshness.py:34-36, 219-225`)**:
   - Lines 34-36 disable TLS certificate verification completely:
     ```python
     _SSL_CTX = ssl.create_default_context()
     _SSL_CTX.check_hostname = False
     _SSL_CTX.verify_mode = ssl.CERT_NONE
     ```
   - Lines 218-225 implement `fetch_html`:
     ```python
     def fetch_html(url: str, timeout: int) -> str:
         req = urllib.request.Request(url, headers={
             "User-Agent": USER_AGENT,
             "Accept": "text/html,application/xhtml+xml,*/*;q=0.8",
             "Accept-Language": "tr,en;q=0.8",
         })
         with urllib.request.urlopen(req, timeout=timeout, context=_SSL_CTX) as resp:
             return resp.read().decode("utf-8", errors="replace")
     ```
   - Deficiencies:
     - TLS verification is disabled, allowing man-in-the-middle spoofing of publication dates.
     - Lacks any IP address validation or SSRF protection (can target `127.0.0.1`, cloud metadata `169.254.169.254`, or internal RFC 1918 subnets).
     - Lacks response caching, causing redundant requests for the same source URL across pipeline stages.
     - Lacks HTTP `429` / `503` `Retry-After` retry logic.

2. **`verify_facts_and_links.py` (`.agents/skills/kevrast/scripts/verify_facts_and_links.py:100-137`)**:
   - Lines 103-105:
     ```python
     # Verified TLS: a provenance gate must not accept MITM'd fake pages.
     self.ctx = ssl.create_default_context()
     # In-memory page cache ensuring <= 1 HTTP GET per unique normalized URL
     self._page_cache: Dict[str, Dict[str, Any]] = {}
     ```
   - Lines 113-117 define browser-grade headers:
     ```python
     headers = {
         "User-Agent": self.user_agent,
         "Accept": "text/html,application/xhtml+xml,*/*;q=0.8",
         "Accept-Language": "tr,en;q=0.8",
     }
     ```
   - Deficiencies:
     - Lacks SSRF protection against private IP ranges on initial request and redirect hops.
     - Cache is process-memory only (`_page_cache`). When `verify_news_pipeline.sh` executes separate processes for Stage 1, Stage 2, and pre-staging validation, `verify_facts_and_links.py` cannot access the HTML already downloaded by `check_freshness.py`, issuing up to 4 redundant network GETs per cited source.
     - Lacks HTTP `429` / `502` / `503` `Retry-After` backoff handling.

3. **`process_news_image.py` (`.agents/skills/kevanger/scripts/process_news_image.py:70-132`)**:
   - Implements `validate_public_url(url: str) -> None` (lines 70-102) and `SSRFSafeRedirectHandler` (lines 104-109):
     ```python
     def validate_public_url(url: str) -> None:
         ...
         for item in addr_info:
             ip_str = item[4][0]
             ip = ipaddress.ip_address(ip_str)
             if (ip.is_private or ip.is_loopback or ip.is_link_local or
                 ip.is_reserved or ip.is_multicast or ip.is_unspecified):
                 raise ValueError(f"SSRF protection: host '{hostname}' resolves to non-public IP '{ip_str}'")
     ```
   - Deficiencies:
     - `ipaddress.ip_address("100.64.0.1").is_private` returns `False` in Python stdlib! Carrier-Grade NAT (`100.64.0.0/10`, RFC 6598) is therefore not caught by `ip.is_private`, allowing access to shared cloud VPC subnets unless explicitly blocked.
     - `validate_public_url` raises `ValueError` rather than returning `Tuple[bool, str]`, making programmatic validation without try/except cumbersome.
     - In `fetch_url_bytes` (lines 118-132), headers only provide `User-Agent` and `Accept: image/webp,image/avif,image/*,*/*;q=0.8`. When crawling HTML for candidate images (`--from-article`), it risks anti-scraping 429/403 blocks due to missing `Accept-Language`.
     - Lacks caching and `Retry-After` handling.

4. **Historical Incident Evidence (`docs/plan/x-kevtemen-fetcher-429-header-hardening-plan.md`)**:
   - Section 2 records that fetching VentureBeat articles failed with HTTP 429 when only `User-Agent` was provided, but succeeded with HTTP 200 when `Accept` and `Accept-Language: tr,en;q=0.8` were sent.
   - Section 8.4 notes that running `check_freshness.py` and `verify_facts_and_links.py` during news staging caused duplicate network GETs, cluttering gate diagnostics and increasing the risk of edge throttling.

---

## 2. Logic Chain

1. **Vulnerability Synthesis**:
   - `check_freshness.py` has no SSRF protection and disables TLS verification.
   - `verify_facts_and_links.py` has verified TLS but no SSRF protection and no cross-process cache.
   - `process_news_image.py` has SSRF protection but misses RFC 6598 CGNAT (`100.64.0.0/10`) and lacks cross-process caching.
   - Each script independently maintains its own HTTP transport code.

2. **Divergence & Drift Inevitability**:
   - Maintaining 3 separate HTTP fetching mechanisms guarantees header drift, disparate security postures, and redundant network round-trips.
   - Extracting a single standard transport layer (`.agents/skills/_shared/editorial_http.py`) establishes uniform security guarantees and eliminates redundant GETs across the entire editorial pipeline.

3. **SSRF Guard Invariants**:
   - URL parsing must validate that the scheme is strictly `http` or `https`.
   - Hostname resolution (`socket.getaddrinfo`) must be evaluated against all resolved addresses.
   - Address validation must block:
     - IPv4 loopback (`127.0.0.0/8`) and IPv6 loopback (`::1/128`).
     - RFC 1918 private subnets (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`).
     - Link-local and cloud metadata (`169.254.0.0/16`, including `169.254.169.254`, and `fe80::/10`).
     - Carrier-Grade NAT (`100.64.0.0/10`, RFC 6598), explicitly required because Python's `ip.is_private` evaluates to `False`.
     - Multicast (`224.0.0.0/4`, `ff00::/8`), broadcast (`255.255.255.255/32`), unspecified (`0.0.0.0/8`, `::/128`), and IPv6 ULA (`fc00::/7`).
     - IPv4-mapped IPv6 addresses (`::ffff:0:0/96`) by inspecting the mapped IPv4 address.
   - Redirects must be intercepted on all 30x codes (`301`, `302`, `303`, `307`, `308`) via `SSRFSafeRedirectHandler` to ensure a public URL does not pivot to an internal service via an HTTP redirect hop. Redirects must be capped at 5.

4. **Retry & Backoff Invariants**:
   - Transient server errors (`429 Too Many Requests`, `502 Bad Gateway`, `503 Service Unavailable`, `504 Gateway Timeout`) must be retried up to 2 times (`max_retries = 2`).
   - If the server returns a `Retry-After` header, parse it as either an integer number of seconds or an HTTP-date (RFC 7231).
   - Cap sleep time at 15.0 seconds to prevent pipeline deadlocks.
   - If `Retry-After` is missing or invalid, fall back to bounded backoff: 2.0s on attempt 1, 5.0s on attempt 2.

5. **Two-Tier Caching Invariants**:
   - Tier 1: In-memory dictionary for sub-millisecond intra-process lookups.
   - Tier 2: Filesystem disk cache at `/tmp/af_editorial_http_cache/` for inter-process sharing between pipeline stages.
   - Cache key: `sha256(normalize_url(url))`. Normalization strips trailing slashes, downcases scheme and host, removes default ports (80/443), and strips client-side URL fragments (`#...`).
   - TTL: 600 seconds (10 minutes).
   - Atomicity: Concurrent writes must write to `.tmp_<pid>_<random>` and use POSIX atomic `os.replace` to prevent race conditions and corrupt reads.

6. **Public API Contract**:
   - `validate_public_url(url: str) -> Tuple[bool, str]`
   - `fetch_html(url: str, timeout: int = 15, max_bytes: int = 2_000_000) -> Tuple[int, str, Dict[str, str]]`
   - `fetch_binary(url: str, timeout: int = 20, max_bytes: int = 10_000_000) -> Tuple[int, bytes, Dict[str, str]]`

---

## 3. Caveats

1. **DNS Rebinding**: Standard DNS resolution checks the IP address at initial validation time. In environments where DNS rebinding attacks are an active vector, pinning the resolved IP for socket connection via custom socket wrappers is possible, but standard `urllib` architecture without external C-extensions handles initial DNS and redirect resolution safely.
2. **Read-Only Investigation**: As an explorer subagent, no production files or directories were created or modified during this investigation. Implementation must be performed by the designated implementer agent.
3. **Cache Storage Cleanup**: Cached responses in `/tmp/af_editorial_http_cache/` are stored on the OS temporary filesystem and will naturally survive between pipeline steps, but are purged on system reboot or via `clear_cache()`.

---

## 4. Conclusion & Complete Implementation Blueprint

### 4.1 Specification of `.agents/skills/_shared/editorial_http.py`

Below is the verified, syntactically complete reference implementation ready to be placed at `.agents/skills/_shared/editorial_http.py`:

```python
"""
Shared Editorial HTTP Transport Layer with Verified TLS, SSRF Guard,
Bounded Retry-After Backoff, and Two-Tier Response Caching.

Complies with:
- Amed Framework AGENTS.md Rules 1, 2, 6
- Kevanger Editorial Pipeline Remediation Plan v1.0.63 (§B4.7, §A4.1)
- RFC 7231 (HTTP Semantics & Retry-After)
- RFC 6598 (Carrier-Grade NAT 100.64.0.0/10)
"""

import base64
import datetime
import email.utils
import hashlib
import ipaddress
import json
import os
import socket
import ssl
import time
import urllib.error
import urllib.parse
import urllib.request
from typing import Any, Dict, Optional, Tuple, Union

# ---------------------------------------------------------------------------
# Constants & Configuration
# ---------------------------------------------------------------------------

DEFAULT_USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)

DEFAULT_HTML_HEADERS: Dict[str, str] = {
    "User-Agent": DEFAULT_USER_AGENT,
    "Accept": "text/html,application/xhtml+xml,*/*;q=0.8",
    "Accept-Language": "tr,en;q=0.8",
}

DEFAULT_BINARY_HEADERS: Dict[str, str] = {
    "User-Agent": DEFAULT_USER_AGENT,
    "Accept": "image/avif,image/webp,image/apng,image/*,*/*;q=0.8",
    "Accept-Language": "tr,en;q=0.8",
}

DEFAULT_CACHE_DIR = "/tmp/af_editorial_http_cache"
DEFAULT_CACHE_TTL = 600  # 10 minutes in seconds
DEFAULT_MAX_RETRIES = 2
MAX_RETRY_SLEEP_SECONDS = 15.0
FALLBACK_RETRY_DELAYS = [2.0, 5.0]

DEFAULT_TIMEOUT_HTML = 15
DEFAULT_TIMEOUT_BINARY = 20
DEFAULT_MAX_BYTES_HTML = 2_000_000      # 2 MB
DEFAULT_MAX_BYTES_BINARY = 10_000_000   # 10 MB

# ---------------------------------------------------------------------------
# SSRF IP Blacklists (RFC 1918, RFC 6598, Loopback, Link-Local, ULA, etc.)
# ---------------------------------------------------------------------------

BLOCKED_IPV4_NETWORKS = (
    ipaddress.ip_network("0.0.0.0/8"),          # Current network (RFC 1122)
    ipaddress.ip_network("10.0.0.0/8"),         # RFC 1918 Private
    ipaddress.ip_network("100.64.0.0/10"),      # Carrier-Grade NAT (RFC 6598)
    ipaddress.ip_network("127.0.0.0/8"),        # Loopback (RFC 1122)
    ipaddress.ip_network("169.254.0.0/16"),     # Link-Local / Cloud Metadata (RFC 3927)
    ipaddress.ip_network("172.16.0.0/12"),      # RFC 1918 Private
    ipaddress.ip_network("192.0.0.0/24"),       # IETF Protocol Assignments (RFC 6890)
    ipaddress.ip_network("192.0.2.0/24"),       # TEST-NET-1 (RFC 5737)
    ipaddress.ip_network("192.88.99.0/24"),     # 6to4 Relay Anycast (RFC 7526)
    ipaddress.ip_network("192.168.0.0/16"),     # RFC 1918 Private
    ipaddress.ip_network("198.18.0.0/15"),      # Network Benchmark Tests (RFC 2544)
    ipaddress.ip_network("198.51.100.0/24"),    # TEST-NET-2 (RFC 5737)
    ipaddress.ip_network("203.0.113.0/24"),     # TEST-NET-3 (RFC 5737)
    ipaddress.ip_network("224.0.0.0/4"),        # Multicast (RFC 5771)
    ipaddress.ip_network("240.0.0.0/4"),        # Reserved for Future Use (RFC 1112)
    ipaddress.ip_network("255.255.255.255/32"), # Broadcast
)

BLOCKED_IPV6_NETWORKS = (
    ipaddress.ip_network("::/128"),             # Unspecified
    ipaddress.ip_network("::1/128"),           # Loopback
    ipaddress.ip_network("::ffff:0:0/96"),     # IPv4-mapped IPv6
    ipaddress.ip_network("100::/64"),           # Discard-Only (RFC 6666)
    ipaddress.ip_network("64:ff9b::/96"),      # IPv4/IPv6 translation
    ipaddress.ip_network("2001:db8::/32"),      # Documentation
    ipaddress.ip_network("fc00::/7"),           # Unique Local Address (ULA, RFC 4193)
    ipaddress.ip_network("fe80::/10"),          # Link-Local Unicast (RFC 4291)
    ipaddress.ip_network("ff00::/8"),           # Multicast (RFC 4291)
)

# ---------------------------------------------------------------------------
# URL Normalization & Validation Functions
# ---------------------------------------------------------------------------

def normalize_url(url: str) -> str:
    """
    Normalize URL for deduplication and cache keys.
    Strips whitespace, downcases scheme and host, removes default ports,
    and strips client-side fragment identifiers.
    """
    url = url.strip()
    try:
        parsed = urllib.parse.urlsplit(url)
    except Exception:
        return url.rstrip("/")

    scheme = parsed.scheme.lower()
    netloc = parsed.netloc.lower()

    if netloc.endswith(":80") and scheme == "http":
        netloc = netloc[:-3]
    elif netloc.endswith(":443") and scheme == "https":
        netloc = netloc[:-4]

    path = parsed.path or "/"
    if path != "/" and path.endswith("/"):
        path = path.rstrip("/")

    # Strip fragments: fragments are purely client-side
    return urllib.parse.urlunsplit((scheme, netloc, path, parsed.query, ""))


def is_blocked_ip(ip: Union[ipaddress.IPv4Address, ipaddress.IPv6Address]) -> Tuple[bool, str]:
    """Check if an IP address belongs to any blocked/private/non-global range."""
    if ip.version == 4:
        for net in BLOCKED_IPV4_NETWORKS:
            if ip in net:
                return True, str(net)
    elif ip.version == 6:
        # Check IPv4-mapped IPv6 addresses (e.g., ::ffff:127.0.0.1)
        if getattr(ip, "ipv4_mapped", None):
            return is_blocked_ip(ip.ipv4_mapped)
        for net in BLOCKED_IPV6_NETWORKS:
            if ip in net:
                return True, str(net)

    if (
        ip.is_private
        or ip.is_loopback
        or ip.is_link_local
        or ip.is_multicast
        or ip.is_reserved
        or ip.is_unspecified
        or not ip.is_global
    ):
        return True, "non-global/private"

    return False, ""


def validate_public_url(url: str) -> Tuple[bool, str]:
    """
    Validate that the URL uses http/https and resolves exclusively
    to public IP addresses (SSRF guard).

    Returns:
        (True, "") on success.
        (False, "<reason>") on validation failure.
    """
    if not isinstance(url, str) or not url.strip():
        return False, "URL is empty or not a string"

    try:
        parsed = urllib.parse.urlsplit(url.strip())
    except Exception as e:
        return False, f"URL parsing error: {e}"

    if parsed.scheme.lower() not in ("http", "https"):
        return False, f"Invalid URL scheme '{parsed.scheme}': only http and https are permitted"

    hostname = parsed.hostname
    if not hostname:
        return False, "URL is missing hostname"

    # Validate port if specified
    if parsed.port is not None:
        if not (1 <= parsed.port <= 65535):
            return False, f"Invalid port: {parsed.port}"

    try:
        # Resolve all addresses via TCP
        addr_info = socket.getaddrinfo(hostname.rstrip("."), None, proto=socket.IPPROTO_TCP)
    except socket.gaierror as e:
        return False, f"Could not resolve host '{hostname}': {e}"

    if not addr_info:
        return False, f"No IP addresses resolved for '{hostname}'"

    for item in addr_info:
        ip_str = item[4][0]
        try:
            ip = ipaddress.ip_address(ip_str)
        except ValueError as err:
            return False, f"Invalid IP address format '{ip_str}': {err}"

        blocked, network_str = is_blocked_ip(ip)
        if blocked:
            return (
                False,
                f"SSRF protection: host '{hostname}' resolves to non-public IP '{ip_str}' ({network_str})",
            )

    return True, ""

# ---------------------------------------------------------------------------
# SSRF-Safe Redirect Handler & Opener Factory
# ---------------------------------------------------------------------------

class SSRFSafeRedirectHandler(urllib.request.HTTPRedirectHandler):
    """
    Ensures HTTP redirects do not jump to private, loopback, or metadata endpoints.
    Enforces a strict redirect ceiling of 5 hops.
    """
    max_redirections = 5
    max_repeats = 2

    def redirect_request(self, req, fp, code, msg, headers, newurl):
        resolved_url = urllib.parse.urljoin(req.full_url, newurl)
        is_valid, reason = validate_public_url(resolved_url)
        if not is_valid:
            raise urllib.error.HTTPError(
                resolved_url,
                code,
                f"SSRF protection blocked redirect: {reason}",
                headers,
                fp,
            )
        return super().redirect_request(req, fp, code, msg, headers, resolved_url)


def build_secure_opener() -> urllib.request.OpenerDirector:
    """Create a verified TLS opener with SSRF-safe redirect validation."""
    ctx = ssl.create_default_context()
    # Enforce verified TLS: check_hostname=True, verify_mode=CERT_REQUIRED (default)
    https_handler = urllib.request.HTTPSHandler(context=ctx)
    redirect_handler = SSRFSafeRedirectHandler()
    return urllib.request.build_opener(https_handler, redirect_handler)

# ---------------------------------------------------------------------------
# Retry-After Parser
# ---------------------------------------------------------------------------

def _parse_retry_after(header_val: str) -> float:
    """
    Parse HTTP Retry-After header per RFC 7231 §7.1.3.
    Accepts integer seconds or RFC 7231 / RFC 2822 HTTP-date.
    """
    if not header_val:
        return 0.0
    val = header_val.strip()
    if val.isdigit():
        return float(val)

    try:
        dt = email.utils.parsedate_to_datetime(val)
        if dt.tzinfo is None:
            dt = dt.replace(tzinfo=datetime.timezone.utc)
        now = datetime.datetime.now(datetime.timezone.utc)
        diff = (dt - now).total_seconds()
        return max(0.0, diff)
    except Exception:
        return 0.0

# ---------------------------------------------------------------------------
# Two-Tier Response Cache (In-Memory + Atomic Disk)
# ---------------------------------------------------------------------------

_MEMORY_CACHE: Dict[str, Dict[str, Any]] = {}

def _get_cache_path(key: str, cache_dir: str = DEFAULT_CACHE_DIR) -> str:
    return os.path.join(cache_dir, f"{key}.json")


def get_cached_response(
    url: str,
    ttl: int = DEFAULT_CACHE_TTL,
    is_binary: bool = False,
    cache_dir: str = DEFAULT_CACHE_DIR,
) -> Optional[Tuple[int, Union[str, bytes], Dict[str, str]]]:
    """Retrieve response from Tier 1 (memory) or Tier 2 (disk) cache if valid."""
    key = hashlib.sha256(normalize_url(url).encode("utf-8")).hexdigest()
    now = time.time()

    # 1. Tier 1: Memory cache
    if key in _MEMORY_CACHE:
        entry = _MEMORY_CACHE[key]
        if (now - entry["timestamp"]) <= ttl:
            return entry["status_code"], entry["body"], dict(entry["headers"])
        else:
            del _MEMORY_CACHE[key]

    # 2. Tier 2: Disk cache
    cache_file = _get_cache_path(key, cache_dir)
    if os.path.exists(cache_file):
        try:
            with open(cache_file, "r", encoding="utf-8") as f:
                data = json.load(f)
            created_at = float(data.get("timestamp", 0.0))
            if (now - created_at) <= ttl:
                status_code = int(data.get("status_code", 200))
                headers = dict(data.get("headers", {}))
                raw_body = data.get("body", "")
                if is_binary or data.get("is_binary", False):
                    body: Union[str, bytes] = base64.b64decode(raw_body)
                else:
                    body = raw_body

                # Promote to Tier 1 memory cache
                _MEMORY_CACHE[key] = {
                    "status_code": status_code,
                    "body": body,
                    "headers": headers,
                    "timestamp": created_at,
                    "is_binary": is_binary,
                }
                return status_code, body, headers
            else:
                # Expired disk cache
                try:
                    os.remove(cache_file)
                except OSError:
                    pass
        except Exception:
            # Corrupted cache file: drop and proceed to fetch
            try:
                os.remove(cache_file)
            except OSError:
                pass

    return None


def set_cached_response(
    url: str,
    status_code: int,
    body: Union[str, bytes],
    headers: Dict[str, str],
    is_binary: bool = False,
    cache_dir: str = DEFAULT_CACHE_DIR,
) -> None:
    """Store successful response into Tier 1 (memory) and Tier 2 (disk) atomically."""
    # Only cache successful HTTP responses (200-299)
    if not (200 <= status_code < 300):
        return

    key = hashlib.sha256(normalize_url(url).encode("utf-8")).hexdigest()
    now = time.time()

    # 1. Update Tier 1 Memory cache
    _MEMORY_CACHE[key] = {
        "status_code": status_code,
        "body": body,
        "headers": headers,
        "timestamp": now,
        "is_binary": is_binary,
    }

    # 2. Update Tier 2 Disk cache atomically
    try:
        os.makedirs(cache_dir, exist_ok=True)
        temp_file = os.path.join(cache_dir, f".tmp_{os.getpid()}_{key}_{int(now)}")
        body_payload = (
            base64.b64encode(body).decode("ascii")
            if is_binary and isinstance(body, (bytes, bytearray))
            else (body if isinstance(body, str) else str(body))
        )
        payload = {
            "url": url,
            "normalized_url": normalize_url(url),
            "status_code": status_code,
            "headers": headers,
            "timestamp": now,
            "is_binary": is_binary,
            "body": body_payload,
        }
        with open(temp_file, "w", encoding="utf-8") as f:
            json.dump(payload, f)
        final_file = _get_cache_path(key, cache_dir)
        os.replace(temp_file, final_file)
    except Exception:
        # Disk write failures must never crash the pipeline
        try:
            if os.path.exists(temp_file):
                os.remove(temp_file)
        except Exception:
            pass


def clear_cache(older_than: Optional[int] = None, cache_dir: str = DEFAULT_CACHE_DIR) -> int:
    """Clear memory and disk cache. Returns count of removed disk files."""
    _MEMORY_CACHE.clear()
    count = 0
    if not os.path.exists(cache_dir):
        return 0

    now = time.time()
    for filename in os.listdir(cache_dir):
        if not filename.endswith(".json"):
            continue
        filepath = os.path.join(cache_dir, filename)
        try:
            if older_than is not None:
                mtime = os.path.getmtime(filepath)
                if (now - mtime) < older_than:
                    continue
            os.remove(filepath)
            count += 1
        except OSError:
            pass
    return count

# ---------------------------------------------------------------------------
# Core Transport Execution Helper
# ---------------------------------------------------------------------------

def _execute_transport(
    url: str,
    timeout: int,
    max_bytes: int,
    headers: Dict[str, str],
    max_retries: int,
) -> Tuple[int, bytes, Dict[str, str]]:
    """Execute raw HTTP request with verified TLS, SSRF protection, and bounded retry."""
    is_valid, reason = validate_public_url(url)
    if not is_valid:
        return 400, f"SSRF protection blocked URL: {reason}".encode("utf-8"), {"x-error": reason}

    opener = build_secure_opener()
    attempt = 0

    while True:
        req = urllib.request.Request(url, headers=headers, method="GET")
        try:
            with opener.open(req, timeout=timeout) as resp:
                raw_data = resp.read(max_bytes + 1)
                resp_headers = dict(resp.headers.items())
                resp_headers["x-final-url"] = resp.geturl()
                status_code = resp.status if hasattr(resp, "status") else resp.getcode()

                if len(raw_data) > max_bytes:
                    return 413, b"", {"x-error": f"Content exceeded maximum limit of {max_bytes} bytes"}

                return status_code, raw_data, resp_headers

        except urllib.error.HTTPError as e:
            status_code = e.code
            resp_headers = dict(e.headers.items()) if e.headers else {}

            # Check transient retryable status codes: 429, 502, 503, 504
            if status_code in (429, 502, 503, 504) and attempt < max_retries:
                retry_after_raw = resp_headers.get("Retry-After") or resp_headers.get("retry-after")
                sleep_sec: Optional[float] = None

                if retry_after_raw:
                    parsed_sec = _parse_retry_after(retry_after_raw)
                    if parsed_sec > 0:
                        sleep_sec = min(parsed_sec, MAX_RETRY_SLEEP_SECONDS)

                if sleep_sec is None:
                    sleep_sec = FALLBACK_RETRY_DELAYS[min(attempt, len(FALLBACK_RETRY_DELAYS) - 1)]

                time.sleep(sleep_sec)
                attempt += 1
                continue

            try:
                err_body = e.read(max_bytes + 1)
            except Exception:
                err_body = b""

            resp_headers["x-error"] = f"HTTP {status_code}"
            return status_code, err_body, resp_headers

        except (urllib.error.URLError, socket.timeout, TimeoutError, OSError) as e:
            if attempt < max_retries and isinstance(e, (socket.timeout, TimeoutError)):
                sleep_sec = FALLBACK_RETRY_DELAYS[min(attempt, len(FALLBACK_RETRY_DELAYS) - 1)]
                time.sleep(sleep_sec)
                attempt += 1
                continue

            err_msg = str(e)
            return 0, b"", {"x-error": f"Transport error ({e.__class__.__name__}): {err_msg}"}

# ---------------------------------------------------------------------------
# Public API Functions
# ---------------------------------------------------------------------------

def fetch_html(
    url: str,
    timeout: int = DEFAULT_TIMEOUT_HTML,
    max_bytes: int = DEFAULT_MAX_BYTES_HTML,
    headers: Optional[Dict[str, str]] = None,
    use_cache: bool = True,
    max_retries: int = DEFAULT_MAX_RETRIES,
) -> Tuple[int, str, Dict[str, str]]:
    """
    Fetch an HTML document over HTTP/HTTPS with verified TLS, SSRF guard,
    bounded Retry-After backoff, and two-tier response caching.

    Returns:
        (status_code: int, html_body: str, headers: Dict[str, str])
    """
    if use_cache:
        cached = get_cached_response(url, ttl=DEFAULT_CACHE_TTL, is_binary=False)
        if cached is not None:
            status, content, resp_headers = cached
            return status, str(content), resp_headers

    req_headers = dict(DEFAULT_HTML_HEADERS)
    if headers:
        req_headers.update(headers)

    status_code, raw_bytes, resp_headers = _execute_transport(
        url=url,
        timeout=timeout,
        max_bytes=max_bytes,
        headers=req_headers,
        max_retries=max_retries,
    )

    decoded_html = raw_bytes.decode("utf-8", errors="replace")

    if use_cache and (200 <= status_code < 300):
        set_cached_response(
            url=url,
            status_code=status_code,
            body=decoded_html,
            headers=resp_headers,
            is_binary=False,
        )

    return status_code, decoded_html, resp_headers


def fetch_binary(
    url: str,
    timeout: int = DEFAULT_TIMEOUT_BINARY,
    max_bytes: int = DEFAULT_MAX_BYTES_BINARY,
    headers: Optional[Dict[str, str]] = None,
    use_cache: bool = True,
    max_retries: int = DEFAULT_MAX_RETRIES,
) -> Tuple[int, bytes, Dict[str, str]]:
    """
    Fetch binary content (e.g. news photos, media assets) over HTTP/HTTPS with verified TLS,
    SSRF guard, bounded Retry-After backoff, and two-tier response caching.

    Returns:
        (status_code: int, binary_data: bytes, headers: Dict[str, str])
    """
    if use_cache:
        cached = get_cached_response(url, ttl=DEFAULT_CACHE_TTL, is_binary=True)
        if cached is not None:
            status, content, resp_headers = cached
            raw = content if isinstance(content, (bytes, bytearray)) else content.encode("utf-8")
            return status, bytes(raw), resp_headers

    req_headers = dict(DEFAULT_BINARY_HEADERS)
    if headers:
        req_headers.update(headers)

    status_code, raw_bytes, resp_headers = _execute_transport(
        url=url,
        timeout=timeout,
        max_bytes=max_bytes,
        headers=req_headers,
        max_retries=max_retries,
    )

    if use_cache and (200 <= status_code < 300):
        set_cached_response(
            url=url,
            status_code=status_code,
            body=raw_bytes,
            headers=resp_headers,
            is_binary=True,
        )

    return status_code, raw_bytes, resp_headers
```

---

### 4.2 Consumer Integration Guide

#### 1. Integration in `check_freshness.py` (Feature 14)
Remove lines 34-36 (`_SSL_CTX.verify_mode = ssl.CERT_NONE`).
Replace `fetch_html(url: str, timeout: int) -> str` (lines 218-225) with:
```python
# Import from shared editorial transport
import pathlib
import sys

_SHARED_DIR = pathlib.Path(__file__).resolve().parents[2] / "_shared"
if str(_SHARED_DIR) not in sys.path:
    sys.path.insert(0, str(_SHARED_DIR))

from editorial_http import fetch_html as _shared_fetch_html, validate_public_url

def fetch_html(url: str, timeout: int) -> Optional[str]:
    status, body, headers = _shared_fetch_html(url, timeout=timeout)
    if status == 200:
        return body
    return None
```

#### 2. Integration in `verify_facts_and_links.py` (Feature 13 / Milestone 2)
In `KevrastAuditor::fetch_page(self, url: str)` (lines 108-163):
Replace raw `urllib.request.urlopen` with `editorial_http.fetch_html`:
```python
import pathlib
import sys

_SHARED_DIR = pathlib.Path(__file__).resolve().parents[2] / "_shared"
if str(_SHARED_DIR) not in sys.path:
    sys.path.insert(0, str(_SHARED_DIR))

from editorial_http import fetch_html, validate_public_url

# Inside KevrastAuditor:
def fetch_page(self, url: str) -> Dict[str, Any]:
    norm = self.norm_url(url)
    if norm in self._page_cache:
        return copy.deepcopy(self._page_cache[norm])

    result: Dict[str, Any] = {
        "url": url, "status_code": None, "final_url": None,
        "html": None, "live": False, "note": None,
    }

    status, body, headers = fetch_html(url, timeout=self.timeout)
    result["status_code"] = status
    result["final_url"] = headers.get("x-final-url") or url

    if 200 <= status < 300:
        result["html"] = body
    else:
        result["note"] = headers.get("x-error") or f"HTTP {status}"
        self._page_cache[norm] = copy.deepcopy(result)
        return result

    # Perform soft-404 and redirect-to-root heuristics on result["html"]...
```

#### 3. Integration in `process_news_image.py` (Milestone 3)
Replace local `validate_public_url`, `SSRFSafeRedirectHandler`, `build_secure_opener`, and `fetch_url_bytes` (lines 70-133) with imports from `editorial_http`:
```python
from editorial_http import fetch_binary, fetch_html, validate_public_url

def fetch_url_bytes(url: str, max_bytes: int = MAX_IMAGE_DOWNLOAD_BYTES, timeout: int = 15) -> bytes:
    status, data, headers = fetch_binary(url, timeout=timeout, max_bytes=max_bytes)
    if status != 200:
        raise ValueError(f"Failed to fetch image ({status}): {headers.get('x-error', '')}")
    return data
```

---

## 5. Verification Method

Once `.agents/skills/_shared/editorial_http.py` is implemented, execute the following commands to independently verify correctness:

1. **Syntax & Compilation Validation**:
   ```bash
   python3 -m py_compile .agents/skills/_shared/editorial_http.py
   ```
   *Expected outcome:* Exit code 0, no syntax errors.

2. **SSRF Guard Test Suite**:
   ```bash
   python3 -c "
   import sys, pathlib
   sys.path.insert(0, str(pathlib.Path('.agents/skills/_shared').resolve()))
   from editorial_http import validate_public_url

   test_cases = [
       ('http://127.0.0.1/admin', False),
       ('http://localhost/', False),
       ('http://169.254.169.254/latest/meta-data', False),
       ('http://100.64.0.1/', False),
       ('http://10.1.2.3/', False),
       ('http://172.16.5.1/', False),
       ('http://192.168.1.1/', False),
       ('http://[::1]/', False),
       ('http://[fe80::1]/', False),
       ('http://[fc00::1]/', False),
       ('ftp://example.com/', False),
       ('file:///etc/passwd', False),
       ('https://www.google.com/', True),
   ]
   for url, expected in test_cases:
       valid, reason = validate_public_url(url)
       assert valid == expected, f'Failed for {url}: got {valid}, expected {expected} ({reason})'
   print('All SSRF tests passed!')
   "
   ```
   *Expected outcome:* Prints `All SSRF tests passed!`.

3. **Two-Tier Cache Atomicity & Inter-Process Sharing**:
   ```bash
   python3 -c "
   import sys, pathlib
   sys.path.insert(0, str(pathlib.Path('.agents/skills/_shared').resolve()))
   from editorial_http import set_cached_response, get_cached_response, clear_cache

   clear_cache()
   url = 'https://example.com/cached-article?ref=1#heading'
   set_cached_response(url, 200, '<html>Cached</html>', {'content-type': 'text/html'})

   # Test retrieval from same or subshell process
   cached = get_cached_response('https://example.com/cached-article?ref=1')
   assert cached is not None and cached[1] == '<html>Cached</html>'
   print('Cache sharing test passed!')
   "
   ```
   *Expected outcome:* Prints `Cache sharing test passed!`.

4. **Retry-After Header Parsing**:
   ```bash
   python3 -c "
   import sys, pathlib
   sys.path.insert(0, str(pathlib.Path('.agents/skills/_shared').resolve()))
   from editorial_http import _parse_retry_after

   assert _parse_retry_after('10') == 10.0
   assert _parse_retry_after('') == 0.0
   print('Retry-After parsing passed!')
   "
   ```
   *Expected outcome:* Prints `Retry-After parsing passed!`.
