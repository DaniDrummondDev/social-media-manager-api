"""VisionAnalyzer agent — multimodal semantic analysis of images."""

from __future__ import annotations

from typing import Any

from langchain_core.messages import HumanMessage
from pydantic import BaseModel, Field

from app.agents.visual_adaptation.prompts import VISION_ANALYZER_SYSTEM_PROMPT
from app.agents.visual_adaptation.state import VisualAdaptationState
from app.services.llm import get_llm
from app.shared.logging import get_logger


class SemanticMap(BaseModel):
    """Structured output returned by the VisionAnalyzer LLM call."""

    subject_description: str = Field(description="Description of the main subject")
    subject_position: str = Field(
        description="Position: center, left_third, right_third, top_half, bottom_half",
    )
    subject_bounding_box: dict[str, float] = Field(
        description="Bounding box {x, y, width, height} as 0-1 percentages",
    )
    text_regions: list[dict[str, Any]] = Field(
        default_factory=list,
        description="Text regions [{text, x, y, width, height}]",
    )
    brand_elements: list[dict[str, Any]] = Field(
        default_factory=list,
        description="Brand elements [{type, description, x, y, width, height}]",
    )
    dominant_colors: list[str] = Field(description="Dominant colors in hex")
    composition_type: str = Field(
        description="Composition: rule_of_thirds, centered, symmetrical, asymmetrical",
    )
    complexity: str = Field(description="Complexity: simple, moderate, complex")


async def vision_analyzer_node(state: VisualAdaptationState) -> dict[str, Any]:
    """Analyze an image semantically using a multimodal LLM."""
    logger = get_logger(
        pipeline="visual_adaptation",
        agent="vision_analyzer",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )
    logger.info("VisionAnalyzer started")

    message = HumanMessage(content=[
        {"type": "text", "text": "Analyze this image semantically for social media adaptation."},
        {"type": "image_url", "image_url": {"url": state["image_url"]}},
    ])

    llm = get_llm(temperature=0.2).with_structured_output(SemanticMap)
    result: SemanticMap = await llm.ainvoke([
        ("system", VISION_ANALYZER_SYSTEM_PROMPT),
        message,
    ])

    logger.info(
        "VisionAnalyzer finished",
        subject_position=result.subject_position,
        composition_type=result.composition_type,
    )

    return {
        "semantic_map": result.model_dump(),
        "agents_executed": ["vision_analyzer"],
        "total_tokens": 0,
        "total_cost": 0.0,
    }
