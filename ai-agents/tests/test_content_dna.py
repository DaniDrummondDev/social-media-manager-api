"""Tests for the Content DNA Deep Analysis pipeline.

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

from app.agents.content_dna.engagement_analyzer import EngagementCorrelations
from app.agents.content_dna.graph import build_content_dna_graph
from app.agents.content_dna.state import ContentDNAState
from app.agents.content_dna.style_analyzer import StylePatterns
from app.agents.content_dna.synthesizer import DNAProfile
from app.api.routes import router
from app.config import Settings

TEST_INTERNAL_SECRET = "test-internal-secret-for-unit-tests-only-32chars"
TEST_ORGANIZATION_ID = "550e8400-e29b-41d4-a716-446655440000"


# ---------------------------------------------------------------------------
# Sample data factories
# ---------------------------------------------------------------------------


def _sample_style_patterns(*, insufficient: bool = False) -> StylePatterns:
    return StylePatterns(
        tone_distribution={"casual": 0.6, "professional": 0.3, "witty": 0.1},
        vocabulary_clusters={
            "domain_terms": ["productivity", "workflow", "automation"],
            "emoji_frequency": "frequent",
            "slang": "moderate",
        },
        structure_patterns={
            "dominant_hook": "question",
            "avg_paragraph_length": "short",
            "cta_placement": "end",
        },
        recurring_themes=["productivity", "remote work", "AI tools"],
        insufficient_data=insufficient,
    )


def _sample_engagement_correlations() -> EngagementCorrelations:
    return EngagementCorrelations(
        tone_impact={"casual": {"avg_engagement_rate": 4.5, "vs_average": "+35%"}},
        structure_impact={"question_hooks": {"avg_engagement_rate": 5.2}},
        hashtag_impact={"optimal_count": 8, "diminishing_after": 15},
        timing_patterns={"best_day": "Tuesday", "best_hour": "10:00"},
        top_performing_patterns=[
            {"pattern": "casual tone + question hook", "impact": "+50% engagement"},
            {"pattern": "short paragraphs + emoji", "impact": "+30% saves"},
            {"pattern": "3-5 hashtags niche mix", "impact": "+25% reach"},
        ],
    )


def _sample_dna_profile() -> DNAProfile:
    return DNAProfile(
        tone_insights={"primary_voice": "casual", "engagement_boost": "+35%"},
        vocabulary_insights={"key_phrases": ["productivity", "workflow"], "avoid": ["synergy"]},
        structure_insights={"optimal_template": "question hook -> short body -> direct CTA"},
        engagement_drivers=[
            {"factor": "casual tone", "impact": "high"},
            {"factor": "question hooks", "impact": "high"},
        ],
        gaps_and_opportunities=["Video content underexplored", "Stories format unused"],
        recommendations=[
            "Use question hooks in 70%+ of posts",
            "Increase casual tone consistency",
            "Test video format for higher engagement",
        ],
        overall_confidence=0.78,
        sample_size=25,
    )


def _sample_contents(count: int = 10) -> list[dict]:
    return [
        {
            "id": f"content-{i}",
            "title": f"Post about topic {i}",
            "body": f"This is the body of post {i} about productivity and workflow.",
            "provider": "instagram_feed",
            "hashtags": ["#productivity", "#workflow"],
            "published_at": "2026-01-15T10:00:00Z",
        }
        for i in range(count)
    ]


def _sample_metrics(count: int = 10) -> list[dict]:
    return [
        {
            "content_id": f"content-{i}",
            "impressions": 1000 + i * 100,
            "reach": 800 + i * 80,
            "likes": 50 + i * 5,
            "comments": 10 + i,
            "shares": 5 + i,
            "saves": 3 + i,
            "engagement_rate": 3.5 + i * 0.2,
        }
        for i in range(count)
    ]


def _base_state(content_count: int = 10) -> ContentDNAState:
    """Minimal valid state for the Content DNA graph."""
    return {
        "organization_id": TEST_ORGANIZATION_ID,
        "published_contents": _sample_contents(content_count),
        "metrics": _sample_metrics(content_count),
        "current_style_profile": None,
        "time_window": "last_90_days",
        "style_patterns": None,
        "engagement_correlations": None,
        "dna_profile": None,
        "callback_url": "http://nginx/callback",
        "correlation_id": "corr-dna-001",
        "total_tokens": 0,
        "total_cost": 0.0,
        "agents_executed": [],
    }


def _mock_llm_structured(structured_return):
    """Create a mock LLM that supports .with_structured_output()."""
    mock = MagicMock()
    structured_mock = AsyncMock()
    structured_mock.ainvoke = AsyncMock(return_value=structured_return)
    mock.with_structured_output = MagicMock(return_value=structured_mock)
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
    """Minimal FastAPI app with mocked Redis."""
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
# Graph: Full flow
# ---------------------------------------------------------------------------


@patch("app.agents.content_dna.style_analyzer.get_llm")
@patch("app.agents.content_dna.engagement_analyzer.get_llm")
@patch("app.agents.content_dna.synthesizer.get_llm")
async def test_graph_full_flow(mock_synth_llm, mock_eng_llm, mock_style_llm):
    """StyleAnalyzer -> EngagementAnalyzer -> Synthesizer produces dna_profile."""
    mock_style_llm.return_value = _mock_llm_structured(_sample_style_patterns())
    mock_eng_llm.return_value = _mock_llm_structured(_sample_engagement_correlations())
    mock_synth_llm.return_value = _mock_llm_structured(_sample_dna_profile())

    graph = build_content_dna_graph()
    result = await graph.ainvoke(_base_state())

    assert result["dna_profile"] is not None
    assert result["dna_profile"]["overall_confidence"] == 0.78
    assert result["dna_profile"]["sample_size"] == 25
    assert len(result["dna_profile"]["recommendations"]) == 3
    assert "style_analyzer" in result["agents_executed"]
    assert "engagement_analyzer" in result["agents_executed"]
    assert "synthesizer" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Individual agents
# ---------------------------------------------------------------------------


@patch("app.agents.content_dna.style_analyzer.get_llm")
async def test_style_analyzer_produces_patterns(mock_llm):
    """StyleAnalyzer returns style_patterns dict."""
    mock_llm.return_value = _mock_llm_structured(_sample_style_patterns())

    from app.agents.content_dna.style_analyzer import style_analyzer_node

    result = await style_analyzer_node(_base_state())

    assert result["style_patterns"]["tone_distribution"]["casual"] == 0.6
    assert result["style_patterns"]["insufficient_data"] is False
    assert "style_analyzer" in result["agents_executed"]


@patch("app.agents.content_dna.engagement_analyzer.get_llm")
async def test_engagement_analyzer_receives_patterns(mock_llm):
    """EngagementAnalyzer uses style_patterns and produces correlations."""
    mock_llm.return_value = _mock_llm_structured(_sample_engagement_correlations())

    from app.agents.content_dna.engagement_analyzer import engagement_analyzer_node

    state = _base_state()
    state["style_patterns"] = _sample_style_patterns().model_dump()

    result = await engagement_analyzer_node(state)

    assert len(result["engagement_correlations"]["top_performing_patterns"]) == 3
    assert "engagement_analyzer" in result["agents_executed"]


@patch("app.agents.content_dna.synthesizer.get_llm")
async def test_synthesizer_produces_profile_with_confidence(mock_llm):
    """Synthesizer combines analyses into dna_profile with confidence score."""
    mock_llm.return_value = _mock_llm_structured(_sample_dna_profile())

    from app.agents.content_dna.synthesizer import synthesizer_node

    state = _base_state()
    state["style_patterns"] = _sample_style_patterns().model_dump()
    state["engagement_correlations"] = _sample_engagement_correlations().model_dump()

    result = await synthesizer_node(state)

    assert result["dna_profile"]["overall_confidence"] == 0.78
    assert len(result["dna_profile"]["gaps_and_opportunities"]) == 2
    assert "synthesizer" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Insufficient data
# ---------------------------------------------------------------------------


@patch("app.agents.content_dna.style_analyzer.get_llm")
@patch("app.agents.content_dna.engagement_analyzer.get_llm")
@patch("app.agents.content_dna.synthesizer.get_llm")
async def test_insufficient_data_still_produces_profile(
    mock_synth_llm,
    mock_eng_llm,
    mock_style_llm,
):
    """With < 5 contents, pipeline still completes with insufficient_data flag."""
    mock_style_llm.return_value = _mock_llm_structured(
        _sample_style_patterns(insufficient=True),
    )
    mock_eng_llm.return_value = _mock_llm_structured(_sample_engagement_correlations())

    low_confidence_profile = _sample_dna_profile()
    low_confidence_profile.overall_confidence = 0.3
    low_confidence_profile.sample_size = 3
    mock_synth_llm.return_value = _mock_llm_structured(low_confidence_profile)

    graph = build_content_dna_graph()
    result = await graph.ainvoke(_base_state(content_count=3))

    assert result["style_patterns"]["insufficient_data"] is True
    assert result["dna_profile"]["overall_confidence"] == 0.3
    assert result["dna_profile"]["sample_size"] == 3


# ---------------------------------------------------------------------------
# Endpoint tests
# ---------------------------------------------------------------------------


def test_dna_endpoint_requires_auth(client: TestClient, test_settings: Settings) -> None:
    """POST /api/v1/pipelines/content-dna returns 401 without auth."""
    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        response = client.post(
            "/api/v1/pipelines/content-dna",
            json={
                "organization_id": TEST_ORGANIZATION_ID,
                "correlation_id": "corr-dna-001",
                "callback_url": "http://nginx/callback",
                "published_contents": _sample_contents(5),
                "metrics": _sample_metrics(5),
            },
        )

        assert response.status_code == 401


def test_dna_endpoint_returns_202(auth_client: TestClient, test_settings: Settings) -> None:
    """POST /api/v1/pipelines/content-dna returns 202 with job_id."""
    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        response = auth_client.post(
            "/api/v1/pipelines/content-dna",
            json={
                "organization_id": TEST_ORGANIZATION_ID,
                "correlation_id": "corr-dna-001",
                "callback_url": "http://nginx/callback",
                "published_contents": _sample_contents(5),
                "metrics": _sample_metrics(5),
            },
        )

        assert response.status_code == 202
        data = response.json()
        assert "job_id" in data
        # Job ID now includes org hash prefix
        assert "_" in data["job_id"]


def test_dna_endpoint_validates_input(auth_client: TestClient, test_settings: Settings) -> None:
    """POST /api/v1/pipelines/content-dna rejects missing required fields."""
    with patch("app.middleware.auth.get_settings", return_value=test_settings):
        response = auth_client.post(
            "/api/v1/pipelines/content-dna",
            json={"organization_id": TEST_ORGANIZATION_ID},
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
