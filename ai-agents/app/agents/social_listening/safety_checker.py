"""SafetyChecker agent — validates brand safety before returning the response."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

from app.agents.social_listening.prompts import SAFETY_CHECKER_SYSTEM_PROMPT
from app.agents.social_listening.state import SocialListeningState
from app.services.llm import get_llm
from app.shared.logging import get_logger
from app.shared.token_tracker import TokenTrackingCallback, estimate_cost


class SafetyCheckResult(BaseModel):
    """Structured output returned by the SafetyChecker LLM call."""

    safety_passed: bool = Field(
        description="True when the response passes all safety checks",
    )
    risk_level: str = Field(
        description="Risk level: safe, low_risk, medium_risk, or high_risk",
    )
    flagged_issues: list[str] = Field(
        description="Issues found: contains_promise, legal_risk, tone_mismatch, etc.",
    )
    sanitized_response: str | None = Field(
        description="Cleaned version if minor issues (low_risk only)",
    )
    recommendation: str = Field(
        description="Action: approve, review_needed, or block",
    )


async def safety_checker_node(state: SocialListeningState) -> dict[str, Any]:
    """Validate the suggested response against brand safety guidelines."""
    logger = get_logger(
        pipeline="social_listening",
        agent="safety_checker",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )
    logger.info("SafetyChecker started")

    mention = state["mention"]
    brand = state["brand_context"]
    classification = state["classification"]
    suggested = state["suggested_response"]

    parts: list[str] = [
        f"Suggested response:\n{suggested.get('response_text', '') if suggested else ''}",
        f"\nOriginal mention:\n{mention.get('content', '')}",
        f"\nClassification: {classification}",
        f"\nBrand name: {brand.get('brand_name', '')}",
        f"Brand guidelines: {brand.get('guidelines', '')}",
        f"Tone preferences: {brand.get('tone_preferences', '')}",
        f"Blacklisted words: {brand.get('blacklisted_words', [])}",
        f"\nResponse tone: {suggested.get('tone', '') if suggested else ''}",
        f"Response strategy: {suggested.get('strategy', '') if suggested else ''}",
    ]

    human_message = "\n".join(parts)

    tracker = TokenTrackingCallback()
    llm = get_llm(temperature=0.1, callbacks=[tracker]).with_structured_output(SafetyCheckResult)
    result: SafetyCheckResult = await llm.ainvoke([
        ("system", SAFETY_CHECKER_SYSTEM_PROMPT),
        ("human", human_message),
    ])

    token_cost = estimate_cost(tracker.usage)

    logger.info(
        "SafetyChecker finished",
        safety_passed=result.safety_passed,
        risk_level=result.risk_level,
        recommendation=result.recommendation,
        issues_count=len(result.flagged_issues),
        tokens=tracker.usage.total_tokens,
        cost_usd=token_cost,
    )

    return {
        "safety_result": result.model_dump(),
        "agents_executed": ["safety_checker"],
        "total_tokens": tracker.usage.total_tokens,
        "total_cost": token_cost,
    }
