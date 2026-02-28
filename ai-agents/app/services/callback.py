"""Callback service — POST pipeline results back to Laravel."""

from __future__ import annotations

from typing import Any

import httpx

from app.config import get_settings
from app.shared.logging import get_logger


async def send_callback(
    callback_url: str,
    correlation_id: str,
    job_id: str,
    *,
    pipeline: str,
    status: str,
    result: dict[str, Any] | None = None,
    metadata: dict[str, Any] | None = None,
) -> None:
    """POST the pipeline outcome to the Laravel callback endpoint.

    Parameters
    ----------
    callback_url:
        Full URL provided in the original pipeline request.
    correlation_id:
        Unique ID linking this callback to the originating request.
    job_id:
        Internal job identifier stored in Redis.
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
    )

    payload = {
        "correlation_id": correlation_id,
        "job_id": job_id,
        "pipeline": pipeline,
        "status": status,
        "result": result or {},
        "metadata": metadata or {},
    }

    headers = {}
    if settings.internal_secret:
        headers["X-Internal-Secret"] = settings.internal_secret

    try:
        async with httpx.AsyncClient(timeout=10.0) as client:
            response = await client.post(
                callback_url,
                json=payload,
                headers=headers,
            )
            response.raise_for_status()
        logger.info("Callback sent", status=status, http_status=response.status_code)
    except httpx.HTTPError as exc:
        logger.error(
            "Callback failed",
            status=status,
            error=str(exc),
            callback_url=callback_url,
        )
