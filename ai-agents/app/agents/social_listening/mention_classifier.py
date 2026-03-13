"""MentionClassifier agent — categorises a social media mention."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

from app.agents.social_listening.prompts import MENTION_CLASSIFIER_SYSTEM_PROMPT
from app.agents.social_listening.state import SocialListeningState
from app.services.llm import get_llm
from app.shared.logging import get_logger
from app.shared.token_tracker import TokenTrackingCallback, estimate_cost


class MentionClassification(BaseModel):
    """Structured output returned by the MentionClassifier LLM call."""

    category: str = Field(
        description="Mention category: praise, complaint, question, crisis, or spam",
    )
    confidence: float = Field(
        ge=0.0, le=1.0,
        description="Classification confidence score",
    )
    is_crisis: bool = Field(
        description="True when the mention is categorised as a crisis",
    )
    reasoning: str = Field(
        description="Brief explanation of the classification decision",
    )
    urgency_level: str = Field(
        description="Urgency: critical, high, medium, or low",
    )


async def mention_classifier_node(state: SocialListeningState) -> dict[str, Any]:
    """Classify the mention into a category and assess urgency."""
    logger = get_logger(
        pipeline="social_listening",
        agent="mention_classifier",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )
    logger.info("MentionClassifier started")

    mention = state["mention"]
    brand = state["brand_context"]

    parts: list[str] = [
        f"Platform: {mention.get('platform', 'unknown')}",
        f"Author: @{mention.get('author_username', 'unknown')}",
        f"Author display name: {mention.get('author_display_name', '')}",
        f"Author followers: {mention.get('author_follower_count', 0)}",
        f"Mention URL: {mention.get('url', '')}",
        f"Published at: {mention.get('published_at', '')}",
        f"\nMention content:\n{mention.get('content', '')}",
        f"\nBrand name: {brand.get('brand_name', '')}",
        f"Industry: {brand.get('industry', '')}",
        f"Language: {state['language']}",
    ]

    human_message = "\n".join(parts)

    tracker = TokenTrackingCallback()
    llm = get_llm(temperature=0.2, callbacks=[tracker]).with_structured_output(MentionClassification)
    classification: MentionClassification = await llm.ainvoke([
        ("system", MENTION_CLASSIFIER_SYSTEM_PROMPT),
        ("human", human_message),
    ])

    token_cost = estimate_cost(tracker.usage)

    logger.info(
        "MentionClassifier finished",
        category=classification.category,
        confidence=classification.confidence,
        is_crisis=classification.is_crisis,
        urgency=classification.urgency_level,
        tokens=tracker.usage.total_tokens,
        cost_usd=token_cost,
    )

    return {
        "classification": classification.model_dump(),
        "agents_executed": ["mention_classifier"],
        "total_tokens": tracker.usage.total_tokens,
        "total_cost": token_cost,
    }
