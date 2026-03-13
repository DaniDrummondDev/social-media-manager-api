"""CropStrategist agent — determines optimal crop coordinates per network format."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

from app.agents.visual_adaptation.prompts import (
    CROP_NO_FEEDBACK_BLOCK,
    CROP_RETRY_FEEDBACK_BLOCK,
    CROP_STRATEGIST_SYSTEM_PROMPT,
)
from app.agents.visual_adaptation.state import VisualAdaptationState
from app.services.llm import get_llm
from app.shared.logging import get_logger
from app.shared.token_tracker import TokenTrackingCallback, estimate_cost

# ---------------------------------------------------------------------------
# Network format specifications
# ---------------------------------------------------------------------------

NETWORK_SPECS: dict[str, list[dict[str, Any]]] = {
    "instagram": [
        {"format": "instagram_feed_1_1", "width": 1080, "height": 1080, "aspect": "1:1"},
        {"format": "instagram_feed_4_5", "width": 1080, "height": 1350, "aspect": "4:5"},
        {"format": "instagram_stories_9_16", "width": 1080, "height": 1920, "aspect": "9:16"},
    ],
    "tiktok": [
        {"format": "tiktok_9_16", "width": 1080, "height": 1920, "aspect": "9:16"},
    ],
    "youtube": [
        {"format": "youtube_thumbnail_16_9", "width": 1280, "height": 720, "aspect": "16:9"},
    ],
}


# ---------------------------------------------------------------------------
# Structured output
# ---------------------------------------------------------------------------


class CropPlan(BaseModel):
    """Crop specification for a single target format."""

    format_key: str = Field(
        description="Format key: instagram_feed_1_1, tiktok_9_16, etc.",
    )
    aspect_ratio: str = Field(description="Aspect ratio: 1:1, 4:5, 9:16, 16:9")
    target_width: int
    target_height: int
    crop_x: float = Field(ge=0.0, le=1.0, description="Crop start X as 0-1 percentage")
    crop_y: float = Field(ge=0.0, le=1.0, description="Crop start Y as 0-1 percentage")
    crop_width: float = Field(gt=0.0, le=1.0, description="Crop width as 0-1 percentage")
    crop_height: float = Field(gt=0.0, le=1.0, description="Crop height as 0-1 percentage")
    preserve_notes: str
    strategy_notes: str


class CropStrategyOutput(BaseModel):
    """Structured output returned by the CropStrategist LLM call."""

    plans: list[CropPlan]
    overall_strategy: str


# ---------------------------------------------------------------------------
# Node
# ---------------------------------------------------------------------------


def _expand_target_formats(target_networks: list[str]) -> list[dict[str, Any]]:
    """Expand network names into their individual format specs."""
    formats: list[dict[str, Any]] = []
    for network in target_networks:
        specs = NETWORK_SPECS.get(network, [])
        formats.extend(specs)
    return formats


async def crop_strategist_node(state: VisualAdaptationState) -> dict[str, Any]:
    """Determine optimal crop coordinates for each target format."""
    logger = get_logger(
        pipeline="visual_adaptation",
        agent="crop_strategist",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )
    logger.info("CropStrategist started", retry_count=state["retry_count"])

    # Build feedback block for retries
    if state["retry_count"] > 0 and state.get("quality_feedback"):
        feedback_block = CROP_RETRY_FEEDBACK_BLOCK.format(
            quality_feedback=state["quality_feedback"],
        )
    else:
        feedback_block = CROP_NO_FEEDBACK_BLOCK

    system_prompt = CROP_STRATEGIST_SYSTEM_PROMPT.format(
        retry_feedback_block=feedback_block,
    )

    # Expand target networks into individual formats
    target_formats = _expand_target_formats(state["target_networks"])

    # Build human message
    parts: list[str] = [
        f"Semantic map:\n{state['semantic_map']}",
        f"Target formats:\n{target_formats}",
    ]
    if state.get("brand_guidelines"):
        parts.append(f"Brand guidelines:\n{state['brand_guidelines']}")

    human_message = "\n\n".join(parts)

    tracker = TokenTrackingCallback()
    llm = get_llm(temperature=0.3, callbacks=[tracker]).with_structured_output(CropStrategyOutput)
    output: CropStrategyOutput = await llm.ainvoke([
        ("system", system_prompt),
        ("human", human_message),
    ])

    token_cost = estimate_cost(tracker.usage)

    logger.info(
        "CropStrategist finished",
        num_plans=len(output.plans),
        overall_strategy=output.overall_strategy,
        tokens=tracker.usage.total_tokens,
        cost_usd=token_cost,
    )

    return {
        "crop_plans": [plan.model_dump() for plan in output.plans],
        "agents_executed": ["crop_strategist"],
        "total_tokens": tracker.usage.total_tokens,
        "total_cost": token_cost,
    }
