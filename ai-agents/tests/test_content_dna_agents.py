"""Tests for Content DNA individual agents - edge cases."""

from __future__ import annotations

from unittest.mock import AsyncMock, MagicMock, patch

import pytest

from app.agents.content_dna.engagement_analyzer import EngagementCorrelations
from app.agents.content_dna.state import ContentDNAState
from app.agents.content_dna.style_analyzer import StylePatterns
from app.agents.content_dna.synthesizer import DNAProfile


# ---------------------------------------------------------------------------
# Fixtures and helpers
# ---------------------------------------------------------------------------


def _base_state() -> ContentDNAState:
    """Minimal valid state for Content DNA graph."""
    return {
        "organization_id": "org-dna-123",
        "published_contents": [
            {"id": "post-1", "text": "Great productivity tips!"},
            {"id": "post-2", "text": "Morning routines for success."},
        ],
        "metrics": [
            {"id": "post-1", "likes": 150, "comments": 20, "shares": 10},
            {"id": "post-2", "likes": 200, "comments": 30, "shares": 15},
        ],
        "current_style_profile": None,
        "time_window": "last_90_days",
        "style_patterns": None,
        "engagement_correlations": None,
        "dna_profile": None,
        "callback_url": "http://localhost/callback",
        "correlation_id": "corr-dna-001",
        "total_tokens": 0,
        "total_cost": 0.0,
        "agents_executed": [],
    }


def _sample_style_patterns() -> StylePatterns:
    return StylePatterns(
        tone_distribution={"casual": 0.6, "professional": 0.4},
        vocabulary_clusters={
            "domain_terms": ["productivity", "workflow"],
            "emoji_frequency": "moderate",
        },
        structure_patterns={
            "dominant_hook": "question",
            "avg_paragraph_length": "short",
        },
        recurring_themes=["productivity", "lifestyle"],
        insufficient_data=False,
    )


def _sample_engagement() -> EngagementCorrelations:
    return EngagementCorrelations(
        tone_impact={"casual": {"avg_engagement": 5.2}, "professional": {"avg_engagement": 3.8}},
        structure_impact={"short_hook": {"performance": "high"}},
        hashtag_impact={"optimal_count": 5, "best_categories": ["productivity"]},
        timing_patterns={"best_days": ["monday", "wednesday"], "best_hours": ["09:00", "18:00"]},
        top_performing_patterns=[
            {"pattern": "questions", "impact": 0.35},
            {"pattern": "emojis", "impact": 0.25},
        ],
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
# Style Analyzer tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.content_dna.style_analyzer.get_llm")
async def test_style_analyzer_with_minimal_contents(mock_llm):
    """Style analyzer handles minimal content samples."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_style_patterns())

    from app.agents.content_dna.style_analyzer import style_analyzer_node

    state = _base_state()
    state["published_contents"] = [{"id": "1", "text": "Single post"}]

    result = await style_analyzer_node(state)

    assert result["style_patterns"] is not None
    assert result["style_patterns"]["insufficient_data"] is False
    assert "style_analyzer" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Engagement Analyzer tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.content_dna.engagement_analyzer.get_llm")
async def test_engagement_analyzer_with_zero_metrics(mock_llm):
    """Engagement analyzer handles zero engagement metrics."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_engagement())

    from app.agents.content_dna.engagement_analyzer import engagement_analyzer_node

    state = _base_state()
    state["metrics"] = [{"id": "1", "likes": 0, "comments": 0, "shares": 0}]
    state["style_patterns"] = _sample_style_patterns().model_dump()

    result = await engagement_analyzer_node(state)

    assert result["engagement_correlations"] is not None
    assert "engagement_analyzer" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Synthesizer tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.content_dna.synthesizer.get_llm")
async def test_synthesizer_produces_dna_profile(mock_llm):
    """Synthesizer combines style and engagement into DNA profile."""
    dna = DNAProfile(
        tone_insights={"primary_voice": "casual_professional", "impact_scores": {"casual": 0.7}},
        vocabulary_insights={"key_terms": ["productivity"], "emoji_impact": "positive"},
        structure_insights={"optimal_hook": "question", "cta_placement": "end"},
        engagement_drivers=[
            {"driver": "questions", "impact": 0.35},
            {"driver": "emojis", "impact": 0.25},
        ],
        gaps_and_opportunities=["video content", "carousel posts"],
        recommendations=[
            "Use more questions in hooks",
            "Increase emoji usage",
            "Try video format",
        ],
        overall_confidence=0.82,
        sample_size=50,
    )
    mock_llm.return_value = _mock_llm_for_node(structured_return=dna)

    from app.agents.content_dna.synthesizer import synthesizer_node

    state = _base_state()
    state["style_patterns"] = _sample_style_patterns().model_dump()
    state["engagement_correlations"] = _sample_engagement().model_dump()

    result = await synthesizer_node(state)

    assert result["dna_profile"]["tone_insights"]["primary_voice"] == "casual_professional"
    assert result["dna_profile"]["overall_confidence"] == 0.82
    assert "synthesizer" in result["agents_executed"]
