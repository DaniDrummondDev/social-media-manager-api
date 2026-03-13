"""Rate limiting middleware for AI pipeline requests.

This module implements per-organization rate limiting to prevent:
- DoS attacks
- Unlimited AI cost abuse
- Resource exhaustion

Rate limits are enforced using Redis with sliding window algorithm.
"""

from __future__ import annotations

import time
from dataclasses import dataclass
from typing import TYPE_CHECKING

from fastapi import HTTPException, Request

from app.config import get_settings
from app.shared.logging import get_logger

if TYPE_CHECKING:
    import redis.asyncio as aioredis


@dataclass
class RateLimitConfig:
    """Rate limit configuration per pipeline type."""

    requests_per_minute: int
    requests_per_hour: int
    requests_per_day: int


# Default rate limits per organization per pipeline
DEFAULT_RATE_LIMITS: dict[str, RateLimitConfig] = {
    "content_creation": RateLimitConfig(
        requests_per_minute=10,
        requests_per_hour=100,
        requests_per_day=500,
    ),
    "content_dna": RateLimitConfig(
        requests_per_minute=5,
        requests_per_hour=30,
        requests_per_day=100,
    ),
    "social_listening": RateLimitConfig(
        requests_per_minute=30,
        requests_per_hour=500,
        requests_per_day=5000,
    ),
    "visual_adaptation": RateLimitConfig(
        requests_per_minute=10,
        requests_per_hour=100,
        requests_per_day=500,
    ),
}


class RateLimiter:
    """Redis-backed rate limiter with sliding window algorithm."""

    def __init__(self, redis_client: "aioredis.Redis") -> None:
        """Initialize rate limiter with Redis client.

        Parameters
        ----------
        redis_client : aioredis.Redis
            Async Redis client for storing rate limit counters.
        """
        self.redis = redis_client
        self.logger = get_logger()

    async def check_rate_limit(
        self,
        organization_id: str,
        pipeline: str,
        *,
        config: RateLimitConfig | None = None,
    ) -> None:
        """Check if request is within rate limits.

        Parameters
        ----------
        organization_id : str
            Organization making the request.
        pipeline : str
            Pipeline type being invoked.
        config : RateLimitConfig | None
            Custom rate limit config (uses defaults if None).

        Raises
        ------
        HTTPException
            429 if rate limit exceeded.
        """
        if config is None:
            config = DEFAULT_RATE_LIMITS.get(
                pipeline,
                RateLimitConfig(
                    requests_per_minute=10,
                    requests_per_hour=100,
                    requests_per_day=1000,
                ),
            )

        now = int(time.time())
        base_key = f"ratelimit:{organization_id}:{pipeline}"

        # Check all windows
        checks = [
            (f"{base_key}:minute", 60, config.requests_per_minute),
            (f"{base_key}:hour", 3600, config.requests_per_hour),
            (f"{base_key}:day", 86400, config.requests_per_day),
        ]

        for key, window_seconds, limit in checks:
            current_count = await self._get_window_count(key, now, window_seconds)

            if current_count >= limit:
                window_name = "minute" if window_seconds == 60 else ("hour" if window_seconds == 3600 else "day")
                retry_after = await self._get_retry_after(key, now, window_seconds)

                self.logger.warning(
                    "Rate limit exceeded",
                    organization_id=organization_id,
                    pipeline=pipeline,
                    window=window_name,
                    current_count=current_count,
                    limit=limit,
                    retry_after=retry_after,
                )

                raise HTTPException(
                    status_code=429,
                    detail=f"Rate limit exceeded: {limit} requests per {window_name}",
                    headers={"Retry-After": str(retry_after)},
                )

    async def increment(
        self,
        organization_id: str,
        pipeline: str,
    ) -> None:
        """Increment rate limit counters for all windows.

        Call this AFTER successfully accepting a request.

        Parameters
        ----------
        organization_id : str
            Organization making the request.
        pipeline : str
            Pipeline type being invoked.
        """
        now = int(time.time())
        base_key = f"ratelimit:{organization_id}:{pipeline}"

        # Increment all windows using Redis pipeline for efficiency
        async with self.redis.pipeline() as pipe:
            for suffix, ttl in [("minute", 60), ("hour", 3600), ("day", 86400)]:
                key = f"{base_key}:{suffix}"
                pipe.zadd(key, {str(now): now})
                pipe.expire(key, ttl + 1)  # TTL slightly longer than window

            await pipe.execute()

        self.logger.debug(
            "Rate limit counters incremented",
            organization_id=organization_id,
            pipeline=pipeline,
        )

    async def _get_window_count(
        self,
        key: str,
        now: int,
        window_seconds: int,
    ) -> int:
        """Get count of requests in the current sliding window.

        Parameters
        ----------
        key : str
            Redis key for the window.
        now : int
            Current Unix timestamp.
        window_seconds : int
            Size of the sliding window in seconds.

        Returns
        -------
        int
            Number of requests in the window.
        """
        window_start = now - window_seconds

        # Remove expired entries and count remaining
        await self.redis.zremrangebyscore(key, 0, window_start)
        count = await self.redis.zcard(key)

        return count

    async def _get_retry_after(
        self,
        key: str,
        now: int,
        window_seconds: int,
    ) -> int:
        """Calculate seconds until oldest request expires from window.

        Parameters
        ----------
        key : str
            Redis key for the window.
        now : int
            Current Unix timestamp.
        window_seconds : int
            Size of the sliding window in seconds.

        Returns
        -------
        int
            Seconds until rate limit resets.
        """
        # Get oldest entry in window
        oldest = await self.redis.zrange(key, 0, 0, withscores=True)

        if not oldest:
            return 1

        oldest_timestamp = int(oldest[0][1])
        retry_after = (oldest_timestamp + window_seconds) - now

        return max(1, retry_after)


async def get_rate_limiter(request: Request) -> RateLimiter:
    """FastAPI dependency to get rate limiter instance.

    Parameters
    ----------
    request : Request
        FastAPI request object.

    Returns
    -------
    RateLimiter
        Rate limiter instance backed by app Redis client.
    """
    return RateLimiter(request.app.state.redis)
