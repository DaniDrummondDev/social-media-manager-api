"""NetworkAdapters node — pure Pillow image processing (no LLM)."""

from __future__ import annotations

import base64
import io
from typing import Any

import httpx
from PIL import Image

from app.agents.visual_adaptation.state import VisualAdaptationState
from app.shared.logging import get_logger


async def _download_image(url: str) -> Image.Image:
    """Download an image from a URL and return a PIL Image."""
    async with httpx.AsyncClient(timeout=30.0) as client:
        response = await client.get(url)
        response.raise_for_status()
    return Image.open(io.BytesIO(response.content))


async def network_adapters_node(state: VisualAdaptationState) -> dict[str, Any]:
    """Crop and resize the original image for each target format using Pillow."""
    logger = get_logger(
        pipeline="visual_adaptation",
        agent="network_adapters",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )
    logger.info("NetworkAdapters started", num_plans=len(state["crop_plans"] or []))

    img = await _download_image(state["image_url"])
    img_width, img_height = img.size

    adapted: dict[str, Any] = {}
    for plan in state["crop_plans"] or []:
        left = int(plan["crop_x"] * img_width)
        upper = int(plan["crop_y"] * img_height)
        right = int((plan["crop_x"] + plan["crop_width"]) * img_width)
        lower = int((plan["crop_y"] + plan["crop_height"]) * img_height)

        # Clamp to image bounds
        right = min(right, img_width)
        lower = min(lower, img_height)

        cropped = img.crop((left, upper, right, lower))
        resized = cropped.resize(
            (plan["target_width"], plan["target_height"]),
            Image.LANCZOS,
        )

        buf = io.BytesIO()
        resized.save(buf, format="JPEG", quality=90)
        b64 = base64.b64encode(buf.getvalue()).decode()

        adapted[plan["format_key"]] = {
            "base64": b64,
            "width": plan["target_width"],
            "height": plan["target_height"],
            "format": "jpeg",
        }

    logger.info("NetworkAdapters finished", formats_processed=len(adapted))

    return {
        "adapted_images": adapted,
        "agents_executed": ["network_adapters"],
        "total_tokens": 0,
        "total_cost": 0.0,
    }
