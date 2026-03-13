"""Reviewer agent — validates draft quality, brand safety and tone alignment."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

from app.agents.content_creation.prompts import REVIEWER_SYSTEM_PROMPT
from app.agents.content_creation.state import ContentCreationState
from app.services.llm import get_llm
from app.shared.logging import get_logger
from app.shared.token_tracker import TokenTrackingCallback, estimate_cost

MAX_RETRIES = 2


class ReviewResult(BaseModel):
    """Structured output returned by the Reviewer LLM call."""

    passed: bool = Field(description="True when all scores are >= 0.7")
    feedback: str = Field(description="Actionable feedback for the writer")
    brand_safety_score: float = Field(ge=0.0, le=1.0)
    tone_alignment_score: float = Field(ge=0.0, le=1.0)
    quality_score: float = Field(ge=0.0, le=1.0)


async def reviewer_node(state: ContentCreationState) -> dict[str, Any]:
    """Evaluate the draft against the brief and style profile."""
    logger = get_logger(
        pipeline="content_creation",
        agent="reviewer",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )
    logger.info("Reviewer started", retry_count=state["retry_count"])

    parts: list[str] = [
        f"Draft to review:\n{state['draft']}",
        f"Brief:\n{state['brief']}",
    ]
    if state.get("style_profile"):
        parts.append(f"Brand style profile: {state['style_profile']}")

    human_message = "\n\n".join(parts)

    tracker = TokenTrackingCallback()
    llm = get_llm(temperature=0.2, callbacks=[tracker]).with_structured_output(
        ReviewResult
    )
    review: ReviewResult = await llm.ainvoke([
        ("system", REVIEWER_SYSTEM_PROMPT),
        ("human", human_message),
    ])

    new_retry_count = state["retry_count"] + (0 if review.passed else 1)

    # Force forward if retries exhausted
    passed = review.passed or new_retry_count >= MAX_RETRIES
    token_cost = estimate_cost(tracker.usage)

    logger.info(
        "Reviewer finished",
        passed=passed,
        forced=not review.passed and passed,
        brand_safety=review.brand_safety_score,
        tone_alignment=review.tone_alignment_score,
        quality=review.quality_score,
        retry_count=new_retry_count,
        tokens=tracker.usage.total_tokens,
        cost_usd=token_cost,
    )

    return {
        "review_passed": passed,
        "review_feedback": review.feedback,
        "retry_count": new_retry_count,
        "agents_executed": ["reviewer"],
        "total_tokens": tracker.usage.total_tokens,
        "total_cost": token_cost,
    }


def review_router(state: ContentCreationState) -> str:
    """Route to writer (retry) or optimizer (proceed) based on review outcome."""
    if state["review_passed"]:
        return "optimizer"
    return "writer"
