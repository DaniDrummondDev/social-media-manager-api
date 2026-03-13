"""Tests for health check endpoints.

The /health endpoint is public (no auth required) for container orchestration.
The /health/detailed endpoint requires authentication and shows full info.
"""

from __future__ import annotations

from unittest.mock import AsyncMock, MagicMock, patch

import pytest
from fastapi import FastAPI
from fastapi.testclient import TestClient

from app.api.routes import router


@pytest.fixture
def test_app() -> FastAPI:
    """Create a minimal FastAPI app with mocked dependencies for testing."""
    application = FastAPI()
    application.include_router(router)

    # Mock Redis
    mock_redis = AsyncMock()
    mock_redis.ping = AsyncMock(return_value=True)
    application.state.redis = mock_redis

    # Mock PostgreSQL pool
    mock_conn = AsyncMock()
    mock_conn.fetchval = AsyncMock(return_value=1)
    mock_conn.__aenter__ = AsyncMock(return_value=mock_conn)
    mock_conn.__aexit__ = AsyncMock(return_value=None)

    mock_pool = MagicMock()
    mock_pool.acquire = MagicMock(return_value=mock_conn)
    application.state.pg_pool = mock_pool

    return application


@pytest.fixture
def client(test_app: FastAPI) -> TestClient:
    """Create a test client from the test app."""
    return TestClient(test_app)


# ---------------------------------------------------------------------------
# Public Health Endpoint Tests
# ---------------------------------------------------------------------------


def test_health_returns_200(client: TestClient) -> None:
    """GET /health returns 200 with healthy status."""
    response = client.get("/health")

    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "healthy"


def test_health_response_structure(client: TestClient) -> None:
    """GET /health returns all expected fields."""
    response = client.get("/health")
    data = response.json()

    assert "status" in data
    assert "service" in data
    assert "version" in data
    assert "pipelines" in data
    assert data["service"] == "ai-agents"
    assert data["version"] == "0.2.0"  # Updated version
    assert isinstance(data["pipelines"], list)


def test_health_hides_pipelines_for_security(client: TestClient) -> None:
    """GET /health returns empty pipelines list (security: don't expose internals)."""
    response = client.get("/health")
    data = response.json()

    # Public endpoint hides pipeline list
    assert data["pipelines"] == []


# ---------------------------------------------------------------------------
# Authenticated Detailed Health Endpoint Tests
# ---------------------------------------------------------------------------


def test_health_detailed_requires_auth(test_app: FastAPI) -> None:
    """GET /health/detailed returns 401 without authentication."""
    from app.config import Settings

    test_settings = Settings(
        internal_secret="test-secret-32-characters-long!!",
        environment="development",
    )

    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(test_app)
        response = client.get("/health/detailed")
        assert response.status_code == 401


def test_health_detailed_returns_pipelines_when_authenticated(test_app: FastAPI) -> None:
    """GET /health/detailed returns full pipeline list when authenticated."""
    from app.config import Settings

    test_settings = Settings(
        internal_secret="test-secret-32-characters-long!!",
        environment="development",
    )

    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(test_app)
        response = client.get(
            "/health/detailed",
            headers={"X-Internal-Secret": "test-secret-32-characters-long!!"},
        )

        assert response.status_code == 200
        data = response.json()
        assert "content_creation" in data["pipelines"]
        assert "content_dna" in data["pipelines"]
        assert "social_listening" in data["pipelines"]
        assert "visual_adaptation" in data["pipelines"]


# ---------------------------------------------------------------------------
# Readiness Endpoint Tests (Public)
# ---------------------------------------------------------------------------


def test_ready_returns_200_when_dependencies_ok(client: TestClient) -> None:
    """GET /ready returns 200 with ready=true when Redis and PostgreSQL are ok."""
    response = client.get("/ready")

    assert response.status_code == 200
    data = response.json()
    assert data["ready"] is True
    assert data["redis"] == "ok"
    assert data["postgres"] == "ok"


def test_ready_reports_redis_error(test_app: FastAPI) -> None:
    """GET /ready reports redis error when Redis is unavailable."""
    test_app.state.redis.ping = AsyncMock(side_effect=ConnectionError("Redis down"))
    client = TestClient(test_app)

    response = client.get("/ready")
    data = response.json()

    assert data["redis"] == "error"
    assert data["ready"] is False
