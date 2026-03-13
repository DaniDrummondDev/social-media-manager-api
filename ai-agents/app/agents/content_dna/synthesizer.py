"""ProfileSynthesizer agent — combines analyses into a unified DNA profile."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

from app.agents.content_dna.prompts import SYNTHESIZER_SYSTEM_PROMPT
from app.agents.content_dna.state import ContentDNAState
from app.services.llm import get_llm
from app.shared.logging import get_logger
from app.shared.token_tracker import TokenTrackingCallback, estimate_cost


class DNAProfile(BaseModel):
    """Structured output returned by the ProfileSynthesizer LLM call."""

    tone_insights: dict[str, Any] = Field(
        description="Voice identity with performance impact per tone variant",
    )
    vocabulary_insights: dict[str, Any] = Field(
        description="Language patterns with engagement correlation",
    )
    structure_insights: dict[str, Any] = Field(
        description="Optimal content structure templates with data",
    )
    engagement_drivers: list[dict[str, Any]] = Field(
        description="Top engagement factors ranked by impact",
    )
    gaps_and_opportunities: list[str] = Field(
        description="Unexplored content types or formats",
    )
    recommendations: list[str] = Field(
        description="Exactly 3 actionable recommendations",
    )
    overall_confidence: float = Field(
        ge=0.0,
        le=1.0,
        description="Reliability score based on sample size and data quality",
    )
    sample_size: int = Field(
        description="Number of contents analysed",
    )


async def synthesizer_node(state: ContentDNAState) -> dict[str, Any]:
    """Combine style patterns and engagement correlations into a DNA profile."""
    logger = get_logger(
        pipeline="content_dna",
        agent="synthesizer",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )

    sample_size = len(state["published_contents"])
    logger.info("ProfileSynthesizer started", sample_size=sample_size)

    human_message = (
        f"Style patterns:\n{state['style_patterns']}\n\n"
        f"Engagement correlations:\n{state['engagement_correlations']}\n\n"
        f"Sample size: {sample_size} contents\n"
        f"Time window: {state['time_window']}"
    )

    if state.get("current_style_profile"):
        human_message += (
            f"\n\nExisting style profile for reference:\n"
            f"{state['current_style_profile']}"
        )

    tracker = TokenTrackingCallback()
    llm = get_llm(temperature=0.4, callbacks=[tracker]).with_structured_output(
        DNAProfile
    )
    profile: DNAProfile = await llm.ainvoke([
        ("system", SYNTHESIZER_SYSTEM_PROMPT),
        ("human", human_message),
    ])

    token_cost = estimate_cost(tracker.usage)

    logger.info(
        "ProfileSynthesizer finished",
        confidence=profile.overall_confidence,
        drivers_count=len(profile.engagement_drivers),
        recommendations_count=len(profile.recommendations),
        tokens=tracker.usage.total_tokens,
        cost_usd=token_cost,
    )

    return {
        "dna_profile": profile.model_dump(),
        "agents_executed": ["synthesizer"],
        "total_tokens": tracker.usage.total_tokens,
        "total_cost": token_cost,
    }
