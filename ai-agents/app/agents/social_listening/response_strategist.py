"""ResponseStrategist agent — crafts contextualised response suggestions."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

from app.agents.social_listening.prompts import (
    CATEGORY_STRATEGIES,
    DEFAULT_CATEGORY_STRATEGY,
    RESPONSE_STRATEGIST_SYSTEM_PROMPT,
)
from app.agents.social_listening.state import SocialListeningState
from app.services.llm import get_llm
from app.shared.logging import get_logger


class SuggestedResponse(BaseModel):
    """Structured output returned by the ResponseStrategist LLM call."""

    response_text: str = Field(
        description="The suggested response text to post",
    )
    tone: str = Field(
        description="Response tone: empathetic, professional, friendly, or urgent",
    )
    strategy: str = Field(
        description="Strategy: acknowledge, apologize, redirect, celebrate, or inform",
    )
    escalation_needed: bool = Field(
        description="True when human review or escalation is recommended",
    )
    response_priority: str = Field(
        description="Priority: immediate, within_1h, within_4h, within_24h, or low",
    )
    alternative_responses: list[str] = Field(
        description="Two alternative response options with different tones",
    )


async def response_strategist_node(state: SocialListeningState) -> dict[str, Any]:
    """Craft a contextualised response based on classification and sentiment."""
    logger = get_logger(
        pipeline="social_listening",
        agent="response_strategist",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )

    classification = state["classification"]
    category = classification.get("category", "complaint") if classification else "complaint"
    logger.info("ResponseStrategist started", category=category)

    category_strategy = CATEGORY_STRATEGIES.get(category, DEFAULT_CATEGORY_STRATEGY)
    system_prompt = RESPONSE_STRATEGIST_SYSTEM_PROMPT.format(
        category_strategy_block=category_strategy,
    )

    mention = state["mention"]
    brand = state["brand_context"]
    sentiment = state["sentiment_analysis"]

    parts: list[str] = [
        f"Mention content:\n{mention.get('content', '')}",
        f"\nPlatform: {mention.get('platform', 'unknown')}",
        f"Author: @{mention.get('author_username', 'unknown')}",
        f"Language: {state['language']}",
        f"\nClassification: {classification}",
        f"\nSentiment analysis: {sentiment}",
        f"\nBrand name: {brand.get('brand_name', '')}",
        f"Industry: {brand.get('industry', '')}",
        f"Brand guidelines: {brand.get('guidelines', '')}",
        f"Tone preferences: {brand.get('tone_preferences', '')}",
        f"Blacklisted words: {brand.get('blacklisted_words', [])}",
    ]

    human_message = "\n".join(parts)

    llm = get_llm(temperature=0.5).with_structured_output(SuggestedResponse)
    response: SuggestedResponse = await llm.ainvoke([
        ("system", system_prompt),
        ("human", human_message),
    ])

    logger.info(
        "ResponseStrategist finished",
        tone=response.tone,
        strategy=response.strategy,
        escalation=response.escalation_needed,
        priority=response.response_priority,
    )

    return {
        "suggested_response": response.model_dump(),
        "agents_executed": ["response_strategist"],
    }
