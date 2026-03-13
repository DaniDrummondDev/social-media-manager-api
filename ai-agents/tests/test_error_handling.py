"""Tests for error handling across pipelines.

Security Features:
- Callback tests include organization_id parameter
- Job status tests use valid organization scoping
"""

from __future__ import annotations

from unittest.mock import AsyncMock, MagicMock, patch

import httpx
import pytest

from app.config import Settings

TEST_INTERNAL_SECRET = "test-internal-secret-for-unit-tests-only-32chars"
TEST_ORGANIZATION_ID = "550e8400-e29b-41d4-a716-446655440000"


# ---------------------------------------------------------------------------
# Callback error handling tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.services.callback.get_settings")
@patch("app.services.callback.httpx.AsyncClient")
async def test_callback_continues_on_http_error(mock_client_cls, mock_settings):
    """Callback service handles HTTP errors gracefully without raising."""
    mock_settings.return_value = MagicMock(internal_secret="secret-32-characters-exactly!!")

    mock_client = AsyncMock()
    mock_client.post = AsyncMock(
        side_effect=httpx.HTTPStatusError(
            "Server Error",
            request=MagicMock(),
            response=MagicMock(status_code=500),
        )
    )
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=False)
    mock_client_cls.return_value = mock_client

    from app.services.callback import send_callback

    # Should NOT raise - error is handled internally
    await send_callback(
        callback_url="http://nginx/callback",
        correlation_id="corr-err",
        job_id="job-err",
        organization_id=TEST_ORGANIZATION_ID,
        pipeline="content_creation",
        status="completed",
    )


@pytest.mark.asyncio
@patch("app.services.callback.get_settings")
@patch("app.services.callback.httpx.AsyncClient")
async def test_callback_handles_connection_error(mock_client_cls, mock_settings):
    """Callback service handles connection errors gracefully."""
    mock_settings.return_value = MagicMock(internal_secret="secret-32-characters-exactly!!")

    mock_client = AsyncMock()
    mock_client.post = AsyncMock(
        side_effect=httpx.ConnectError("Connection refused")
    )
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=False)
    mock_client_cls.return_value = mock_client

    from app.services.callback import send_callback

    # Should NOT raise - error is handled internally
    await send_callback(
        callback_url="http://nginx/callback",
        correlation_id="corr-conn",
        job_id="job-conn",
        organization_id=TEST_ORGANIZATION_ID,
        pipeline="content_dna",
        status="failed",
    )


# ---------------------------------------------------------------------------
# Graph error handling tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.content_creation.planner.get_llm")
async def test_graph_handles_llm_exception(mock_llm):
    """Graph handles LLM exceptions and propagates them."""
    mock = MagicMock()
    structured_mock = AsyncMock()
    structured_mock.ainvoke = AsyncMock(side_effect=Exception("LLM API Error"))
    mock.with_structured_output = MagicMock(return_value=structured_mock)
    mock_llm.return_value = mock

    from app.agents.content_creation.planner import planner_node
    from app.agents.content_creation.state import ContentCreationState

    state: ContentCreationState = {
        "organization_id": TEST_ORGANIZATION_ID,
        "topic": "Test topic",
        "provider": "instagram_feed",
        "tone": "casual",
        "keywords": [],
        "language": "en-US",
        "style_profile": None,
        "rag_examples": [],
        "brief": None,
        "draft": None,
        "review_passed": False,
        "review_feedback": None,
        "retry_count": 0,
        "final_content": None,
        "callback_url": "http://nginx/callback",
        "correlation_id": "corr-001",
        "total_tokens": 0,
        "total_cost": 0.0,
        "agents_executed": [],
    }

    with pytest.raises(Exception, match="LLM API Error"):
        await planner_node(state)


# ---------------------------------------------------------------------------
# Job status tests
# ---------------------------------------------------------------------------


def test_job_status_handles_invalid_json():
    """Job status endpoint handles invalid JSON in Redis gracefully."""
    from fastapi import FastAPI
    from fastapi.testclient import TestClient

    from app.api.routes import router
    from app.shared.security import generate_namespaced_job_id

    test_settings = Settings(
        internal_secret=TEST_INTERNAL_SECRET,
        environment="development",
    )

    app = FastAPI()
    app.include_router(router)

    # Create proper async mock for Redis
    mock_redis = MagicMock()
    mock_redis.ping = AsyncMock(return_value=True)
    # Return invalid JSON - use AsyncMock for async method
    mock_redis.get = AsyncMock(return_value="not valid json {{{")
    mock_redis.zremrangebyscore = AsyncMock(return_value=0)
    mock_redis.zcard = AsyncMock(return_value=0)
    app.state.redis = mock_redis

    mock_conn = MagicMock()
    mock_conn.fetchval = AsyncMock(return_value=1)

    # Create async context manager for connection
    async_cm = MagicMock()
    async_cm.__aenter__ = AsyncMock(return_value=mock_conn)
    async_cm.__aexit__ = AsyncMock(return_value=None)

    mock_pool = MagicMock()
    mock_pool.acquire = MagicMock(return_value=async_cm)
    app.state.pg_pool = mock_pool

    # Generate a valid namespaced job ID
    job_id = generate_namespaced_job_id(TEST_ORGANIZATION_ID)

    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        # Use raise_server_exceptions=False to get 500 response instead of exception
        client = TestClient(app, raise_server_exceptions=False)
        response = client.get(
            f"/api/v1/jobs/{job_id}",
            params={"organization_id": TEST_ORGANIZATION_ID},
            headers={"X-Internal-Secret": TEST_INTERNAL_SECRET},
        )

        # Should return 500 due to JSON parse error
        assert response.status_code == 500


def test_job_status_requires_organization_id():
    """Job status endpoint requires organization_id query parameter."""
    from fastapi import FastAPI
    from fastapi.testclient import TestClient

    from app.api.routes import router
    from app.shared.security import generate_namespaced_job_id

    test_settings = Settings(
        internal_secret=TEST_INTERNAL_SECRET,
        environment="development",
    )

    app = FastAPI()
    app.include_router(router)

    mock_redis = MagicMock()
    mock_redis.ping = AsyncMock(return_value=True)
    mock_redis.get = AsyncMock(return_value=None)
    app.state.redis = mock_redis

    mock_conn = MagicMock()
    mock_conn.fetchval = AsyncMock(return_value=1)

    async_cm = MagicMock()
    async_cm.__aenter__ = AsyncMock(return_value=mock_conn)
    async_cm.__aexit__ = AsyncMock(return_value=None)

    mock_pool = MagicMock()
    mock_pool.acquire = MagicMock(return_value=async_cm)
    app.state.pg_pool = mock_pool

    job_id = generate_namespaced_job_id(TEST_ORGANIZATION_ID)

    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(app)
        # Missing organization_id parameter
        response = client.get(
            f"/api/v1/jobs/{job_id}",
            headers={"X-Internal-Secret": TEST_INTERNAL_SECRET},
        )

        # Should return 422 (validation error) for missing required param
        assert response.status_code == 422
