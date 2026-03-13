"""Planner agent — produces a structured content brief."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

from app.agents.content_creation.prompts import PLANNER_SYSTEM_PROMPT
from app.agents.content_creation.state import ContentCreationState
from app.services.llm import get_llm
from app.shared.logging import get_logger
from app.shared.token_tracker import TokenTrackingCallback, estimate_cost


class ContentBrief(BaseModel):
    """Structured output returned by the Planner LLM call."""

    tone: str = Field(description="Tone / voice direction for the content")
    structure: str = Field(description="Content structure, e.g. 'hook -> body -> CTA'")
    target_audience: str = Field(description="Description of the target audience")
    cta_style: str = Field(description="CTA approach: direct, soft, playful, etc.")
    constraints: list[str] = Field(description="Hard constraints (char limits, compliance)")
    suggested_length: str = Field(description="short, medium or long")


async def planner_node(state: ContentCreationState) -> dict[str, Any]:
    """Analyse topic + context and produce a content brief."""
    logger = get_logger(
        pipeline="content_creation",
        agent="planner",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )
    logger.info("Planner started")

    # Build the human message with all available context
    parts: list[str] = [
        f"Topic: {state['topic']}",
        f"Tone requested: {state['tone']}",
        f"Target provider: {state['provider']}",
        f"Language: {state['language']}",
    ]
    if state["keywords"]:
        parts.append(f"Keywords: {', '.join(state['keywords'])}")
    if state.get("style_profile"):
        parts.append(f"Brand style profile: {state['style_profile']}")
    if state.get("rag_examples"):
        parts.append(
            "High-performing examples from this organisation:\n"
            + "\n---\n".join(str(ex) for ex in state["rag_examples"])
        )

    human_message = "\n\n".join(parts)

    tracker = TokenTrackingCallback()
    llm = get_llm(temperature=0.4, callbacks=[tracker]).with_structured_output(
        ContentBrief
    )
    brief: ContentBrief = await llm.ainvoke([
        ("system", PLANNER_SYSTEM_PROMPT),
        ("human", human_message),
    ])

    token_cost = estimate_cost(tracker.usage)

    logger.info(
        "Planner finished",
        brief_tone=brief.tone,
        tokens=tracker.usage.total_tokens,
        cost_usd=token_cost,
    )

    return {
        "brief": brief.model_dump(),
        "agents_executed": ["planner"],
        "total_tokens": tracker.usage.total_tokens,
        "total_cost": token_cost,
    }
