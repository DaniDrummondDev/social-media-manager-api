"""Tests for Visual Adaptation individual agents - edge cases."""

from __future__ import annotations

import base64
import io
from unittest.mock import AsyncMock, MagicMock, patch

import pytest
from PIL import Image

from app.agents.visual_adaptation.crop_strategist import CropPlan, CropStrategyOutput
from app.agents.visual_adaptation.quality_checker import FormatQualityResult, QualityCheckOutput
from app.agents.visual_adaptation.state import VisualAdaptationState
from app.agents.visual_adaptation.vision_analyzer import SemanticMap


# ---------------------------------------------------------------------------
# Fixtures and helpers
# ---------------------------------------------------------------------------


def _base_state() -> VisualAdaptationState:
    """Minimal valid state for Visual Adaptation graph."""
    return {
        "organization_id": "org-va-123",
        "image_url": "https://example.com/photo.jpg",
        "target_networks": ["instagram", "youtube"],
        "brand_guidelines": {"primary_color": "#FF5733"},
        "semantic_map": None,
        "crop_plans": None,
        "adapted_images": None,
        "quality_results": None,
        "quality_passed": False,
        "quality_feedback": None,
        "retry_count": 0,
        "callback_url": "http://localhost/callback",
        "correlation_id": "corr-va-001",
        "total_tokens": 0,
        "total_cost": 0.0,
        "agents_executed": [],
    }


def _sample_semantic_map() -> SemanticMap:
    return SemanticMap(
        subject_description="Person holding coffee",
        subject_position="center",
        subject_bounding_box={"x": 0.2, "y": 0.1, "width": 0.6, "height": 0.8},
        text_regions=[],
        brand_elements=[],
        dominant_colors=["#8B4513", "#FFFFFF"],
        composition_type="centered",
        complexity="simple",
    )


def _sample_crop_plans() -> CropStrategyOutput:
    return CropStrategyOutput(
        plans=[
            CropPlan(
                format_key="instagram_feed_1_1",
                aspect_ratio="1:1",
                target_width=1080,
                target_height=1080,
                crop_x=0.1,
                crop_y=0.0,
                crop_width=0.8,
                crop_height=0.8,
                preserve_notes="Keep subject centered",
                strategy_notes="Square crop",
            ),
        ],
        overall_strategy="Center-focused crops",
    )


def _sample_quality_pass() -> QualityCheckOutput:
    return QualityCheckOutput(
        results=[
            FormatQualityResult(
                format_key="instagram_feed_1_1",
                passed=True,
                score=0.9,
                issues=[],
                recommendation="approve",
            ),
        ],
        overall_passed=True,
        overall_feedback="All formats look great.",
    )


def _create_test_image_bytes(width=100, height=100) -> bytes:
    """Generate a small test image."""
    img = Image.new("RGB", (width, height), color=(100, 150, 200))
    buf = io.BytesIO()
    img.save(buf, format="JPEG")
    return buf.getvalue()


def _mock_llm_for_node(structured_return=None):
    """Create a mock LLM with structured output support."""
    mock = MagicMock()
    if structured_return is not None:
        structured_mock = AsyncMock()
        structured_mock.ainvoke = AsyncMock(return_value=structured_return)
        mock.with_structured_output = MagicMock(return_value=structured_mock)
    return mock


def _mock_httpx_for_image(image_bytes: bytes):
    """Create a mock httpx.AsyncClient for image download."""
    mock_response = MagicMock()
    mock_response.content = image_bytes
    mock_response.raise_for_status = MagicMock()

    mock_client = AsyncMock()
    mock_client.get = AsyncMock(return_value=mock_response)
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=None)

    return MagicMock(return_value=mock_client)


# ---------------------------------------------------------------------------
# Vision Analyzer tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.vision_analyzer.get_llm")
async def test_vision_analyzer_produces_semantic_map(mock_llm):
    """Vision analyzer produces a valid semantic map."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_semantic_map())

    from app.agents.visual_adaptation.vision_analyzer import vision_analyzer_node

    result = await vision_analyzer_node(_base_state())

    assert result["semantic_map"]["subject_position"] == "center"
    assert result["semantic_map"]["composition_type"] == "centered"
    assert "vision_analyzer" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Crop Strategist tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.crop_strategist.get_llm")
async def test_crop_strategist_with_retry_feedback(mock_llm):
    """Crop strategist incorporates feedback when retrying."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_crop_plans())

    from app.agents.visual_adaptation.crop_strategist import crop_strategist_node

    state = _base_state()
    state["semantic_map"] = _sample_semantic_map().model_dump()
    state["retry_count"] = 1
    state["quality_feedback"] = "Instagram crop cuts off subject."

    result = await crop_strategist_node(state)

    assert len(result["crop_plans"]) == 1
    assert "crop_strategist" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Network Adapters tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.network_adapters.httpx.AsyncClient")
async def test_network_adapters_processes_images(mock_httpx):
    """Network adapters crops and resizes images correctly."""
    image_bytes = _create_test_image_bytes(width=400, height=600)
    mock_httpx.return_value = _mock_httpx_for_image(image_bytes).return_value

    from app.agents.visual_adaptation.network_adapters import network_adapters_node

    state = _base_state()
    state["crop_plans"] = [plan.model_dump() for plan in _sample_crop_plans().plans]

    result = await network_adapters_node(state)

    assert "instagram_feed_1_1" in result["adapted_images"]
    assert result["adapted_images"]["instagram_feed_1_1"]["format"] == "jpeg"
    assert "network_adapters" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Quality Checker tests
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.quality_checker.get_llm")
async def test_quality_checker_force_forward_on_max_retries(mock_llm):
    """Quality checker forces forward after max retries."""
    # Return failing quality check
    fail_result = QualityCheckOutput(
        results=[
            FormatQualityResult(
                format_key="instagram_feed_1_1",
                passed=False,
                score=0.4,
                issues=["Subject cut off"],
                recommendation="retry",
            ),
        ],
        overall_passed=False,
        overall_feedback="Needs adjustment.",
    )
    mock_llm.return_value = _mock_llm_for_node(structured_return=fail_result)

    from app.agents.visual_adaptation.quality_checker import quality_checker_node

    state = _base_state()
    state["retry_count"] = 2  # At max retries
    img_bytes = _create_test_image_bytes()
    b64 = base64.b64encode(img_bytes).decode()
    state["adapted_images"] = {
        "instagram_feed_1_1": {"base64": b64, "width": 1080, "height": 1080, "format": "jpeg"},
    }

    result = await quality_checker_node(state)

    # Should force forward despite failure
    assert result["quality_passed"] is True
    assert "quality_checker" in result["agents_executed"]
