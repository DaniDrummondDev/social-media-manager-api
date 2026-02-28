"""SentimentAnalyzer agent — deep sentiment analysis with cultural context."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

from app.agents.social_listening.prompts import (
    SENTIMENT_ANALYZER_SYSTEM_PROMPT,
    SENTIMENT_CRISIS_BLOCK,
    SENTIMENT_STANDARD_BLOCK,
)
from app.agents.social_listening.state import SocialListeningState
from app.services.llm import get_llm
from app.shared.logging import get_logger


class DeepSentimentAnalysis(BaseModel):
    """Structured output returned by the SentimentAnalyzer LLM call."""

    sentiment: str = Field(
        description="Overall sentiment: positive, neutral, negative, or mixed",
    )
    sentiment_score: float = Field(
        ge=-1.0, le=1.0,
        description="Sentiment score from -1.0 to +1.0",
    )
    emotional_tones: list[str] = Field(
        description="Specific emotions detected: frustrated, grateful, angry, etc.",
    )
    irony_detected: bool = Field(
        description="True when sarcasm or irony is detected",
    )
    cultural_context: str = Field(
        description="Cultural nuances affecting interpretation",
    )
    key_concerns: list[str] = Field(
        description="Main issues or points raised by the author",
    )
    intensity: str = Field(
        description="Emotional intensity: mild, moderate, or strong",
    )


async def sentiment_analyzer_node(state: SocialListeningState) -> dict[str, Any]:
    """Perform deep sentiment analysis with cultural context and irony detection."""
    logger = get_logger(
        pipeline="social_listening",
        agent="sentiment_analyzer",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )

    classification = state["classification"]
    is_crisis = classification.get("is_crisis", False) if classification else False
    logger.info("SentimentAnalyzer started", is_crisis=is_crisis)

    crisis_block = SENTIMENT_CRISIS_BLOCK if is_crisis else SENTIMENT_STANDARD_BLOCK
    system_prompt = SENTIMENT_ANALYZER_SYSTEM_PROMPT.format(crisis_block=crisis_block)

    mention = state["mention"]

    parts: list[str] = [
        f"Mention content:\n{mention.get('content', '')}",
        f"\nPlatform: {mention.get('platform', 'unknown')}",
        f"Language: {state['language']}",
        f"\nClassification: {classification}",
    ]

    human_message = "\n".join(parts)

    llm = get_llm(temperature=0.3).with_structured_output(DeepSentimentAnalysis)
    analysis: DeepSentimentAnalysis = await llm.ainvoke([
        ("system", system_prompt),
        ("human", human_message),
    ])

    logger.info(
        "SentimentAnalyzer finished",
        sentiment=analysis.sentiment,
        score=analysis.sentiment_score,
        irony=analysis.irony_detected,
        intensity=analysis.intensity,
    )

    return {
        "sentiment_analysis": analysis.model_dump(),
        "agents_executed": ["sentiment_analyzer"],
    }
