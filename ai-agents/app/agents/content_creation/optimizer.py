"""Optimizer agent — adapts the draft for the target social network."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

from app.agents.content_creation.prompts import (
    DEFAULT_PROVIDER_SPECS,
    OPTIMIZER_SYSTEM_PROMPT,
    PROVIDER_SPECS,
)
from app.agents.content_creation.state import ContentCreationState
from app.services.llm import get_llm
from app.shared.logging import get_logger
from app.shared.token_tracker import TokenTrackingCallback, estimate_cost


class OptimizedContent(BaseModel):
    """Structured output returned by the Optimizer LLM call."""

    title: str = Field(description="Short punchy headline")
    description: str = Field(description="Main body text within provider limits")
    hashtags: list[str] = Field(description="Relevant hashtags")
    cta_text: str = Field(description="Call-to-action for the provider")
    media_guidance: str = Field(description="Recommended aspect ratio and format tips")
    character_count: dict[str, int] = Field(
        description="Character counts, e.g. {'title': 45, 'description': 280}"
    )


async def optimizer_node(state: ContentCreationState) -> dict[str, Any]:
    """Optimise the approved draft for the target provider."""
    logger = get_logger(
        pipeline="content_creation",
        agent="optimizer",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )
    provider = state["provider"]
    logger.info("Optimizer started", provider=provider)

    provider_specs = PROVIDER_SPECS.get(provider, DEFAULT_PROVIDER_SPECS)
    system_prompt = OPTIMIZER_SYSTEM_PROMPT.format(
        provider=provider,
        provider_specs=provider_specs,
    )

    human_message = (
        f"Draft:\n{state['draft']}\n\n"
        f"Brief:\n{state['brief']}\n\n"
        f"Language: {state['language']}"
    )

    tracker = TokenTrackingCallback()
    llm = get_llm(temperature=0.3, callbacks=[tracker]).with_structured_output(
        OptimizedContent
    )
    optimized: OptimizedContent = await llm.ainvoke([
        ("system", system_prompt),
        ("human", human_message),
    ])

    token_cost = estimate_cost(tracker.usage)

    logger.info(
        "Optimizer finished",
        provider=provider,
        title_len=len(optimized.title),
        desc_len=len(optimized.description),
        hashtag_count=len(optimized.hashtags),
        tokens=tracker.usage.total_tokens,
        cost_usd=token_cost,
    )

    return {
        "final_content": optimized.model_dump(),
        "agents_executed": ["optimizer"],
        "total_tokens": tracker.usage.total_tokens,
        "total_cost": token_cost,
    }
