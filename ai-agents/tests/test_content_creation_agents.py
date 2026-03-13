"""Tests for Content Creation individual agents - edge cases."""

from __future__ import annotations

from unittest.mock import AsyncMock, MagicMock, patch

import pytest

from app.agents.content_creation.optimizer import OptimizedContent
from app.agents.content_creation.planner import ContentBrief
from app.agents.content_creation.reviewer import ReviewResult
from app.agents.content_creation.state import ContentCreationState


# ---------------------------------------------------------------------------
# Fixtures and helpers
# ---------------------------------------------------------------------------


def _base_state() -> ContentCreationState:
    """Minimal valid state to feed into agents."""
    return {
        "organization_id": "org-123",
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
        "callback_url": "http://localhost/callback",
        "correlation_id": "corr-001",
        "total_tokens": 0,
        "total_cost": 0.0,
        "agents_executed": [],
    }


def _sample_brief() -> ContentBrief:
    return ContentBrief(
        tone="casual",
        structure="hook -> body -> CTA",
        target_audience="Young professionals aged 25-35",
        cta_style="direct",
        constraints=["Max 2200 characters"],
        suggested_length="medium",
    )


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


# ---------------------------------------------------------------------------
# Planner agent tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.content_creation.planner.get_llm")
async def test_planner_handles_empty_topic(mock_llm):
    """Planner handles empty topic gracefully and produces valid brief."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_brief())

    from app.agents.content_creation.planner import planner_node

    state = _base_state()
    state["topic"] = ""

    result = await planner_node(state)

    assert result["brief"]["tone"] == "casual"
    assert result["brief"]["structure"] is not None
    assert "planner" in result["agents_executed"]


@pytest.mark.asyncio
@patch("app.agents.content_creation.planner.get_llm")
async def test_planner_handles_unicode_topic(mock_llm):
    """Planner processes topics with unicode characters correctly."""
    brief = _sample_brief()
    mock_llm.return_value = _mock_llm_for_node(structured_return=brief)

    from app.agents.content_creation.planner import planner_node

    state = _base_state()
    state["topic"] = "Dicas de produtividade matinal 🌅 — rotina de sucesso"
    state["language"] = "pt-BR"

    result = await planner_node(state)

    assert result["brief"]["tone"] == "casual"
    assert "planner" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Writer agent tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.content_creation.writer.get_llm")
async def test_writer_produces_draft(mock_llm):
    """Writer produces draft content based on brief."""
    mock_llm.return_value = _mock_llm_for_node(content_return="Draft content here.")

    from app.agents.content_creation.writer import writer_node

    state = _base_state()
    state["brief"] = _sample_brief().model_dump()

    result = await writer_node(state)

    assert result["draft"] == "Draft content here."
    assert "writer" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Reviewer agent tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.content_creation.reviewer.get_llm")
async def test_reviewer_handles_low_scores(mock_llm):
    """Reviewer handles low quality scores and rejects draft."""
    review = ReviewResult(
        passed=False,
        feedback="Needs improvement",
        brand_safety_score=0.3,
        tone_alignment_score=0.4,
        quality_score=0.5,
    )
    mock_llm.return_value = _mock_llm_for_node(structured_return=review)

    from app.agents.content_creation.reviewer import reviewer_node

    state = _base_state()
    state["draft"] = "Some draft content."
    state["brief"] = _sample_brief().model_dump()

    result = await reviewer_node(state)

    assert result["review_passed"] is False
    assert result["retry_count"] == 1
    assert "reviewer" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Optimizer agent tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.content_creation.optimizer.get_llm")
async def test_optimizer_produces_final_content(mock_llm):
    """Optimizer produces optimized final content."""
    optimized = OptimizedContent(
        title="Great Title",
        description="Optimized description.",
        hashtags=["#productivity"],
        cta_text="Learn more",
        media_guidance="Square format",
        character_count={"title": 11, "description": 22},
    )
    mock_llm.return_value = _mock_llm_for_node(structured_return=optimized)

    from app.agents.content_creation.optimizer import optimizer_node

    state = _base_state()
    state["draft"] = "Approved draft."
    state["brief"] = _sample_brief().model_dump()

    result = await optimizer_node(state)

    assert result["final_content"]["title"] == "Great Title"
    assert "optimizer" in result["agents_executed"]
