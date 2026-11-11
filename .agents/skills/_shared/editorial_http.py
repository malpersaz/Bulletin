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
from typing import Any, Dict, List, Optional, Tuple, Union

__all__ = [
    "validate_public_url",
    "fetch_html",
    "fetch_binary",
    "normalize_url",
    "get_cached_response",
    "set_cached_response",
    "clear_cache",
    "SSRFSafeRedirectHandler",
    "build_secure_opener",
    "is_blocked_ip",
]

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
FALLBACK_RETRY_DELAYS: List[float] = [2.0, 5.0]

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
    ipaddress.ip_network("::1/128"),            # Loopback
    ipaddress.ip_network("::ffff:0:0/96"),      # IPv4-mapped IPv6
    ipaddress.ip_network("100::/64"),           # Discard-Only (RFC 6666)
    ipaddress.ip_network("64:ff9b::/96"),       # IPv4/IPv6 translation
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

    if (parsed.scheme or "").lower() not in ("http", "https"):
        return False, f"Invalid URL scheme '{parsed.scheme}': only http and https are permitted"

    try:
        hostname = parsed.hostname
    except (ValueError, Exception) as host_err:
        return False, f"Invalid hostname in URL: {host_err}"

    if not hostname:
        return False, "URL is missing hostname"

    # Validate port if specified
    try:
        port = parsed.port
    except ValueError as e:
        return False, f"Invalid port: {e}"
    except Exception as e:
        return False, f"Invalid port: {e}"

    if port is not None and not (1 <= port <= 65535):
        return False, f"Invalid port: Port out of range 1-65535: {port}"

    # Pre-validate hostname length and label length before DNS resolution
    if len(hostname) > 253 or any(len(label) > 63 for label in hostname.split(".")):
        return False, f"Hostname or domain label exceeds length limit: '{hostname}'"

    try:
        addr_info = socket.getaddrinfo(hostname.rstrip("."), None, proto=socket.IPPROTO_TCP)
    except (socket.gaierror, socket.herror, UnicodeError, OSError, ValueError) as e:
        return False, f"Could not resolve host '{hostname}': {e}"
    except Exception as e:
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
                try:
                    os.remove(cache_file)
                except OSError:
                    pass
        except Exception:
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
    temp_file: Optional[str] = None
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
        if temp_file and os.path.exists(temp_file):
            try:
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
    try:
        is_valid, reason = validate_public_url(url)
    except Exception as exc:
        is_valid, reason = False, f"Validation exception: {exc}"

    if not is_valid:
        err_dict = {"x-error": reason, "error": reason}
        return 400, f"SSRF protection blocked URL: {reason}".encode("utf-8"), err_dict

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
                    err_msg = f"Content exceeded maximum limit of {max_bytes} bytes"
                    return 413, b"", {"x-error": err_msg, "error": err_msg}

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

            err_detail = str(e.msg) if getattr(e, "msg", None) else f"HTTP {status_code}"
            resp_headers["x-error"] = err_detail
            resp_headers["error"] = err_detail
            return status_code, err_body, resp_headers

        except (urllib.error.URLError, socket.timeout, TimeoutError, OSError, ValueError, Exception) as e:
            if attempt < max_retries and isinstance(e, (socket.timeout, TimeoutError)):
                sleep_sec = FALLBACK_RETRY_DELAYS[min(attempt, len(FALLBACK_RETRY_DELAYS) - 1)]
                time.sleep(sleep_sec)
                attempt += 1
                continue

            err_msg = str(e)
            err_dict = {
                "x-error": f"Transport error ({e.__class__.__name__}): {err_msg}",
                "error": f"Transport error ({e.__class__.__name__}): {err_msg}",
            }
            return 0, b"", err_dict

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
    try:
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
    except Exception as e:
        err_str = str(e)
        return 0, "", {"error": err_str, "x-error": err_str}


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
    try:
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
    except Exception as e:
        err_str = str(e)
        return 0, b"", {"error": err_str, "x-error": err_str}
