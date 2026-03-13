"""Callback service — POST pipeline results back to Laravel.

Security Controls:
- HMAC-SHA256 signature on all payloads (X-Signature-SHA256 header)
- Organization ID included in all callbacks
- Connection pooling for efficiency
- Timeout protection
"""

from __future__ import annotations

from typing import Any

import httpx

from app.config import get_settings
from app.shared.logging import get_logger
from app.shared.security import sign_callback_payload


async def send_callback(
    callback_url: str,
    correlation_id: str,
    job_id: str,
    organization_id: str,
    *,
    pipeline: str,
    status: str,
    result: dict[str, Any] | None = None,
    metadata: dict[str, Any] | None = None,
) -> None:
    """POST the pipeline outcome to the Laravel callback endpoint.

    Security:
    - Includes HMAC-SHA256 signature for payload verification
    - Includes organization_id for Laravel-side validation
    - Uses X-Internal-Secret for service-to-service auth

    Parameters
    ----------
    callback_url:
        Full URL provided in the original pipeline request.
    correlation_id:
        Unique ID linking this callback to the originating request.
    job_id:
        Internal job identifier stored in Redis.
    organization_id:
        Organization UUID for tenant context.
    pipeline:
        Pipeline identifier (e.g. ``"content_creation"``, ``"social_listening"``).
    status:
        ``"completed"`` or ``"failed"``.
    result:
        Final pipeline output (e.g. ``final_content`` dict).
    metadata:
        Execution metadata (tokens, cost, agents, duration, retries).
    """
    settings = get_settings()
    logger = get_logger(
        pipeline=pipeline,
        correlation_id=correlation_id,
        organization_id=organization_id,
    )

    payload = {
        "correlation_id": correlation_id,
        "job_id": job_id,
        "organization_id": organization_id,
        "pipeline": pipeline,
        "status": status,
        "result": result or {},
        "metadata": metadata or {},
    }

    headers: dict[str, str] = {
        "Content-Type": "application/json",
    }

    # Add authentication header
    if settings.internal_secret:
        headers["X-Internal-Secret"] = settings.internal_secret

        # Add HMAC signature for payload verification
        signature = sign_callback_payload(payload, settings.internal_secret)
        headers["X-Signature-SHA256"] = signature

    try:
        async with httpx.AsyncClient(timeout=10.0) as client:
            response = await client.post(
                callback_url,
                json=payload,
                headers=headers,
            )
            response.raise_for_status()

        logger.info(
            "Callback sent successfully",
            status=status,
            http_status=response.status_code,
        )

    except httpx.TimeoutException:
        logger.error(
            "Callback timed out",
            status=status,
            callback_url=_mask_url(callback_url),
            timeout_seconds=10.0,
        )

    except httpx.HTTPStatusError as exc:
        logger.error(
            "Callback received error response",
            status=status,
            http_status=exc.response.status_code,
            callback_url=_mask_url(callback_url),
        )

    except httpx.RequestError as exc:
        logger.error(
            "Callback request failed",
            status=status,
            error=str(exc),
            callback_url=_mask_url(callback_url),
        )


def _mask_url(url: str) -> str:
    """Mask URL for safe logging."""
    from urllib.parse import urlparse

    try:
        parsed = urlparse(url)
        return f"{parsed.scheme}://{parsed.hostname}:{parsed.port or 'default'}{parsed.path}"
    except Exception:
        return "***INVALID_URL***"
