"""Tests for the Content Creation pipeline.

Security Features:
- All endpoint tests use X-Internal-Secret authentication
- Organization IDs are valid UUIDs
"""

from __future__ import annotations

import json
from unittest.mock import AsyncMock, MagicMock, patch

import pytest
from fastapi import FastAPI
from fastapi.testclient import TestClient

from app.agents.content_creation.graph import build_content_creation_graph
from app.agents.content_creation.optimizer import OptimizedContent
from app.agents.content_creation.planner import ContentBrief
from app.agents.content_creation.reviewer import ReviewResult
from app.agents.content_creation.state import ContentCreationState
from app.api.routes import router
from app.config import Settings

TEST_INTERNAL_SECRET = "test-internal-secret-for-unit-tests-only-32chars"
TEST_ORGANIZATION_ID = "550e8400-e29b-41d4-a716-446655440000"


# ---------------------------------------------------------------------------
# Fixtures
# ---------------------------------------------------------------------------


def _sample_brief() -> ContentBrief:
    return ContentBrief(
        tone="casual",
        structure="hook -> body -> CTA",
        target_audience="Young professionals aged 25-35",
        cta_style="direct",
        constraints=["Max 2200 characters"],
        suggested_length="medium",
    )


def _sample_review_pass() -> ReviewResult:
    return ReviewResult(
        passed=True,
        feedback="Looks great!",
        brand_safety_score=0.9,
        tone_alignment_score=0.85,
        quality_score=0.88,
    )


def _sample_review_fail() -> ReviewResult:
    return ReviewResult(
        passed=False,
        feedback="Tone is too formal. Use shorter sentences.",
        brand_safety_score=0.9,
        tone_alignment_score=0.4,
        quality_score=0.6,
    )


def _sample_optimized() -> OptimizedContent:
    return OptimizedContent(
        title="Boost Your Productivity Today",
        description="Here's how top performers structure their morning...",
        hashtags=["#Productivity", "#MorningRoutine", "#Growth"],
        cta_text="Save this post for later!",
        media_guidance="Square image (1:1) with bold text overlay",
        character_count={"title": 32, "description": 54},
    )


def _base_state() -> ContentCreationState:
    """Minimal valid state to feed into the graph."""
    return {
        "organization_id": TEST_ORGANIZATION_ID,
        "topic": "Morning productivity tips",
        "provider": "instagram_feed",
        "tone": "casual",
        "keywords": ["productivity", "morning"],
        "language": "en-US",
        "style_profile": {"tone": "casual", "emoji_frequency": "frequent"},
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


def _mock_llm_for_node(structured_return=None, content_return=None):
    """Create a mock LLM that supports both .ainvoke and .with_structured_output."""
    mock = MagicMock()

    if structured_return is not None:
        structured_mock = AsyncMock()
        structured_mock.ainvoke = AsyncMock(return_value=structured_return)
        mock.with_structured_output = MagicMock(return_value=structured_mock)

    if content_return is not None:
        response = MagicMock()
        response.content = content_return
        mock.ainvoke = AsyncMock(return_value=response)

    return mock


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
def test_app(test_settings: Settings) -> FastAPI:
    """Minimal FastAPI app with mocked Redis for pipeline tests."""
    application = FastAPI()
    application.include_router(router)

    mock_redis = MagicMock()
    mock_redis.ping = AsyncMock(return_value=True)
    mock_redis.set = AsyncMock(return_value=True)
    mock_redis.get = AsyncMock(return_value=json.dumps({
        "status": "running",
        "result": None,
        "organization_id": TEST_ORGANIZATION_ID,
    }))
    mock_redis.zremrangebyscore = AsyncMock(return_value=0)
    mock_redis.zcard = AsyncMock(return_value=0)
    mock_redis.zadd = AsyncMock(return_value=1)
    mock_redis.expire = AsyncMock(return_value=True)
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
def auth_client(test_app: FastAPI, test_settings: Settings) -> TestClient:
    """Create authenticated test client."""
    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        client = TestClient(test_app)
        client.headers["X-Internal-Secret"] = TEST_INTERNAL_SECRET
        return client


@pytest.fixture
def client(test_app: FastAPI) -> TestClient:
    """Create unauthenticated test client."""
    return TestClient(test_app)


# ---------------------------------------------------------------------------
# Graph: Full flow (happy path)
# ---------------------------------------------------------------------------


@patch("app.agents.content_creation.planner.get_llm")
@patch("app.agents.content_creation.writer.get_llm")
@patch("app.agents.content_creation.reviewer.get_llm")
@patch("app.agents.content_creation.optimizer.get_llm")
async def test_graph_full_flow_happy_path(
    mock_opt_llm,
    mock_rev_llm,
    mock_wrt_llm,
    mock_pln_llm,
):
    """Planner -> Writer -> Reviewer(pass) -> Optimizer."""
    mock_pln_llm.return_value = _mock_llm_for_node(structured_return=_sample_brief())
    mock_wrt_llm.return_value = _mock_llm_for_node(content_return="A great draft about productivity.")
    mock_rev_llm.return_value = _mock_llm_for_node(structured_return=_sample_review_pass())
    mock_opt_llm.return_value = _mock_llm_for_node(structured_return=_sample_optimized())

    graph = build_content_creation_graph()
    result = await graph.ainvoke(_base_state())

    assert result["final_content"] is not None
    assert result["final_content"]["title"] == "Boost Your Productivity Today"
    assert result["review_passed"] is True
    assert result["retry_count"] == 0
    assert "planner" in result["agents_executed"]
    assert "writer" in result["agents_executed"]
    assert "reviewer" in result["agents_executed"]
    assert "optimizer" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Graph: Retry once then pass
# ---------------------------------------------------------------------------


@patch("app.agents.content_creation.planner.get_llm")
@patch("app.agents.content_creation.writer.get_llm")
@patch("app.agents.content_creation.reviewer.get_llm")
@patch("app.agents.content_creation.optimizer.get_llm")
async def test_graph_retry_once(
    mock_opt_llm,
    mock_rev_llm,
    mock_wrt_llm,
    mock_pln_llm,
):
    """Reviewer rejects first draft, writer retries, then passes."""
    mock_pln_llm.return_value = _mock_llm_for_node(structured_return=_sample_brief())
    mock_wrt_llm.return_value = _mock_llm_for_node(content_return="Improved draft.")

    # First call: fail. Second call: pass.
    reviewer_mock_fail = _mock_llm_for_node(structured_return=_sample_review_fail())
    reviewer_mock_pass = _mock_llm_for_node(structured_return=_sample_review_pass())
    mock_rev_llm.side_effect = [reviewer_mock_fail, reviewer_mock_pass]

    mock_opt_llm.return_value = _mock_llm_for_node(structured_return=_sample_optimized())

    graph = build_content_creation_graph()
    result = await graph.ainvoke(_base_state())

    assert result["final_content"] is not None
    assert result["retry_count"] == 1
    # Writer should appear twice (initial + retry)
    assert result["agents_executed"].count("writer") == 2
    assert result["agents_executed"].count("reviewer") == 2


# ---------------------------------------------------------------------------
# Graph: Max retries exhausted — force forward
# ---------------------------------------------------------------------------


@patch("app.agents.content_creation.planner.get_llm")
@patch("app.agents.content_creation.writer.get_llm")
@patch("app.agents.content_creation.reviewer.get_llm")
@patch("app.agents.content_creation.optimizer.get_llm")
async def test_graph_max_retries_exhausted(
    mock_opt_llm,
    mock_rev_llm,
    mock_wrt_llm,
    mock_pln_llm,
):
    """After 2 rejections, reviewer forces forward to optimizer."""
    mock_pln_llm.return_value = _mock_llm_for_node(structured_return=_sample_brief())
    mock_wrt_llm.return_value = _mock_llm_for_node(content_return="Draft attempt.")

    # Always fail
    mock_rev_llm.return_value = _mock_llm_for_node(structured_return=_sample_review_fail())

    mock_opt_llm.return_value = _mock_llm_for_node(structured_return=_sample_optimized())

    graph = build_content_creation_graph()
    result = await graph.ainvoke(_base_state())

    assert result["final_content"] is not None
    assert result["retry_count"] == 2
    # review_passed forced to True after max retries
    assert result["review_passed"] is True


# ---------------------------------------------------------------------------
# Individual agents
# ---------------------------------------------------------------------------


@patch("app.agents.content_creation.planner.get_llm")
async def test_planner_returns_brief(mock_llm):
    """Planner node returns a valid brief dict."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_brief())

    from app.agents.content_creation.planner import planner_node

    result = await planner_node(_base_state())

    assert result["brief"]["tone"] == "casual"
    assert result["brief"]["structure"] == "hook -> body -> CTA"
    assert "planner" in result["agents_executed"]


@patch("app.agents.content_creation.writer.get_llm")
async def test_writer_uses_feedback_on_retry(mock_llm):
    """Writer incorporates review feedback when retrying."""
    mock_llm.return_value = _mock_llm_for_node(content_return="Revised draft.")

    from app.agents.content_creation.writer import writer_node

    state = _base_state()
    state["brief"] = _sample_brief().model_dump()
    state["review_feedback"] = "Use shorter sentences."
    state["retry_count"] = 1

    result = await writer_node(state)

    assert result["draft"] == "Revised draft."
    # Verify the LLM was called with feedback in the system prompt
    call_args = mock_llm.return_value.ainvoke.call_args
    system_msg = call_args[0][0][0][1]  # first message, content
    assert "shorter sentences" in system_msg


@patch("app.agents.content_creation.reviewer.get_llm")
async def test_reviewer_returns_structured_scores(mock_llm):
    """Reviewer returns review with scores."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_review_pass())

    from app.agents.content_creation.reviewer import reviewer_node

    state = _base_state()
    state["draft"] = "Some draft content."
    state["brief"] = _sample_brief().model_dump()

    result = await reviewer_node(state)

    assert result["review_passed"] is True
    assert "reviewer" in result["agents_executed"]


@patch("app.agents.content_creation.optimizer.get_llm")
async def test_optimizer_instagram_format(mock_llm):
    """Optimizer produces content adapted for instagram_feed."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_optimized())

    from app.agents.content_creation.optimizer import optimizer_node

    state = _base_state()
    state["draft"] = "Approved draft."
    state["brief"] = _sample_brief().model_dump()

    result = await optimizer_node(state)

    assert result["final_content"]["title"] == "Boost Your Productivity Today"
    assert len(result["final_content"]["hashtags"]) == 3
    assert "optimizer" in result["agents_executed"]


@patch("app.agents.content_creation.optimizer.get_llm")
async def test_optimizer_tiktok_format(mock_llm):
    """Optimizer receives tiktok as provider."""
    optimized_tiktok = _sample_optimized()
    mock_llm.return_value = _mock_llm_for_node(structured_return=optimized_tiktok)

    from app.agents.content_creation.optimizer import optimizer_node

    state = _base_state()
    state["provider"] = "tiktok"
    state["draft"] = "Approved draft."
    state["brief"] = _sample_brief().model_dump()

    result = await optimizer_node(state)

    assert result["final_content"] is not None
    # Verify the LLM received tiktok specs in the system prompt
    call_args = mock_llm.return_value.with_structured_output.return_value.ainvoke.call_args
    system_msg = call_args[0][0][0][1]
    assert "tiktok" in system_msg.lower() or "9:16" in system_msg


# ---------------------------------------------------------------------------
# Endpoint tests
# ---------------------------------------------------------------------------


def test_pipeline_endpoint_requires_auth(client: TestClient, test_settings: Settings) -> None:
    """POST /api/v1/pipelines/content-creation returns 401 without auth."""
    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        response = client.post(
            "/api/v1/pipelines/content-creation",
            json={
                "organization_id": TEST_ORGANIZATION_ID,
                "correlation_id": "corr-001",
                "callback_url": "http://nginx/callback",
                "topic": "Test topic",
                "provider": "instagram_feed",
            },
        )

        assert response.status_code == 401


def test_pipeline_endpoint_returns_202(auth_client: TestClient, test_settings: Settings) -> None:
    """POST /api/v1/pipelines/content-creation returns 202 with job_id."""
    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        response = auth_client.post(
            "/api/v1/pipelines/content-creation",
            json={
                "organization_id": TEST_ORGANIZATION_ID,
                "correlation_id": "corr-001",
                "callback_url": "http://nginx/callback",
                "topic": "Test topic",
                "provider": "instagram_feed",
            },
        )

        assert response.status_code == 202
        data = response.json()
        assert "job_id" in data
        # Job ID now includes org hash prefix (8 chars + underscore + 36 char UUID)
        assert "_" in data["job_id"]


def test_pipeline_endpoint_validates_input(auth_client: TestClient, test_settings: Settings) -> None:
    """POST /api/v1/pipelines/content-creation rejects invalid input."""
    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        response = auth_client.post(
            "/api/v1/pipelines/content-creation",
            json={"topic": "Missing required fields"},
        )

        assert response.status_code == 422


def test_health_public_endpoint(client: TestClient) -> None:
    """GET /health is public and hides pipeline list for security."""
    response = client.get("/health")
    data = response.json()

    assert response.status_code == 200
    assert data["status"] == "healthy"
    # Public endpoint hides pipelines for security
    assert data["pipelines"] == []
