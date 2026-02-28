"""QualityChecker agent — multimodal quality evaluation with retry logic."""

from __future__ import annotations

from typing import Any

from langchain_core.messages import HumanMessage
from pydantic import BaseModel, Field

from app.agents.visual_adaptation.prompts import QUALITY_CHECKER_SYSTEM_PROMPT
from app.agents.visual_adaptation.state import VisualAdaptationState
from app.services.llm import get_llm
from app.shared.logging import get_logger

MAX_RETRIES = 2


# ---------------------------------------------------------------------------
# Structured output
# ---------------------------------------------------------------------------


class FormatQualityResult(BaseModel):
    """Quality evaluation for a single adapted format."""

    format_key: str
    passed: bool
    score: float = Field(ge=0.0, le=1.0)
    issues: list[str] = Field(default_factory=list)
    recommendation: str = Field(description="approve, retry, or reject")


class QualityCheckOutput(BaseModel):
    """Structured output returned by the QualityChecker LLM call."""

    results: list[FormatQualityResult]
    overall_passed: bool
    overall_feedback: str = Field(
        description="Feedback for crop strategist if retry needed",
    )


# ---------------------------------------------------------------------------
# Node
# ---------------------------------------------------------------------------


async def quality_checker_node(state: VisualAdaptationState) -> dict[str, Any]:
    """Evaluate adapted images for quality using a multimodal LLM."""
    logger = get_logger(
        pipeline="visual_adaptation",
        agent="quality_checker",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )
    logger.info("QualityChecker started", retry_count=state["retry_count"])

    adapted_images = state.get("adapted_images") or {}

    # Build multimodal message with all adapted images
    content_parts: list[dict[str, Any]] = []

    # Text listing each format
    format_list = "\n".join(
        f"- {key}: {info['width']}x{info['height']} ({info['format']})"
        for key, info in adapted_images.items()
    )
    content_parts.append({
        "type": "text",
        "text": f"Evaluate the following adapted images:\n{format_list}",
    })

    # Image data URIs — one per format
    for key, info in adapted_images.items():
        content_parts.append({
            "type": "image_url",
            "image_url": {"url": f"data:image/jpeg;base64,{info['base64']}"},
        })

    message = HumanMessage(content=content_parts)

    llm = get_llm(temperature=0.1).with_structured_output(QualityCheckOutput)
    output: QualityCheckOutput = await llm.ainvoke([
        ("system", QUALITY_CHECKER_SYSTEM_PROMPT),
        message,
    ])

    # Retry logic — same pattern as content_creation reviewer
    new_retry_count = state["retry_count"] + (0 if output.overall_passed else 1)
    forced = not output.overall_passed and new_retry_count >= MAX_RETRIES
    quality_passed = output.overall_passed or new_retry_count >= MAX_RETRIES

    logger.info(
        "QualityChecker finished",
        overall_passed=output.overall_passed,
        quality_passed=quality_passed,
        forced=forced,
        retry_count=new_retry_count,
    )

    return {
        "quality_results": output.model_dump(),
        "quality_passed": quality_passed,
        "quality_feedback": output.overall_feedback,
        "retry_count": new_retry_count,
        "agents_executed": ["quality_checker"],
        "total_tokens": 0,
        "total_cost": 0.0,
    }


# ---------------------------------------------------------------------------
# Router
# ---------------------------------------------------------------------------


def quality_router(state: VisualAdaptationState) -> str:
    """Route to crop_strategist (retry) or end based on quality outcome."""
    if state["quality_passed"]:
        return "end"
    return "crop_strategist"
