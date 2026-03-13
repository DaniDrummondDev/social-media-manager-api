"""Tests for the job status endpoint GET /api/v1/jobs/{job_id}.

Security Features:
- Requires X-Internal-Secret authentication
- Requires organization_id query parameter
- Job ID must belong to the requesting organization (namespace validation)
- Double-checks organization_id in stored data (defense in depth)
"""

from __future__ import annotations

import json
from unittest.mock import AsyncMock, MagicMock, patch

import pytest
from fastapi import FastAPI
from fastapi.testclient import TestClient

from app.api.routes import router
from app.shared.security import generate_namespaced_job_id, get_job_redis_key

TEST_INTERNAL_SECRET = "test-internal-secret-32-characters"
TEST_ORGANIZATION_ID = "550e8400-e29b-41d4-a716-446655440000"


# ---------------------------------------------------------------------------
# Fixtures
# ---------------------------------------------------------------------------


@pytest.fixture
def test_app() -> FastAPI:
    """Minimal FastAPI app with mocked Redis and PostgreSQL for endpoint tests."""
    application = FastAPI()
    application.include_router(router)

    mock_redis = MagicMock()
    mock_redis.ping = AsyncMock(return_value=True)
    mock_redis.set = AsyncMock(return_value=True)
    mock_redis.get = AsyncMock(return_value=None)
    mock_redis.zremrangebyscore = AsyncMock(return_value=0)
    mock_redis.zcard = AsyncMock(return_value=0)
    application.state.redis = mock_redis

    mock_conn = AsyncMock()
    mock_conn.fetchval = AsyncMock(return_value=1)
    mock_conn.__aenter__ = AsyncMock(return_value=mock_conn)
    mock_conn.__aexit__ = AsyncMock(return_value=None)

    mock_pool = MagicMock()
    mock_pool.acquire = MagicMock(return_value=mock_conn)
    application.state.pg_pool = mock_pool

    return application


@pytest.fixture
def test_settings():
    """Create test settings with internal_secret configured."""
    from app.config import Settings

    return Settings(
        internal_secret=TEST_INTERNAL_SECRET,
        environment="development",
    )


# ---------------------------------------------------------------------------
# Authentication Tests
# ---------------------------------------------------------------------------


def test_job_status_requires_auth(test_app: FastAPI, test_settings) -> None:
    """GET /api/v1/jobs/{job_id} returns 401 without authentication."""
    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(test_app)
        response = client.get(
            "/api/v1/jobs/some-job-id",
            params={"organization_id": TEST_ORGANIZATION_ID},
        )
        assert response.status_code == 401


# ---------------------------------------------------------------------------
# Job Status Tests
# ---------------------------------------------------------------------------


def test_job_status_returns_completed_when_exists(test_app: FastAPI, test_settings) -> None:
    """GET /api/v1/jobs/{job_id} returns 200 with status=completed and result."""
    job_id = generate_namespaced_job_id(TEST_ORGANIZATION_ID)
    stored_result = {"title": "Generated post", "hashtags": ["#ai", "#content"]}

    test_app.state.redis.get = AsyncMock(
        return_value=json.dumps({
            "status": "completed",
            "result": stored_result,
            "organization_id": TEST_ORGANIZATION_ID,
        })
    )

    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(test_app)
        response = client.get(
            f"/api/v1/jobs/{job_id}",
            params={"organization_id": TEST_ORGANIZATION_ID},
            headers={"X-Internal-Secret": TEST_INTERNAL_SECRET},
        )

        assert response.status_code == 200
        data = response.json()
        assert data["job_id"] == job_id
        assert data["status"] == "completed"
        assert data["result"] == stored_result


def test_job_status_returns_404_when_not_found(test_app: FastAPI, test_settings) -> None:
    """GET /api/v1/jobs/{job_id} returns 404 when Redis has no entry for the job."""
    job_id = generate_namespaced_job_id(TEST_ORGANIZATION_ID)

    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(test_app)
        response = client.get(
            f"/api/v1/jobs/{job_id}",
            params={"organization_id": TEST_ORGANIZATION_ID},
            headers={"X-Internal-Secret": TEST_INTERNAL_SECRET},
        )

        assert response.status_code == 404
        data = response.json()
        assert data["detail"] == "Job not found"


def test_job_status_returns_running_for_in_progress(test_app: FastAPI, test_settings) -> None:
    """GET /api/v1/jobs/{job_id} returns 200 with status=running while pipeline executes."""
    job_id = generate_namespaced_job_id(TEST_ORGANIZATION_ID)

    test_app.state.redis.get = AsyncMock(
        return_value=json.dumps({
            "status": "running",
            "result": None,
            "organization_id": TEST_ORGANIZATION_ID,
        })
    )

    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(test_app)
        response = client.get(
            f"/api/v1/jobs/{job_id}",
            params={"organization_id": TEST_ORGANIZATION_ID},
            headers={"X-Internal-Secret": TEST_INTERNAL_SECRET},
        )

        assert response.status_code == 200
        data = response.json()
        assert data["job_id"] == job_id
        assert data["status"] == "running"
        assert data["result"] is None


def test_job_status_uses_correct_redis_key_pattern(test_app: FastAPI, test_settings) -> None:
    """Verify the endpoint queries Redis with namespaced key pattern 'job:{org_id}:{job_id}'."""
    job_id = generate_namespaced_job_id(TEST_ORGANIZATION_ID)
    expected_key = get_job_redis_key(TEST_ORGANIZATION_ID, job_id)

    test_app.state.redis.get = AsyncMock(return_value=None)

    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(test_app)
        client.get(
            f"/api/v1/jobs/{job_id}",
            params={"organization_id": TEST_ORGANIZATION_ID},
            headers={"X-Internal-Secret": TEST_INTERNAL_SECRET},
        )

        test_app.state.redis.get.assert_called_once_with(expected_key)


def test_job_status_returns_failed_status(test_app: FastAPI, test_settings) -> None:
    """GET /api/v1/jobs/{job_id} returns 200 with status=failed when pipeline failed."""
    job_id = generate_namespaced_job_id(TEST_ORGANIZATION_ID)

    test_app.state.redis.get = AsyncMock(
        return_value=json.dumps({
            "status": "failed",
            "result": None,
            "organization_id": TEST_ORGANIZATION_ID,
        })
    )

    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(test_app)
        response = client.get(
            f"/api/v1/jobs/{job_id}",
            params={"organization_id": TEST_ORGANIZATION_ID},
            headers={"X-Internal-Secret": TEST_INTERNAL_SECRET},
        )

        assert response.status_code == 200
        data = response.json()
        assert data["job_id"] == job_id
        assert data["status"] == "failed"
        assert data["result"] is None


# ---------------------------------------------------------------------------
# Organization Isolation Tests
# ---------------------------------------------------------------------------


def test_job_status_rejects_wrong_organization(test_app: FastAPI, test_settings) -> None:
    """Job ID from one org should not be accessible by another org."""
    org1 = "550e8400-e29b-41d4-a716-446655440001"
    org2 = "550e8400-e29b-41d4-a716-446655440002"

    # Generate job ID for org1
    job_id = generate_namespaced_job_id(org1)

    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(test_app)
        # Try to access with org2
        response = client.get(
            f"/api/v1/jobs/{job_id}",
            params={"organization_id": org2},
            headers={"X-Internal-Secret": TEST_INTERNAL_SECRET},
        )

        # Should return 404 (not 403, to avoid information disclosure)
        assert response.status_code == 404


def test_job_status_rejects_org_mismatch_in_data(test_app: FastAPI, test_settings) -> None:
    """Job status rejects if stored org_id doesn't match request (defense in depth)."""
    job_id = generate_namespaced_job_id(TEST_ORGANIZATION_ID)
    different_org = "660e8400-e29b-41d4-a716-446655440000"

    # Redis returns data with different organization_id
    test_app.state.redis.get = AsyncMock(
        return_value=json.dumps({
            "status": "completed",
            "result": {"sensitive": "data"},
            "organization_id": different_org,  # Different org!
        })
    )

    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(test_app)
        response = client.get(
            f"/api/v1/jobs/{job_id}",
            params={"organization_id": TEST_ORGANIZATION_ID},
            headers={"X-Internal-Secret": TEST_INTERNAL_SECRET},
        )

        # Should return 404 even if job exists (org mismatch)
        assert response.status_code == 404
