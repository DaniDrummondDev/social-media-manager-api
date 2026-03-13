"""EngagementAnalyzer agent — analyzes engagement patterns from performance metrics."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

from app.agents.content_dna.prompts import ENGAGEMENT_ANALYZER_SYSTEM_PROMPT
from app.agents.content_dna.state import ContentDNAState
from app.services.llm import get_llm
from app.shared.logging import get_logger
from app.shared.token_tracker import TokenTrackingCallback, estimate_cost


class EngagementCorrelations(BaseModel):
    """Structured output returned by the EngagementAnalyzer LLM call."""

    tone_impact: dict[str, Any] = Field(
        description="How tone choices affect engagement metrics",
    )
    structure_impact: dict[str, Any] = Field(
        description="How content structure correlates with performance",
    )
    hashtag_impact: dict[str, Any] = Field(
        description="Hashtag count and type vs engagement patterns",
    )
    timing_patterns: dict[str, Any] = Field(
        description="Day/time trends if available",
    )
    top_performing_patterns: list[dict[str, Any]] = Field(
        description="Top 3 most impactful style choices with data",
    )


async def engagement_analyzer_node(state: ContentDNAState) -> dict[str, Any]:
    """Analyze engagement patterns from metrics and content data.

    This node runs in parallel with style_analyzer. It independently analyzes
    engagement metrics and content characteristics. The synthesizer will later
    correlate findings from both analyzers.
    """
    logger = get_logger(
        pipeline="content_dna",
        agent="engagement_analyzer",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )

    metrics = state["metrics"]
    contents = state["published_contents"]
    logger.info(
        "EngagementAnalyzer started",
        metrics_count=len(metrics),
        contents_count=len(contents),
    )

    # Build content index for cross-reference
    contents_by_id = {c.get("id"): c for c in contents if c.get("id")}

    parts: list[str] = [
        f"Analyzing engagement patterns for {len(metrics)} content pieces.",
        f"Time window: {state['time_window']}",
        "\n--- ENGAGEMENT METRICS ---",
    ]

    # Add metrics with content context (limit to avoid token overflow)
    for i, metric in enumerate(metrics[:50]):
        content_id = metric.get("content_id", "N/A")
        content = contents_by_id.get(content_id, {})

        parts.append(
            f"\n[Content {i + 1}]\n"
            f"Provider: {content.get('provider', 'unknown')}\n"
            f"Title: {content.get('title', '')[:100]}\n"
            f"Body preview: {content.get('body', '')[:200]}\n"
            f"Hashtags: {content.get('hashtags', [])}\n"
            f"Published: {content.get('published_at', 'N/A')}\n"
            f"Impressions: {metric.get('impressions', 0)}\n"
            f"Reach: {metric.get('reach', 0)}\n"
            f"Likes: {metric.get('likes', 0)}\n"
            f"Comments: {metric.get('comments', 0)}\n"
            f"Shares: {metric.get('shares', 0)}\n"
            f"Saves: {metric.get('saves', 0)}\n"
            f"Engagement rate: {metric.get('engagement_rate', 0.0):.2f}%"
        )

    human_message = "\n".join(parts)

    tracker = TokenTrackingCallback()
    llm = get_llm(temperature=0.3, callbacks=[tracker]).with_structured_output(
        EngagementCorrelations
    )
    correlations: EngagementCorrelations = await llm.ainvoke([
        ("system", ENGAGEMENT_ANALYZER_SYSTEM_PROMPT),
        ("human", human_message),
    ])

    token_cost = estimate_cost(tracker.usage)

    logger.info(
        "EngagementAnalyzer finished",
        top_patterns_count=len(correlations.top_performing_patterns),
        tokens=tracker.usage.total_tokens,
        cost_usd=token_cost,
    )

    return {
        "engagement_correlations": correlations.model_dump(),
        "agents_executed": ["engagement_analyzer"],
        "total_tokens": tracker.usage.total_tokens,
        "total_cost": token_cost,
    }
