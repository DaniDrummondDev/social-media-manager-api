"""Tests for the Social Listening Intelligence pipeline."""

from __future__ import annotations

import json
from unittest.mock import AsyncMock, MagicMock, patch

import pytest
from fastapi import FastAPI
from fastapi.testclient import TestClient

from app.agents.social_listening.graph import build_social_listening_graph
from app.agents.social_listening.mention_classifier import MentionClassification
from app.agents.social_listening.response_strategist import SuggestedResponse
from app.agents.social_listening.safety_checker import SafetyCheckResult
from app.agents.social_listening.sentiment_analyzer import DeepSentimentAnalysis
from app.agents.social_listening.state import SocialListeningState
from app.api.routes import router


# ---------------------------------------------------------------------------
# Sample data factories
# ---------------------------------------------------------------------------


def _sample_classification(
    *,
    category: str = "praise",
    is_crisis: bool = False,
    urgency: str = "low",
) -> MentionClassification:
    return MentionClassification(
        category=category,
        confidence=0.92,
        is_crisis=is_crisis,
        reasoning="Positive feedback about the product quality",
        urgency_level=urgency,
    )


def _sample_sentiment(
    *,
    sentiment: str = "positive",
    score: float = 0.85,
    irony: bool = False,
    intensity: str = "moderate",
) -> DeepSentimentAnalysis:
    return DeepSentimentAnalysis(
        sentiment=sentiment,
        sentiment_score=score,
        emotional_tones=["grateful", "hopeful"],
        irony_detected=irony,
        cultural_context="Standard Brazilian Portuguese expression of satisfaction",
        key_concerns=["product quality", "customer service"],
        intensity=intensity,
    )


def _sample_response(
    *,
    tone: str = "friendly",
    strategy: str = "celebrate",
    escalation: bool = False,
    priority: str = "within_24h",
) -> SuggestedResponse:
    return SuggestedResponse(
        response_text="Obrigado pelo feedback! Ficamos felizes que voce gostou.",
        tone=tone,
        strategy=strategy,
        escalation_needed=escalation,
        response_priority=priority,
        alternative_responses=[
            "Que bom que gostou! Conte sempre conosco.",
            "Muito obrigado! Sua opiniao e muito importante para nos.",
        ],
    )


def _sample_safety_pass() -> SafetyCheckResult:
    return SafetyCheckResult(
        safety_passed=True,
        risk_level="safe",
        flagged_issues=[],
        sanitized_response=None,
        recommendation="approve",
    )


def _sample_safety_fail() -> SafetyCheckResult:
    return SafetyCheckResult(
        safety_passed=False,
        risk_level="high_risk",
        flagged_issues=["contains_promise", "legal_risk"],
        sanitized_response=None,
        recommendation="block",
    )


def _base_state() -> SocialListeningState:
    """Minimal valid state for the Social Listening graph."""
    return {
        "organization_id": "org-789",
        "mention": {
            "id": "mention-001",
            "content": "Adorei o produto da @marca! Qualidade incrivel.",
            "platform": "instagram",
            "author_username": "usuario_feliz",
            "author_display_name": "Usuario Feliz",
            "author_follower_count": 1500,
            "url": "https://instagram.com/p/abc123",
            "published_at": "2026-02-25T14:30:00Z",
        },
        "brand_context": {
            "brand_name": "MarcaXYZ",
            "industry": "e-commerce",
            "guidelines": "Always be respectful and professional",
            "tone_preferences": "friendly, empathetic",
            "blacklisted_words": ["competitor_name", "lawsuit"],
        },
        "language": "pt-BR",
        "classification": None,
        "sentiment_analysis": None,
        "suggested_response": None,
        "safety_result": None,
        "callback_url": "http://localhost/callback",
        "correlation_id": "corr-sl-001",
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
def test_app() -> FastAPI:
    """Minimal FastAPI app with mocked Redis."""
    application = FastAPI()
    application.include_router(router)

    mock_redis = AsyncMock()
    mock_redis.ping = AsyncMock(return_value=True)
    mock_redis.set = AsyncMock(return_value=True)
    mock_redis.get = AsyncMock(return_value=json.dumps({
        "status": "running",
        "result": None,
    }))
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
def client(test_app: FastAPI) -> TestClient:
    return TestClient(test_app)


# ---------------------------------------------------------------------------
# Graph: Full flow — praise
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.social_listening.mention_classifier.get_llm")
@patch("app.agents.social_listening.sentiment_analyzer.get_llm")
@patch("app.agents.social_listening.response_strategist.get_llm")
@patch("app.agents.social_listening.safety_checker.get_llm")
async def test_graph_full_flow_praise(
    mock_safety_llm,
    mock_response_llm,
    mock_sentiment_llm,
    mock_classifier_llm,
):
    """MentionClassifier -> SentimentAnalyzer -> ResponseStrategist -> SafetyChecker for praise."""
    mock_classifier_llm.return_value = _mock_llm_structured(_sample_classification())
    mock_sentiment_llm.return_value = _mock_llm_structured(_sample_sentiment())
    mock_response_llm.return_value = _mock_llm_structured(_sample_response())
    mock_safety_llm.return_value = _mock_llm_structured(_sample_safety_pass())

    graph = build_social_listening_graph()
    result = await graph.ainvoke(_base_state())

    assert result["classification"] is not None
    assert result["classification"]["category"] == "praise"
    assert result["classification"]["is_crisis"] is False
    assert result["sentiment_analysis"]["sentiment"] == "positive"
    assert result["sentiment_analysis"]["sentiment_score"] == 0.85
    assert result["suggested_response"]["tone"] == "friendly"
    assert result["safety_result"]["safety_passed"] is True
    assert result["safety_result"]["recommendation"] == "approve"
    assert "mention_classifier" in result["agents_executed"]
    assert "sentiment_analyzer" in result["agents_executed"]
    assert "response_strategist" in result["agents_executed"]
    assert "safety_checker" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Graph: Full flow — crisis
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.social_listening.mention_classifier.get_llm")
@patch("app.agents.social_listening.sentiment_analyzer.get_llm")
@patch("app.agents.social_listening.response_strategist.get_llm")
@patch("app.agents.social_listening.safety_checker.get_llm")
async def test_graph_full_flow_crisis(
    mock_safety_llm,
    mock_response_llm,
    mock_sentiment_llm,
    mock_classifier_llm,
):
    """Crisis mentions flow through all agents with escalation needed."""
    mock_classifier_llm.return_value = _mock_llm_structured(
        _sample_classification(category="crisis", is_crisis=True, urgency="critical"),
    )
    mock_sentiment_llm.return_value = _mock_llm_structured(
        _sample_sentiment(sentiment="negative", score=-0.9, intensity="strong"),
    )
    mock_response_llm.return_value = _mock_llm_structured(
        _sample_response(tone="urgent", strategy="acknowledge", escalation=True, priority="immediate"),
    )
    mock_safety_llm.return_value = _mock_llm_structured(_sample_safety_pass())

    graph = build_social_listening_graph()
    state = _base_state()
    state["mention"]["content"] = "Data breach at @marca! My personal info was leaked!"
    state["mention"]["author_follower_count"] = 100000

    result = await graph.ainvoke(state)

    assert result["classification"]["category"] == "crisis"
    assert result["classification"]["is_crisis"] is True
    assert result["sentiment_analysis"]["sentiment"] == "negative"
    assert result["suggested_response"]["escalation_needed"] is True
    assert result["suggested_response"]["response_priority"] == "immediate"
    assert len(result["agents_executed"]) == 4


# ---------------------------------------------------------------------------
# Graph: Safety blocks response
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.social_listening.mention_classifier.get_llm")
@patch("app.agents.social_listening.sentiment_analyzer.get_llm")
@patch("app.agents.social_listening.response_strategist.get_llm")
@patch("app.agents.social_listening.safety_checker.get_llm")
async def test_graph_safety_blocks_response(
    mock_safety_llm,
    mock_response_llm,
    mock_sentiment_llm,
    mock_classifier_llm,
):
    """SafetyChecker blocks a response with high_risk issues."""
    mock_classifier_llm.return_value = _mock_llm_structured(
        _sample_classification(category="complaint", urgency="medium"),
    )
    mock_sentiment_llm.return_value = _mock_llm_structured(
        _sample_sentiment(sentiment="negative", score=-0.6),
    )
    mock_response_llm.return_value = _mock_llm_structured(_sample_response())
    mock_safety_llm.return_value = _mock_llm_structured(_sample_safety_fail())

    graph = build_social_listening_graph()
    result = await graph.ainvoke(_base_state())

    assert result["safety_result"]["safety_passed"] is False
    assert result["safety_result"]["risk_level"] == "high_risk"
    assert result["safety_result"]["recommendation"] == "block"
    assert "contains_promise" in result["safety_result"]["flagged_issues"]
    assert "legal_risk" in result["safety_result"]["flagged_issues"]


# ---------------------------------------------------------------------------
# Individual agents
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.social_listening.mention_classifier.get_llm")
async def test_mention_classifier_produces_classification(mock_llm):
    """MentionClassifier returns classification dict."""
    mock_llm.return_value = _mock_llm_structured(_sample_classification())

    from app.agents.social_listening.mention_classifier import mention_classifier_node

    result = await mention_classifier_node(_base_state())

    assert result["classification"]["category"] == "praise"
    assert result["classification"]["confidence"] == 0.92
    assert result["classification"]["is_crisis"] is False
    assert "mention_classifier" in result["agents_executed"]


@pytest.mark.asyncio
@patch("app.agents.social_listening.sentiment_analyzer.get_llm")
async def test_sentiment_analyzer_with_crisis_context(mock_llm):
    """SentimentAnalyzer uses crisis block when is_crisis is True."""
    mock_llm.return_value = _mock_llm_structured(
        _sample_sentiment(sentiment="negative", score=-0.9, intensity="strong"),
    )

    from app.agents.social_listening.sentiment_analyzer import sentiment_analyzer_node

    state = _base_state()
    state["classification"] = _sample_classification(
        category="crisis",
        is_crisis=True,
        urgency="critical",
    ).model_dump()

    result = await sentiment_analyzer_node(state)

    assert result["sentiment_analysis"]["sentiment"] == "negative"
    assert result["sentiment_analysis"]["sentiment_score"] == -0.9
    assert result["sentiment_analysis"]["intensity"] == "strong"
    assert "sentiment_analyzer" in result["agents_executed"]


@pytest.mark.asyncio
@patch("app.agents.social_listening.response_strategist.get_llm")
async def test_response_strategist_crafts_response(mock_llm):
    """ResponseStrategist uses category strategy and brand context."""
    mock_llm.return_value = _mock_llm_structured(_sample_response())

    from app.agents.social_listening.response_strategist import response_strategist_node

    state = _base_state()
    state["classification"] = _sample_classification().model_dump()
    state["sentiment_analysis"] = _sample_sentiment().model_dump()

    result = await response_strategist_node(state)

    assert result["suggested_response"]["tone"] == "friendly"
    assert result["suggested_response"]["strategy"] == "celebrate"
    assert len(result["suggested_response"]["alternative_responses"]) == 2
    assert "response_strategist" in result["agents_executed"]


@pytest.mark.asyncio
@patch("app.agents.social_listening.safety_checker.get_llm")
async def test_safety_checker_validates_response(mock_llm):
    """SafetyChecker validates response against brand guidelines."""
    mock_llm.return_value = _mock_llm_structured(_sample_safety_pass())

    from app.agents.social_listening.safety_checker import safety_checker_node

    state = _base_state()
    state["classification"] = _sample_classification().model_dump()
    state["sentiment_analysis"] = _sample_sentiment().model_dump()
    state["suggested_response"] = _sample_response().model_dump()

    result = await safety_checker_node(state)

    assert result["safety_result"]["safety_passed"] is True
    assert result["safety_result"]["risk_level"] == "safe"
    assert result["safety_result"]["recommendation"] == "approve"
    assert result["safety_result"]["flagged_issues"] == []
    assert "safety_checker" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Irony detection
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.social_listening.mention_classifier.get_llm")
@patch("app.agents.social_listening.sentiment_analyzer.get_llm")
@patch("app.agents.social_listening.response_strategist.get_llm")
@patch("app.agents.social_listening.safety_checker.get_llm")
async def test_irony_detected_in_sentiment(
    mock_safety_llm,
    mock_response_llm,
    mock_sentiment_llm,
    mock_classifier_llm,
):
    """Ironic mentions are detected and sentiment adjusted accordingly."""
    mock_classifier_llm.return_value = _mock_llm_structured(
        _sample_classification(category="complaint", urgency="medium"),
    )
    mock_sentiment_llm.return_value = _mock_llm_structured(
        _sample_sentiment(sentiment="negative", score=-0.4, irony=True),
    )
    mock_response_llm.return_value = _mock_llm_structured(
        _sample_response(tone="empathetic", strategy="apologize"),
    )
    mock_safety_llm.return_value = _mock_llm_structured(_sample_safety_pass())

    graph = build_social_listening_graph()
    state = _base_state()
    state["mention"]["content"] = "Adoro como voces nunca respondem. Muito bom o atendimento."

    result = await graph.ainvoke(state)

    assert result["sentiment_analysis"]["irony_detected"] is True
    assert result["sentiment_analysis"]["sentiment"] == "negative"


# ---------------------------------------------------------------------------
# Endpoint tests
# ---------------------------------------------------------------------------


def test_social_listening_endpoint_returns_202(client: TestClient) -> None:
    """POST /api/v1/pipelines/social-listening returns 202 with job_id."""
    response = client.post(
        "/api/v1/pipelines/social-listening",
        json={
            "organization_id": "org-789",
            "correlation_id": "corr-sl-001",
            "callback_url": "http://localhost/callback",
            "mention": {
                "id": "mention-001",
                "content": "Great product!",
                "platform": "twitter",
                "author_username": "user123",
                "author_display_name": "User",
                "author_follower_count": 500,
                "url": "https://twitter.com/status/123",
                "published_at": "2026-02-25T10:00:00Z",
            },
            "brand_context": {
                "brand_name": "TestBrand",
                "industry": "tech",
                "guidelines": "Be friendly",
                "tone_preferences": "professional",
                "blacklisted_words": [],
            },
        },
    )

    assert response.status_code == 202
    data = response.json()
    assert "job_id" in data


def test_social_listening_endpoint_validates_input(client: TestClient) -> None:
    """POST /api/v1/pipelines/social-listening rejects missing required fields."""
    response = client.post(
        "/api/v1/pipelines/social-listening",
        json={"organization_id": "org-789"},
    )

    assert response.status_code == 422


def test_health_shows_social_listening_registered(client: TestClient) -> None:
    """GET /health lists social_listening in registered pipelines."""
    response = client.get("/health")
    data = response.json()

    assert "social_listening" in data["pipelines"]
    assert "content_creation" in data["pipelines"]
    assert "content_dna" in data["pipelines"]
