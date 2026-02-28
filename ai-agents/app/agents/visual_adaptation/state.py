"""Visual Adaptation pipeline state definition."""

from __future__ import annotations

import operator
from typing import Annotated, Any, TypedDict


class VisualAdaptationState(TypedDict):
    """State for the Visual Adaptation Cross-Network pipeline.

    Nodes receive the full state and return a partial dict with only the keys
    they update.  The ``agents_executed`` field uses an append reducer so each
    node can simply return ``["vision_analyzer"]`` and it will be accumulated.
    """

    # -- Input (provided by the caller) ------------------------------------
    organization_id: str
    image_url: str
    target_networks: list[str]
    brand_guidelines: dict[str, Any] | None

    # -- Intermediate ------------------------------------------------------
    semantic_map: dict[str, Any] | None
    crop_plans: list[dict[str, Any]] | None
    adapted_images: dict[str, Any] | None

    # -- Quality check + retry ---------------------------------------------
    quality_results: dict[str, Any] | None
    quality_passed: bool
    quality_feedback: str | None
    retry_count: int

    # -- Metadata ----------------------------------------------------------
    callback_url: str
    correlation_id: str
    total_tokens: int
    total_cost: float
    agents_executed: Annotated[list[str], operator.add]
