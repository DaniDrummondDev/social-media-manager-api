"""Security utilities for the AI Agents microservice.

This module provides:
- SSRF protection for callback URLs
- Prompt injection defense
- Input sanitization for LLM prompts
- Job ID namespacing for organization isolation
"""

from __future__ import annotations

import hashlib
import hmac
import ipaddress
import re
import secrets
import socket
from typing import Any
from urllib.parse import urlparse

from app.config import get_settings
from app.shared.logging import get_logger

# ---------------------------------------------------------------------------
# SSRF Protection
# ---------------------------------------------------------------------------

# Allowed callback URL patterns (internal services only)
ALLOWED_CALLBACK_HOSTS: set[str] = {
    "nginx",
    "app",
    "localhost",
    "127.0.0.1",
}

# Blocked IP ranges (internal networks, cloud metadata, etc.)
BLOCKED_IP_RANGES: list[ipaddress.IPv4Network | ipaddress.IPv6Network] = [
    ipaddress.ip_network("10.0.0.0/8"),      # Private Class A
    ipaddress.ip_network("172.16.0.0/12"),   # Private Class B
    ipaddress.ip_network("192.168.0.0/16"),  # Private Class C
    ipaddress.ip_network("169.254.0.0/16"),  # Link-local / AWS metadata
    ipaddress.ip_network("127.0.0.0/8"),     # Loopback
    ipaddress.ip_network("0.0.0.0/8"),       # Current network
    ipaddress.ip_network("::1/128"),         # IPv6 loopback
    ipaddress.ip_network("fc00::/7"),        # IPv6 private
    ipaddress.ip_network("fe80::/10"),       # IPv6 link-local
]


class SSRFProtectionError(Exception):
    """Raised when a URL fails SSRF validation."""

    pass


def validate_callback_url(url: str) -> str:
    """Validate callback URL against SSRF attacks.

    Only allows URLs to known internal services (nginx, app).
    Blocks requests to:
    - Cloud metadata endpoints (169.254.169.254)
    - Internal IP ranges
    - External hosts

    Parameters
    ----------
    url : str
        The callback URL to validate.

    Returns
    -------
    str
        The validated URL (unchanged if valid).

    Raises
    ------
    SSRFProtectionError
        If the URL fails validation.
    """
    logger = get_logger()

    try:
        parsed = urlparse(url)
    except Exception as e:
        logger.warning("Invalid callback URL format", url=_mask_url(url), error=str(e))
        raise SSRFProtectionError(f"Invalid URL format: {url}")

    # Must be HTTP or HTTPS
    if parsed.scheme not in ("http", "https"):
        logger.warning("Invalid callback URL scheme", url=_mask_url(url), scheme=parsed.scheme)
        raise SSRFProtectionError(f"Invalid URL scheme: {parsed.scheme}")

    # Must have a host
    if not parsed.hostname:
        logger.warning("Callback URL missing hostname", url=_mask_url(url))
        raise SSRFProtectionError("URL must have a hostname")

    hostname = parsed.hostname.lower()

    # Check if hostname is in allowlist
    if hostname not in ALLOWED_CALLBACK_HOSTS:
        # Check if it ends with allowed pattern (e.g., *.internal)
        is_allowed = False

        # For Docker internal network, allow service names
        # In production, this should be stricter
        settings = get_settings()
        if settings.environment == "development":
            # In dev, also allow the configured callback base URL host
            base_url_host = urlparse(settings.callback_base_url).hostname
            if base_url_host and hostname == base_url_host:
                is_allowed = True

        if not is_allowed:
            logger.warning(
                "Callback URL hostname not in allowlist",
                url=_mask_url(url),
                hostname=hostname,
                allowed=list(ALLOWED_CALLBACK_HOSTS),
            )
            raise SSRFProtectionError(f"Hostname not allowed: {hostname}")

    # Resolve hostname and check IP
    try:
        # Get all IP addresses for hostname
        _, _, ip_addresses = socket.gethostbyname_ex(hostname)

        for ip_str in ip_addresses:
            ip = ipaddress.ip_address(ip_str)

            # Check against blocked ranges
            for blocked_range in BLOCKED_IP_RANGES:
                if ip in blocked_range:
                    # Allow loopback/internal for Docker service names
                    if hostname in ALLOWED_CALLBACK_HOSTS:
                        continue

                    logger.warning(
                        "Callback URL resolves to blocked IP range",
                        url=_mask_url(url),
                        ip=ip_str,
                        blocked_range=str(blocked_range),
                    )
                    raise SSRFProtectionError(f"IP address not allowed: {ip_str}")

    except socket.gaierror:
        # DNS resolution failed - might be valid Docker hostname
        if hostname not in ALLOWED_CALLBACK_HOSTS:
            logger.warning("Callback URL DNS resolution failed", url=_mask_url(url), hostname=hostname)
            raise SSRFProtectionError(f"Cannot resolve hostname: {hostname}")

    logger.debug("Callback URL validated", url=_mask_url(url))
    return url


def _mask_url(url: str) -> str:
    """Mask URL for safe logging (hide potential secrets in query params)."""
    try:
        parsed = urlparse(url)
        return f"{parsed.scheme}://{parsed.hostname}:{parsed.port or 'default'}{parsed.path}..."
    except Exception:
        return "***INVALID_URL***"


# ---------------------------------------------------------------------------
# Prompt Injection Defense
# ---------------------------------------------------------------------------

# Patterns that indicate prompt injection attempts
INJECTION_PATTERNS: list[re.Pattern[str]] = [
    re.compile(r"ignore\s+(previous|above|all)\s+(instructions?|prompts?)", re.IGNORECASE),
    re.compile(r"disregard\s+(previous|above|all)", re.IGNORECASE),
    re.compile(r"forget\s+(everything|previous|above)", re.IGNORECASE),
    re.compile(r"new\s+instructions?:", re.IGNORECASE),
    re.compile(r"system\s*:\s*", re.IGNORECASE),
    re.compile(r"assistant\s*:\s*", re.IGNORECASE),
    re.compile(r"<\s*(system|assistant|user)\s*>", re.IGNORECASE),
    re.compile(r"\[\s*(INST|SYS)\s*\]", re.IGNORECASE),
    re.compile(r"```\s*(system|python|bash|sh)\s*\n", re.IGNORECASE),
    re.compile(r"execute\s+(code|command|script)", re.IGNORECASE),
    re.compile(r"output\s+(api|key|secret|password|token)", re.IGNORECASE),
    re.compile(r"reveal\s+(your|the)\s+(instructions?|prompt|system)", re.IGNORECASE),
]

# Characters that should be escaped in prompts
DANGEROUS_CHARS: dict[str, str] = {
    "```": "'''",  # Code blocks
    "{{": "{ {",   # Template injection
    "}}": "} }",
    "${": "$ {",   # Variable injection
    "<script": "< script",  # XSS if output rendered
}


class PromptInjectionError(Exception):
    """Raised when prompt injection is detected."""

    pass


def sanitize_for_prompt(text: str, *, field_name: str = "input") -> str:
    """Sanitize user input for safe inclusion in LLM prompts.

    This function:
    1. Detects obvious prompt injection patterns
    2. Escapes dangerous characters
    3. Limits length to prevent context overflow
    4. Removes control characters

    Parameters
    ----------
    text : str
        User input to sanitize.
    field_name : str
        Name of the field (for error messages).

    Returns
    -------
    str
        Sanitized text safe for prompt inclusion.

    Raises
    ------
    PromptInjectionError
        If obvious injection attempt detected.
    """
    logger = get_logger()

    if not isinstance(text, str):
        text = str(text)

    # Check for injection patterns
    for pattern in INJECTION_PATTERNS:
        if pattern.search(text):
            logger.warning(
                "Prompt injection pattern detected",
                field=field_name,
                pattern=pattern.pattern,
                text_preview=text[:100],
            )
            raise PromptInjectionError(
                f"Suspicious pattern detected in {field_name}. "
                "Please rephrase your input."
            )

    # Remove control characters (except newline, tab)
    sanitized = "".join(
        char for char in text
        if char.isprintable() or char in ("\n", "\t")
    )

    # Escape dangerous characters
    for dangerous, safe in DANGEROUS_CHARS.items():
        sanitized = sanitized.replace(dangerous, safe)

    # Log if significantly different
    if len(sanitized) < len(text) * 0.9:
        logger.info(
            "Input significantly sanitized",
            field=field_name,
            original_len=len(text),
            sanitized_len=len(sanitized),
        )

    return sanitized


def sanitize_dict_for_prompt(
    data: dict[str, Any],
    *,
    max_depth: int = 3,
    _current_depth: int = 0,
) -> dict[str, Any]:
    """Recursively sanitize a dictionary for safe inclusion in prompts.

    Parameters
    ----------
    data : dict[str, Any]
        Dictionary to sanitize.
    max_depth : int
        Maximum recursion depth.

    Returns
    -------
    dict[str, Any]
        Sanitized dictionary.
    """
    if _current_depth >= max_depth:
        return {"__truncated__": "max depth reached"}

    result: dict[str, Any] = {}

    for key, value in data.items():
        # Sanitize key
        safe_key = sanitize_for_prompt(str(key), field_name=f"key:{key}")

        # Sanitize value based on type
        if isinstance(value, str):
            result[safe_key] = sanitize_for_prompt(value, field_name=f"value:{key}")
        elif isinstance(value, dict):
            result[safe_key] = sanitize_dict_for_prompt(
                value,
                max_depth=max_depth,
                _current_depth=_current_depth + 1,
            )
        elif isinstance(value, list):
            result[safe_key] = [
                sanitize_for_prompt(str(item), field_name=f"list:{key}")
                if isinstance(item, str)
                else item
                for item in value[:100]  # Limit list size
            ]
        else:
            result[safe_key] = value

    return result


# ---------------------------------------------------------------------------
# Job ID Namespacing
# ---------------------------------------------------------------------------


def generate_namespaced_job_id(organization_id: str) -> str:
    """Generate a job ID namespaced by organization.

    This prevents job ID enumeration attacks across organizations.

    Format: {org_prefix}_{random_id}

    Parameters
    ----------
    organization_id : str
        Organization UUID.

    Returns
    -------
    str
        Namespaced job ID.
    """
    # Create a short hash of org_id for prefix (not reversible)
    org_hash = hashlib.sha256(organization_id.encode()).hexdigest()[:8]

    # Generate random component
    random_part = secrets.token_hex(16)

    return f"{org_hash}_{random_part}"


def validate_job_ownership(
    job_id: str,
    organization_id: str,
) -> bool:
    """Validate that a job ID belongs to an organization.

    Parameters
    ----------
    job_id : str
        The job ID to validate.
    organization_id : str
        The organization claiming ownership.

    Returns
    -------
    bool
        True if job belongs to organization.
    """
    expected_prefix = hashlib.sha256(organization_id.encode()).hexdigest()[:8]
    return job_id.startswith(f"{expected_prefix}_")


def get_job_redis_key(organization_id: str, job_id: str) -> str:
    """Get the Redis key for a job, namespaced by organization.

    Parameters
    ----------
    organization_id : str
        Organization UUID.
    job_id : str
        Job ID.

    Returns
    -------
    str
        Redis key in format: job:{org_id}:{job_id}
    """
    return f"job:{organization_id}:{job_id}"


# ---------------------------------------------------------------------------
# Callback Signature
# ---------------------------------------------------------------------------


def sign_callback_payload(payload: dict[str, Any], secret: str) -> str:
    """Generate HMAC-SHA256 signature for callback payload.

    Parameters
    ----------
    payload : dict[str, Any]
        The callback payload to sign.
    secret : str
        The shared secret for signing.

    Returns
    -------
    str
        Hex-encoded HMAC-SHA256 signature.
    """
    import json

    canonical = json.dumps(payload, sort_keys=True, separators=(",", ":"))
    signature = hmac.new(
        secret.encode("utf-8"),
        canonical.encode("utf-8"),
        hashlib.sha256,
    ).hexdigest()

    return signature
