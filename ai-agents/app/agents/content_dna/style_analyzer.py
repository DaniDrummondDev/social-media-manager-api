"""StyleAnalyzer agent — analyses tone, vocabulary, structure and recurring themes."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

from app.agents.content_dna.prompts import STYLE_ANALYZER_SYSTEM_PROMPT
from app.agents.content_dna.state import ContentDNAState
from app.services.llm import get_llm
from app.shared.logging import get_logger
from app.shared.token_tracker import TokenTrackingCallback, estimate_cost

MIN_CONTENTS_FOR_FULL_ANALYSIS = 5


class StylePatterns(BaseModel):
    """Structured output returned by the StyleAnalyzer LLM call."""

    tone_distribution: dict[str, float] = Field(
        description="Tone breakdown, e.g. {'casual': 0.6, 'professional': 0.3}",
    )
    vocabulary_clusters: dict[str, Any] = Field(
        description="Domain terms, emoji usage, slang, recurring phrases",
    )
    structure_patterns: dict[str, Any] = Field(
        description="Hook types, CTA placement, paragraph length patterns",
    )
    recurring_themes: list[str] = Field(
        description="Top recurring themes or motifs",
    )
    insufficient_data: bool = Field(
        description="True when fewer than 5 contents were analysed",
    )


async def style_analyzer_node(state: ContentDNAState) -> dict[str, Any]:
    """Analyse writing patterns across published contents."""
    logger = get_logger(
        pipeline="content_dna",
        agent="style_analyzer",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )

    contents = state["published_contents"]
    logger.info("StyleAnalyzer started", content_count=len(contents))

    # Build human message with content corpus
    parts: list[str] = [
        f"Time window: {state['time_window']}",
        f"Number of contents: {len(contents)}",
    ]

    if state.get("current_style_profile"):
        parts.append(f"Existing style profile: {state['current_style_profile']}")

    if len(contents) < MIN_CONTENTS_FOR_FULL_ANALYSIS:
        parts.append(
            f"WARNING: Only {len(contents)} contents available "
            f"(minimum {MIN_CONTENTS_FOR_FULL_ANALYSIS} for full analysis). "
            "Provide partial analysis with insufficient_data=true."
        )

    # Add content samples (limit to avoid token overflow)
    for i, content in enumerate(contents[:50]):
        parts.append(
            f"--- Content {i + 1} ---\n"
            f"Provider: {content.get('provider', 'unknown')}\n"
            f"Title: {content.get('title', '')}\n"
            f"Body: {content.get('body', '')}\n"
            f"Hashtags: {content.get('hashtags', [])}\n"
            f"Published: {content.get('published_at', '')}"
        )

    human_message = "\n\n".join(parts)

    tracker = TokenTrackingCallback()
    llm = get_llm(temperature=0.3, callbacks=[tracker]).with_structured_output(
        StylePatterns
    )
    patterns: StylePatterns = await llm.ainvoke([
        ("system", STYLE_ANALYZER_SYSTEM_PROMPT),
        ("human", human_message),
    ])

    token_cost = estimate_cost(tracker.usage)

    logger.info(
        "StyleAnalyzer finished",
        themes_count=len(patterns.recurring_themes),
        insufficient=patterns.insufficient_data,
        tokens=tracker.usage.total_tokens,
        cost_usd=token_cost,
    )

    return {
        "style_patterns": patterns.model_dump(),
        "agents_executed": ["style_analyzer"],
        "total_tokens": tracker.usage.total_tokens,
        "total_cost": token_cost,
    }
