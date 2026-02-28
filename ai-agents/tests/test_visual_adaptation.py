"""Tests for the Visual Adaptation Cross-Network pipeline."""

from __future__ import annotations

import base64
import io
import json
from unittest.mock import AsyncMock, MagicMock, patch

import pytest
from fastapi import FastAPI
from fastapi.testclient import TestClient
from PIL import Image

from app.agents.visual_adaptation.crop_strategist import CropPlan, CropStrategyOutput
from app.agents.visual_adaptation.graph import build_visual_adaptation_graph
from app.agents.visual_adaptation.quality_checker import (
    FormatQualityResult,
    QualityCheckOutput,
)
from app.agents.visual_adaptation.state import VisualAdaptationState
from app.agents.visual_adaptation.vision_analyzer import SemanticMap
from app.api.routes import router


# ---------------------------------------------------------------------------
# Sample data factories
# ---------------------------------------------------------------------------


def _sample_semantic_map() -> SemanticMap:
    return SemanticMap(
        subject_description="Woman holding coffee cup",
        subject_position="center",
        subject_bounding_box={"x": 0.25, "y": 0.1, "width": 0.5, "height": 0.8},
        text_regions=[],
        brand_elements=[],
        dominant_colors=["#8B4513", "#FFFFFF", "#F5F5DC"],
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
            CropPlan(
                format_key="youtube_thumbnail_16_9",
                aspect_ratio="16:9",
                target_width=1280,
                target_height=720,
                crop_x=0.0,
                crop_y=0.15,
                crop_width=1.0,
                crop_height=0.7,
                preserve_notes="Full width",
                strategy_notes="Landscape crop",
            ),
        ],
        overall_strategy="Center-focused crops preserving subject",
    )


def _sample_quality_pass() -> QualityCheckOutput:
    return QualityCheckOutput(
        results=[
            FormatQualityResult(
                format_key="instagram_feed_1_1",
                passed=True,
                score=0.92,
                issues=[],
                recommendation="approve",
            ),
            FormatQualityResult(
                format_key="youtube_thumbnail_16_9",
                passed=True,
                score=0.88,
                issues=[],
                recommendation="approve",
            ),
        ],
        overall_passed=True,
        overall_feedback="All formats look great.",
    )


def _sample_quality_fail() -> QualityCheckOutput:
    return QualityCheckOutput(
        results=[
            FormatQualityResult(
                format_key="instagram_feed_1_1",
                passed=False,
                score=0.4,
                issues=["Subject partially cut off"],
                recommendation="retry",
            ),
            FormatQualityResult(
                format_key="youtube_thumbnail_16_9",
                passed=True,
                score=0.88,
                issues=[],
                recommendation="approve",
            ),
        ],
        overall_passed=False,
        overall_feedback="Instagram feed crop cuts off subject's face. Adjust crop_y to include more headroom.",
    )


def _base_state() -> VisualAdaptationState:
    """Minimal valid state to feed into the graph."""
    return {
        "organization_id": "org-456",
        "image_url": "https://example.com/photo.jpg",
        "target_networks": ["instagram", "youtube"],
        "brand_guidelines": {"primary_color": "#FF5733", "logo_position": "top_left"},
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


def _mock_llm_for_node(structured_return=None, content_return=None):
    """Create a mock LLM that supports both .ainvoke and .with_structured_output."""
    mock = MagicMock()

    if structured_return is not None:
        structured_mock = AsyncMock()
        structured_mock.ainvoke = AsyncMock(return_value=structured_return)
        mock.with_structured_output = MagicMock(return_value=structured_mock)

    if content_return is not None:
        response = MagicMock()
        response.content = content_return
        mock.ainvoke = AsyncMock(return_value=response)

    return mock


def _create_test_image_bytes(width: int = 400, height: int = 600) -> bytes:
    """Generate a small JPEG image for testing."""
    img = Image.new("RGB", (width, height), color=(100, 150, 200))
    buf = io.BytesIO()
    img.save(buf, format="JPEG")
    return buf.getvalue()


def _mock_httpx_for_image(image_bytes: bytes):
    """Create a mock httpx.AsyncClient that returns image bytes on GET."""
    mock_response = MagicMock()
    mock_response.content = image_bytes
    mock_response.raise_for_status = MagicMock()

    mock_client = AsyncMock()
    mock_client.get = AsyncMock(return_value=mock_response)
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=None)

    mock_constructor = MagicMock(return_value=mock_client)
    return mock_constructor


# ---------------------------------------------------------------------------
# Fixtures
# ---------------------------------------------------------------------------


@pytest.fixture
def test_app() -> FastAPI:
    """Minimal FastAPI app with mocked Redis for pipeline tests."""
    application = FastAPI()
    application.include_router(router)

    mock_redis = AsyncMock()
    mock_redis.ping = AsyncMock(return_value=True)
    mock_redis.set = AsyncMock(return_value=True)
    mock_redis.get = AsyncMock(return_value=json.dumps({
        "status": "running",
        "result": None,
    }))
    application.state.redis = mock_redis

    mock_conn = AsyncMock()
    mock_conn.fetchval = AsyncMock(return_value=1)
    mock_conn.__aenter__ = AsyncMock(return_value=mock_conn)
    mock_conn.__aexit__ = AsyncMock(return_value=None)

    mock_pool = MagicMock()
    mock_pool.acquire = MagicMock(return_value=mock_conn)
    application.state.pg_pool = mock_pool

    return application


@pytest.fixture
def client(test_app: FastAPI) -> TestClient:
    return TestClient(test_app)


# ---------------------------------------------------------------------------
# Graph: Full flow (happy path) — quality passes first time
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.network_adapters.httpx.AsyncClient")
@patch("app.agents.visual_adaptation.vision_analyzer.get_llm")
@patch("app.agents.visual_adaptation.crop_strategist.get_llm")
@patch("app.agents.visual_adaptation.quality_checker.get_llm")
async def test_graph_full_flow_happy_path(
    mock_qc_llm,
    mock_cs_llm,
    mock_va_llm,
    mock_httpx,
):
    """VisionAnalyzer -> CropStrategist -> NetworkAdapters -> QualityChecker(pass) -> END."""
    mock_va_llm.return_value = _mock_llm_for_node(structured_return=_sample_semantic_map())
    mock_cs_llm.return_value = _mock_llm_for_node(structured_return=_sample_crop_plans())
    mock_qc_llm.return_value = _mock_llm_for_node(structured_return=_sample_quality_pass())

    image_bytes = _create_test_image_bytes()
    mock_httpx.return_value = _mock_httpx_for_image(image_bytes).return_value

    graph = build_visual_adaptation_graph()
    result = await graph.ainvoke(_base_state())

    assert result["adapted_images"] is not None
    assert result["quality_passed"] is True
    assert result["retry_count"] == 0
    assert "vision_analyzer" in result["agents_executed"]
    assert "crop_strategist" in result["agents_executed"]
    assert "network_adapters" in result["agents_executed"]
    assert "quality_checker" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Graph: Retry once then pass
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.network_adapters.httpx.AsyncClient")
@patch("app.agents.visual_adaptation.vision_analyzer.get_llm")
@patch("app.agents.visual_adaptation.crop_strategist.get_llm")
@patch("app.agents.visual_adaptation.quality_checker.get_llm")
async def test_graph_retry_once_then_pass(
    mock_qc_llm,
    mock_cs_llm,
    mock_va_llm,
    mock_httpx,
):
    """Quality fails first, passes second. CropStrategist and NetworkAdapters run twice."""
    mock_va_llm.return_value = _mock_llm_for_node(structured_return=_sample_semantic_map())
    mock_cs_llm.return_value = _mock_llm_for_node(structured_return=_sample_crop_plans())

    # First call: fail. Second call: pass.
    qc_mock_fail = _mock_llm_for_node(structured_return=_sample_quality_fail())
    qc_mock_pass = _mock_llm_for_node(structured_return=_sample_quality_pass())
    mock_qc_llm.side_effect = [qc_mock_fail, qc_mock_pass]

    image_bytes = _create_test_image_bytes()
    mock_httpx.return_value = _mock_httpx_for_image(image_bytes).return_value

    graph = build_visual_adaptation_graph()
    result = await graph.ainvoke(_base_state())

    assert result["quality_passed"] is True
    assert result["retry_count"] == 1
    # CropStrategist should appear twice (initial + retry)
    assert result["agents_executed"].count("crop_strategist") == 2
    # NetworkAdapters should appear twice
    assert result["agents_executed"].count("network_adapters") == 2


# ---------------------------------------------------------------------------
# Graph: Max retries exhausted — force forward
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.network_adapters.httpx.AsyncClient")
@patch("app.agents.visual_adaptation.vision_analyzer.get_llm")
@patch("app.agents.visual_adaptation.crop_strategist.get_llm")
@patch("app.agents.visual_adaptation.quality_checker.get_llm")
async def test_graph_max_retries_force_forward(
    mock_qc_llm,
    mock_cs_llm,
    mock_va_llm,
    mock_httpx,
):
    """After 2 rejections, quality_checker forces forward to END."""
    mock_va_llm.return_value = _mock_llm_for_node(structured_return=_sample_semantic_map())
    mock_cs_llm.return_value = _mock_llm_for_node(structured_return=_sample_crop_plans())

    # Always fail
    mock_qc_llm.return_value = _mock_llm_for_node(structured_return=_sample_quality_fail())

    image_bytes = _create_test_image_bytes()
    mock_httpx.return_value = _mock_httpx_for_image(image_bytes).return_value

    graph = build_visual_adaptation_graph()
    result = await graph.ainvoke(_base_state())

    assert result["retry_count"] == 2
    # quality_passed forced to True after max retries
    assert result["quality_passed"] is True


# ---------------------------------------------------------------------------
# Individual agents
# ---------------------------------------------------------------------------


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.vision_analyzer.get_llm")
async def test_vision_analyzer_produces_semantic_map(mock_llm):
    """VisionAnalyzer node returns a valid semantic map dict."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_semantic_map())

    from app.agents.visual_adaptation.vision_analyzer import vision_analyzer_node

    result = await vision_analyzer_node(_base_state())

    assert result["semantic_map"]["subject_position"] == "center"
    assert result["semantic_map"]["composition_type"] == "centered"
    assert result["semantic_map"]["complexity"] == "simple"
    assert "vision_analyzer" in result["agents_executed"]


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.crop_strategist.get_llm")
async def test_crop_strategist_produces_plans(mock_llm):
    """CropStrategist node returns crop plans for target formats."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_crop_plans())

    from app.agents.visual_adaptation.crop_strategist import crop_strategist_node

    state = _base_state()
    state["semantic_map"] = _sample_semantic_map().model_dump()

    result = await crop_strategist_node(state)

    assert len(result["crop_plans"]) == 2
    assert result["crop_plans"][0]["format_key"] == "instagram_feed_1_1"
    assert result["crop_plans"][1]["format_key"] == "youtube_thumbnail_16_9"
    assert "crop_strategist" in result["agents_executed"]


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.crop_strategist.get_llm")
async def test_crop_strategist_includes_feedback_on_retry(mock_llm):
    """CropStrategist incorporates quality feedback when retrying."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_crop_plans())

    from app.agents.visual_adaptation.crop_strategist import crop_strategist_node

    state = _base_state()
    state["semantic_map"] = _sample_semantic_map().model_dump()
    state["retry_count"] = 1
    state["quality_feedback"] = "Instagram feed crop cuts off subject's face."

    result = await crop_strategist_node(state)

    assert "crop_strategist" in result["agents_executed"]
    # Verify the LLM was called with feedback in the system prompt
    call_args = mock_llm.return_value.with_structured_output.return_value.ainvoke.call_args
    system_msg = call_args[0][0][0][1]  # first message tuple, content
    assert "cuts off subject" in system_msg


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.network_adapters.httpx.AsyncClient")
async def test_network_adapters_processes_images(mock_httpx):
    """NetworkAdapters crops and resizes images using Pillow."""
    image_bytes = _create_test_image_bytes(width=400, height=600)
    mock_httpx.return_value = _mock_httpx_for_image(image_bytes).return_value

    from app.agents.visual_adaptation.network_adapters import network_adapters_node

    state = _base_state()
    state["crop_plans"] = [plan.model_dump() for plan in _sample_crop_plans().plans]

    result = await network_adapters_node(state)

    assert "instagram_feed_1_1" in result["adapted_images"]
    assert "youtube_thumbnail_16_9" in result["adapted_images"]

    # Verify dimensions match target
    ig_info = result["adapted_images"]["instagram_feed_1_1"]
    assert ig_info["width"] == 1080
    assert ig_info["height"] == 1080
    assert ig_info["format"] == "jpeg"

    yt_info = result["adapted_images"]["youtube_thumbnail_16_9"]
    assert yt_info["width"] == 1280
    assert yt_info["height"] == 720

    # Decode base64 and verify with Pillow
    ig_bytes = base64.b64decode(ig_info["base64"])
    ig_image = Image.open(io.BytesIO(ig_bytes))
    assert ig_image.size == (1080, 1080)

    assert "network_adapters" in result["agents_executed"]


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.quality_checker.get_llm")
async def test_quality_checker_approves(mock_llm):
    """QualityChecker approves adapted images."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_quality_pass())

    from app.agents.visual_adaptation.quality_checker import quality_checker_node

    state = _base_state()
    # Provide minimal adapted_images with base64
    img_bytes = _create_test_image_bytes(width=100, height=100)
    b64 = base64.b64encode(img_bytes).decode()
    state["adapted_images"] = {
        "instagram_feed_1_1": {"base64": b64, "width": 1080, "height": 1080, "format": "jpeg"},
    }

    result = await quality_checker_node(state)

    assert result["quality_passed"] is True
    assert result["quality_results"]["overall_passed"] is True
    assert result["retry_count"] == 0
    assert "quality_checker" in result["agents_executed"]


@pytest.mark.asyncio
@patch("app.agents.visual_adaptation.quality_checker.get_llm")
async def test_quality_checker_rejects(mock_llm):
    """QualityChecker rejects adapted images and provides feedback."""
    mock_llm.return_value = _mock_llm_for_node(structured_return=_sample_quality_fail())

    from app.agents.visual_adaptation.quality_checker import quality_checker_node

    state = _base_state()
    img_bytes = _create_test_image_bytes(width=100, height=100)
    b64 = base64.b64encode(img_bytes).decode()
    state["adapted_images"] = {
        "instagram_feed_1_1": {"base64": b64, "width": 1080, "height": 1080, "format": "jpeg"},
    }

    result = await quality_checker_node(state)

    assert result["quality_passed"] is False
    assert result["quality_feedback"] is not None
    assert "crop" in result["quality_feedback"].lower() or "subject" in result["quality_feedback"].lower()
    assert result["retry_count"] == 1
    assert "quality_checker" in result["agents_executed"]


# ---------------------------------------------------------------------------
# Endpoint tests
# ---------------------------------------------------------------------------


def test_visual_adaptation_endpoint_returns_202(client: TestClient) -> None:
    """POST /api/v1/pipelines/visual-adaptation returns 202 with job_id."""
    response = client.post(
        "/api/v1/pipelines/visual-adaptation",
        json={
            "organization_id": "org-456",
            "correlation_id": "corr-va-001",
            "callback_url": "http://localhost/callback",
            "image_url": "https://example.com/photo.jpg",
            "target_networks": ["instagram", "youtube"],
        },
    )

    assert response.status_code == 202
    data = response.json()
    assert "job_id" in data
    assert len(data["job_id"]) == 36  # UUID format


def test_visual_adaptation_validates_input(client: TestClient) -> None:
    """POST /api/v1/pipelines/visual-adaptation rejects missing required fields."""
    response = client.post(
        "/api/v1/pipelines/visual-adaptation",
        json={"organization_id": "org-456"},
    )

    assert response.status_code == 422


def test_health_shows_four_pipelines(client: TestClient) -> None:
    """GET /health lists all 4 pipelines including visual_adaptation."""
    response = client.get("/health")
    data = response.json()

    assert "visual_adaptation" in data["pipelines"]
    assert "content_creation" in data["pipelines"]
    assert "content_dna" in data["pipelines"]
    assert "social_listening" in data["pipelines"]
    assert len(data["pipelines"]) == 4
