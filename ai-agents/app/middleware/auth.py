"""Authentication middleware for service-to-service communication.

This module implements authentication for the AI Agents microservice,
ensuring only authorized Laravel backend requests can execute pipelines.

Security Controls:
- X-Internal-Secret header validation (HMAC-safe comparison)
- Request logging for failed authentication attempts
- Environment-based secret configuration
"""

from __future__ import annotations

import hmac
import secrets
from typing import Annotated

from fastapi import Depends, Header, HTTPException, Request

from app.config import get_settings
from app.shared.logging import get_logger


class AuthenticationError(Exception):
    """Raised when authentication fails."""

    pass


async def verify_internal_secret(
    request: Request,
    x_internal_secret: Annotated[str | None, Header(alias="X-Internal-Secret")] = None,
) -> str:
    """Verify the X-Internal-Secret header for service-to-service auth.

    This dependency MUST be applied to all pipeline endpoints to ensure
    only the Laravel backend can invoke AI pipelines.

    Parameters
    ----------
    request : Request
        FastAPI request object (used for logging client info).
    x_internal_secret : str | None
        The secret header value from the incoming request.

    Returns
    -------
    str
        The validated secret (for downstream use if needed).

    Raises
    ------
    HTTPException
        401 if header is missing.
        403 if header value is invalid.
        500 if internal secret is not configured.
    """
    settings = get_settings()
    logger = get_logger()

    # Get client info for logging (mask sensitive data)
    client_ip = request.client.host if request.client else "unknown"
    user_agent = request.headers.get("user-agent", "unknown")
    path = request.url.path

    # Check if internal secret is configured
    if not settings.internal_secret:
        logger.critical(
            "SECURITY: Internal secret not configured",
            path=path,
            client_ip=client_ip,
        )
        raise HTTPException(
            status_code=500,
            detail="Internal authentication not configured",
        )

    # Check if header is present
    if not x_internal_secret:
        logger.warning(
            "Authentication failed: missing X-Internal-Secret header",
            path=path,
            client_ip=client_ip,
            user_agent=user_agent,
        )
        raise HTTPException(
            status_code=401,
            detail="Authentication required",
        )

    # Use constant-time comparison to prevent timing attacks
    if not hmac.compare_digest(x_internal_secret, settings.internal_secret):
        logger.warning(
            "Authentication failed: invalid X-Internal-Secret",
            path=path,
            client_ip=client_ip,
            user_agent=user_agent,
        )
        raise HTTPException(
            status_code=403,
            detail="Invalid authentication credentials",
        )

    logger.debug(
        "Authentication successful",
        path=path,
        client_ip=client_ip,
    )

    return x_internal_secret


def generate_internal_secret() -> str:
    """Generate a cryptographically secure internal secret.

    Use this to generate the INTERNAL_SECRET environment variable.

    Returns
    -------
    str
        A 64-character hex string (256 bits of entropy).
    """
    return secrets.token_hex(32)
