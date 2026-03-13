"""Tests for Social Listening individual agents - edge cases."""

from __future__ import annotations

from unittest.mock import AsyncMock, MagicMock, patch

import pytest

from app.agents.social_listening.mention_classifier import MentionClassification
from app.agents.social_listening.response_strategist import SuggestedResponse
from app.agents.social_listening.safety_checker import SafetyCheckResult
from app.agents.social_listening.sentiment_analyzer import DeepSentimentAnalysis
from app.agents.social_listening.state import SocialListeningState


# ---------------------------------------------------------------------------
# Fixtures and helpers
# ---------------------------------------------------------------------------


def _base_state() -> SocialListeningState:
    """Minimal valid state for Social Listening graph."""
    return {
        "organization_id": "org-sl-123",
        "mention": {
            "id": "mention-001",
            "content": "Great product!",
            "platform": "instagram",
            "author_username": "happy_user",
            "author_display_name": "Happy User",
            "author_follower_count": 1000,
            "url": "https://instagram.com/p/123",
            "published_at": "2026-02-25T10:00:00Z",
        },
        "brand_context": {
            "brand_name": "TestBrand",
            "industry": "tech",
            "guidelines": "Be friendly",
            "tone_preferences": "professional",
            "blacklisted_words": ["competitor"],
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


def _sample_classification(category="praise", is_crisis=False) -> MentionClassification:
    return MentionClassification(
        category=category,
        confidence=0.9,
        is_crisis=is_crisis,
        reasoning="Positive feedback",
        urgency_level="low",
    )


def _sample_sentiment(sentiment="positive") -> DeepSentimentAnalysis:
    return DeepSentimentAnalysis(
        sentiment=sentiment,
        sentiment_score=0.8,
        emotional_tones=["grateful"],
        irony_detected=False,
        cultural_context="Standard expression",
        key_concerns=[],
        intensity="moderate",
    )


def _mock_llm_for_node(structured_return=None):
    """Create a mock LLM with structured output support."""
    mock = MagicMock()
    if structured_return is not None:
        structured_mock = AsyncMock()
        structured_mock.ainvoke = AsyncMock(return_value=structured_return)
        mock.with_structured_output = MagicMock(return_value=structured_mock)
    return mock


# ---------------------------------------------------------------------------
# Mention Classifier tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.social_listening.mention_classifier.get_llm")
async def test_classifier_with_empty_mention_content(mock_llm):
    """Classifier handles empty mention content."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_classification())

    from app.agents.social_listening.mention_classifier import mention_classifier_node

    state = _base_state()
    state["mention"]["content"] = ""

    result = await mention_classifier_node(state)

    assert result["classification"]["category"] == "praise"
    assert "mention_classifier" in result["agents_executed"]


@pytest.mark.asyncio
@patch("app.agents.social_listening.mention_classifier.get_llm")
async def test_classifier_identifies_crisis(mock_llm):
    """Classifier correctly identifies crisis mentions."""
    crisis = _sample_classification(category="crisis", is_crisis=True)
    mock_llm.return_value = _mock_llm_for_node(structured_return=crisis)

    from app.agents.social_listening.mention_classifier import mention_classifier_node

    state = _base_state()
    state["mention"]["content"] = "Data breach! My info was leaked!"

    result = await mention_classifier_node(state)

    assert result["classification"]["category"] == "crisis"
    assert result["classification"]["is_crisis"] is True
    assert "mention_classifier" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Sentiment Analyzer tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.social_listening.sentiment_analyzer.get_llm")
async def test_sentiment_handles_multilingual_content(mock_llm):
    """Sentiment analyzer handles multilingual content."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_sentiment())

    from app.agents.social_listening.sentiment_analyzer import sentiment_analyzer_node

    state = _base_state()
    state["classification"] = _sample_classification().model_dump()
    state["mention"]["content"] = "Produto incrível! Amazing product! 製品が素晴らしい!"

    result = await sentiment_analyzer_node(state)

    assert result["sentiment_analysis"]["sentiment"] == "positive"
    assert "sentiment_analyzer" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Response Strategist tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.social_listening.response_strategist.get_llm")
async def test_response_strategist_with_crisis_category(mock_llm):
    """Response strategist handles crisis mentions with escalation."""
    response = SuggestedResponse(
        response_text="We're investigating this issue.",
        tone="urgent",
        strategy="acknowledge",
        escalation_needed=True,
        response_priority="immediate",
        alternative_responses=["Please contact support."],
    )
    mock_llm.return_value = _mock_llm_for_node(structured_return=response)

    from app.agents.social_listening.response_strategist import response_strategist_node

    state = _base_state()
    state["classification"] = _sample_classification(category="crisis", is_crisis=True).model_dump()
    state["sentiment_analysis"] = _sample_sentiment(sentiment="negative").model_dump()

    result = await response_strategist_node(state)

    assert result["suggested_response"]["escalation_needed"] is True
    assert result["suggested_response"]["response_priority"] == "immediate"
    assert "response_strategist" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Safety Checker tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.social_listening.safety_checker.get_llm")
async def test_safety_checker_approves_safe_response(mock_llm):
    """Safety checker approves safe responses."""
    safety = SafetyCheckResult(
        safety_passed=True,
        risk_level="safe",
        flagged_issues=[],
        sanitized_response=None,
        recommendation="approve",
    )
    mock_llm.return_value = _mock_llm_for_node(structured_return=safety)

    from app.agents.social_listening.safety_checker import safety_checker_node

    state = _base_state()
    state["classification"] = _sample_classification().model_dump()
    state["sentiment_analysis"] = _sample_sentiment().model_dump()
    state["suggested_response"] = {
        "response_text": "Thank you for the feedback!",
        "tone": "friendly",
        "strategy": "celebrate",
        "escalation_needed": False,
        "response_priority": "within_24h",
        "alternative_responses": [],
    }

    result = await safety_checker_node(state)

    assert result["safety_result"]["safety_passed"] is True
    assert result["safety_result"]["recommendation"] == "approve"
    assert "safety_checker" in result["agents_executed"]
