"""Shared test fixtures for AI Agents microservice tests.

Provides:
- Authenticated test client with X-Internal-Secret header
- Mock settings with test secrets
- Sample request data factories
"""

from __future__ import annotations

from typing import Any, Generator
from unittest.mock import patch

import pytest
from fastapi import FastAPI
from fastapi.testclient import TestClient

from app.api.routes import router
from app.config import Settings


# ---------------------------------------------------------------------------
# Test Configuration
# ---------------------------------------------------------------------------

TEST_INTERNAL_SECRET = "test-internal-secret-for-unit-tests-only-32chars"
TEST_ORGANIZATION_ID = "550e8400-e29b-41d4-a716-446655440000"
TEST_CORRELATION_ID = "test-correlation-id-123"


# ---------------------------------------------------------------------------
# Settings Fixtures
# ---------------------------------------------------------------------------


@pytest.fixture
def test_settings() -> Settings:
    """Create test settings with internal_secret configured."""
    return Settings(
        internal_secret=TEST_INTERNAL_SECRET,
        environment="development",
        openai_api_key="sk-test-key",
        anthropic_api_key="sk-ant-test-key",
    )


@pytest.fixture
def mock_settings(test_settings: Settings) -> Generator[Settings, None, None]:
    """Patch get_settings to return test settings."""
    with patch("app.config.get_settings", return_value=test_settings):
        with patch("app.middleware.auth.get_settings", return_value=test_settings):
            with patch("app.api.routes.get_logger"):
                yield test_settings


# ---------------------------------------------------------------------------
# Client Fixtures
# ---------------------------------------------------------------------------


@pytest.fixture
def app(mock_settings: Settings) -> FastAPI:
    """Create test FastAPI application."""
    from unittest.mock import AsyncMock, MagicMock

    application = FastAPI()
    application.include_router(router)

    # Mock Redis
    mock_redis = MagicMock()
    mock_redis.set = AsyncMock(return_value=True)
    mock_redis.get = AsyncMock(return_value=None)
    mock_redis.ping = AsyncMock(return_value=True)
    mock_redis.zremrangebyscore = AsyncMock(return_value=0)
    mock_redis.zcard = AsyncMock(return_value=0)
    mock_redis.zadd = AsyncMock(return_value=1)
    mock_redis.expire = AsyncMock(return_value=True)
    mock_redis.pipeline = MagicMock(return_value=AsyncMock())
    application.state.redis = mock_redis

    # Mock PostgreSQL pool
    mock_pg_pool = MagicMock()
    mock_conn = AsyncMock()
    mock_conn.fetchval = AsyncMock(return_value=1)
    mock_pg_pool.acquire = MagicMock(return_value=AsyncMock(__aenter__=AsyncMock(return_value=mock_conn)))
    application.state.pg_pool = mock_pg_pool

    return application


@pytest.fixture
def client(app: FastAPI) -> TestClient:
    """Create test client WITHOUT authentication headers."""
    return TestClient(app)


@pytest.fixture
def auth_client(app: FastAPI) -> TestClient:
    """Create test client WITH authentication headers."""
    client = TestClient(app)
    client.headers["X-Internal-Secret"] = TEST_INTERNAL_SECRET
    return client


@pytest.fixture
def auth_headers() -> dict[str, str]:
    """Return authentication headers for manual use."""
    return {"X-Internal-Secret": TEST_INTERNAL_SECRET}


# ---------------------------------------------------------------------------
# Request Data Factories
# ---------------------------------------------------------------------------


@pytest.fixture
def content_creation_request() -> dict[str, Any]:
    """Sample content creation request data."""
    return {
        "organization_id": TEST_ORGANIZATION_ID,
        "correlation_id": TEST_CORRELATION_ID,
        "callback_url": "http://nginx/api/v1/internal/callback",
        "topic": "Test topic for content creation",
        "provider": "instagram_feed",
        "tone": "professional",
        "keywords": ["test", "example"],
        "language": "pt-BR",
    }


@pytest.fixture
def content_dna_request() -> dict[str, Any]:
    """Sample content DNA request data."""
    return {
        "organization_id": TEST_ORGANIZATION_ID,
        "correlation_id": TEST_CORRELATION_ID,
        "callback_url": "http://nginx/api/v1/internal/callback",
        "time_window": "last_90_days",
        "published_contents": [
            {
                "id": "content-1",
                "title": "Test Content",
                "body": "Test body",
                "provider": "instagram_feed",
                "hashtags": ["#test"],
                "published_at": "2026-01-01T12:00:00Z",
            }
        ],
        "metrics": [
            {
                "content_id": "content-1",
                "impressions": 1000,
                "reach": 800,
                "likes": 50,
                "comments": 10,
                "shares": 5,
                "saves": 20,
                "engagement_rate": 0.085,
            }
        ],
    }


@pytest.fixture
def social_listening_request() -> dict[str, Any]:
    """Sample social listening request data."""
    return {
        "organization_id": TEST_ORGANIZATION_ID,
        "correlation_id": TEST_CORRELATION_ID,
        "callback_url": "http://nginx/api/v1/internal/callback",
        "mention": {
            "id": "mention-1",
            "content": "Great product, love it!",
            "platform": "instagram",
            "author_username": "testuser",
            "author_display_name": "Test User",
            "author_follower_count": 1000,
            "url": "https://instagram.com/p/123",
            "published_at": "2026-02-28T12:00:00Z",
        },
        "brand_context": {
            "brand_name": "Test Brand",
            "industry": "Technology",
            "guidelines": "Be friendly and helpful",
            "tone_preferences": ["professional", "friendly"],
            "blacklisted_words": ["spam"],
        },
        "language": "pt-BR",
    }


@pytest.fixture
def visual_adaptation_request() -> dict[str, Any]:
    """Sample visual adaptation request data."""
    return {
        "organization_id": TEST_ORGANIZATION_ID,
        "correlation_id": TEST_CORRELATION_ID,
        "callback_url": "http://nginx/api/v1/internal/callback",
        "image_url": "https://example.com/image.jpg",
        "target_networks": ["instagram", "tiktok"],
        "brand_guidelines": {
            "primary_color": "#FF0000",
            "logo_position": "bottom-right",
        },
    }
