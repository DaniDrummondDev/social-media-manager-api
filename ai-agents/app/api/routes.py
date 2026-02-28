"""HTTP endpoints for the AI Agents microservice."""

from __future__ import annotations

import asyncio
import json
import time
import uuid
from typing import Any

from fastapi import APIRouter, Request
from fastapi.responses import JSONResponse

from app.api.schemas import (
    ContentCreationRequest,
    ContentDNARequest,
    HealthResponse,
    JobAcceptedResponse,
    JobStatusResponse,
    ReadinessResponse,
    SocialListeningRequest,
    VisualAdaptationRequest,
)
from app.shared.logging import get_logger

router = APIRouter()

VERSION = "0.1.0"

REGISTERED_PIPELINES: list[str] = ["content_creation", "content_dna", "social_listening", "visual_adaptation"]


# ---------------------------------------------------------------------------
# Health / Readiness
# ---------------------------------------------------------------------------


@router.get("/health", response_model=HealthResponse)
async def health() -> HealthResponse:
    """Liveness check — returns 200 if the application is running."""
    return HealthResponse(
        status="healthy",
        service="ai-agents",
        version=VERSION,
        pipelines=REGISTERED_PIPELINES,
    )


@router.get("/ready", response_model=ReadinessResponse)
async def ready(request: Request) -> ReadinessResponse:
    """Readiness check — verifies Redis and PostgreSQL connectivity."""
    redis_status = "ok"
    postgres_status = "ok"

    try:
        redis_client = request.app.state.redis
        await redis_client.ping()
    except Exception:
        redis_status = "error"

    try:
        pg_pool = request.app.state.pg_pool
        async with pg_pool.acquire() as conn:
            await conn.fetchval("SELECT 1")
    except Exception:
        postgres_status = "error"

    is_ready = redis_status == "ok" and postgres_status == "ok"

    return ReadinessResponse(
        ready=is_ready,
        redis=redis_status,
        postgres=postgres_status,
    )


# ---------------------------------------------------------------------------
# Content Creation Pipeline
# ---------------------------------------------------------------------------


@router.post(
    "/api/v1/pipelines/content-creation",
    response_model=JobAcceptedResponse,
    status_code=202,
)
async def create_content(
    body: ContentCreationRequest,
    request: Request,
) -> JobAcceptedResponse:
    """Accept a content-creation pipeline request and process it in the background."""
    job_id = str(uuid.uuid4())
    logger = get_logger(
        pipeline="content_creation",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )
    logger.info("Pipeline job accepted", job_id=job_id)

    # Store initial status in Redis
    redis_client = request.app.state.redis
    await redis_client.set(
        f"job:{job_id}",
        json.dumps({"status": "running", "result": None}),
        ex=3600,
    )

    # Fire-and-forget background task
    asyncio.create_task(
        _run_content_creation(body, job_id, redis_client),
    )

    return JobAcceptedResponse(job_id=job_id)


@router.get("/api/v1/jobs/{job_id}", response_model=JobStatusResponse)
async def job_status(job_id: str, request: Request) -> JSONResponse:
    """Query the current status of a background pipeline job."""
    redis_client = request.app.state.redis
    raw = await redis_client.get(f"job:{job_id}")

    if raw is None:
        return JSONResponse(status_code=404, content={"detail": "Job not found"})

    data = json.loads(raw)
    return JSONResponse(content={
        "job_id": job_id,
        "status": data["status"],
        "result": data.get("result"),
    })


# ---------------------------------------------------------------------------
# Content DNA Pipeline
# ---------------------------------------------------------------------------


@router.post(
    "/api/v1/pipelines/content-dna",
    response_model=JobAcceptedResponse,
    status_code=202,
)
async def analyze_content_dna(
    body: ContentDNARequest,
    request: Request,
) -> JobAcceptedResponse:
    """Accept a content-DNA analysis request and process it in the background."""
    job_id = str(uuid.uuid4())
    logger = get_logger(
        pipeline="content_dna",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )
    logger.info("Pipeline job accepted", job_id=job_id)

    redis_client = request.app.state.redis
    await redis_client.set(
        f"job:{job_id}",
        json.dumps({"status": "running", "result": None}),
        ex=3600,
    )

    asyncio.create_task(
        _run_content_dna(body, job_id, redis_client),
    )

    return JobAcceptedResponse(job_id=job_id)


# ---------------------------------------------------------------------------
# Social Listening Pipeline
# ---------------------------------------------------------------------------


@router.post(
    "/api/v1/pipelines/social-listening",
    response_model=JobAcceptedResponse,
    status_code=202,
)
async def analyze_social_listening(
    body: SocialListeningRequest,
    request: Request,
) -> JobAcceptedResponse:
    """Accept a social-listening pipeline request and process it in the background."""
    job_id = str(uuid.uuid4())
    logger = get_logger(
        pipeline="social_listening",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )
    logger.info("Pipeline job accepted", job_id=job_id)

    redis_client = request.app.state.redis
    await redis_client.set(
        f"job:{job_id}",
        json.dumps({"status": "running", "result": None}),
        ex=3600,
    )

    asyncio.create_task(
        _run_social_listening(body, job_id, redis_client),
    )

    return JobAcceptedResponse(job_id=job_id)


# ---------------------------------------------------------------------------
# Visual Adaptation Pipeline
# ---------------------------------------------------------------------------


@router.post(
    "/api/v1/pipelines/visual-adaptation",
    response_model=JobAcceptedResponse,
    status_code=202,
)
async def adapt_visual(
    body: VisualAdaptationRequest,
    request: Request,
) -> JobAcceptedResponse:
    """Accept a visual-adaptation pipeline request and process it in the background."""
    job_id = str(uuid.uuid4())
    logger = get_logger(
        pipeline="visual_adaptation",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )
    logger.info("Pipeline job accepted", job_id=job_id)

    redis_client = request.app.state.redis
    await redis_client.set(
        f"job:{job_id}",
        json.dumps({"status": "running", "result": None}),
        ex=3600,
    )

    asyncio.create_task(
        _run_visual_adaptation(body, job_id, redis_client),
    )

    return JobAcceptedResponse(job_id=job_id)


# ---------------------------------------------------------------------------
# Background execution
# ---------------------------------------------------------------------------


async def _run_content_creation(
    body: ContentCreationRequest,
    job_id: str,
    redis_client: Any,
) -> None:
    """Execute the content-creation graph and send the callback."""
    from app.agents.content_creation.graph import build_content_creation_graph
    from app.services.callback import send_callback

    logger = get_logger(
        pipeline="content_creation",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )

    start_time = time.monotonic()

    try:
        graph = build_content_creation_graph()
        result = await graph.ainvoke({
            "organization_id": body.organization_id,
            "topic": body.topic,
            "provider": body.provider,
            "tone": body.tone,
            "keywords": body.keywords,
            "language": body.language,
            "style_profile": body.style_profile,
            "rag_examples": body.rag_examples,
            "brief": None,
            "draft": None,
            "review_passed": False,
            "review_feedback": None,
            "retry_count": 0,
            "final_content": None,
            "callback_url": body.callback_url,
            "correlation_id": body.correlation_id,
            "total_tokens": 0,
            "total_cost": 0.0,
            "agents_executed": [],
        })

        duration_ms = int((time.monotonic() - start_time) * 1000)

        # Update Redis with completed status
        await redis_client.set(
            f"job:{job_id}",
            json.dumps({"status": "completed", "result": result["final_content"]}),
            ex=3600,
        )

        metadata = {
            "total_tokens": result.get("total_tokens", 0),
            "total_cost": result.get("total_cost", 0.0),
            "agents_executed": result.get("agents_executed", []),
            "retry_count": result.get("retry_count", 0),
            "duration_ms": duration_ms,
        }

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            pipeline="content_creation",
            status="completed",
            result=result.get("final_content"),
            metadata=metadata,
        )

        logger.info("Pipeline completed", job_id=job_id, duration_ms=duration_ms)

    except Exception:
        duration_ms = int((time.monotonic() - start_time) * 1000)
        logger.exception("Pipeline failed", job_id=job_id, duration_ms=duration_ms)

        await redis_client.set(
            f"job:{job_id}",
            json.dumps({"status": "failed", "result": None}),
            ex=3600,
        )

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            pipeline="content_creation",
            status="failed",
            metadata={"duration_ms": duration_ms},
        )


async def _run_content_dna(
    body: ContentDNARequest,
    job_id: str,
    redis_client: Any,
) -> None:
    """Execute the content-DNA analysis graph and send the callback."""
    from app.agents.content_dna.graph import build_content_dna_graph
    from app.services.callback import send_callback

    logger = get_logger(
        pipeline="content_dna",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )

    start_time = time.monotonic()

    try:
        graph = build_content_dna_graph()
        result = await graph.ainvoke({
            "organization_id": body.organization_id,
            "published_contents": body.published_contents,
            "metrics": body.metrics,
            "current_style_profile": body.current_style_profile,
            "time_window": body.time_window,
            "style_patterns": None,
            "engagement_correlations": None,
            "dna_profile": None,
            "callback_url": body.callback_url,
            "correlation_id": body.correlation_id,
            "total_tokens": 0,
            "total_cost": 0.0,
            "agents_executed": [],
        })

        duration_ms = int((time.monotonic() - start_time) * 1000)

        await redis_client.set(
            f"job:{job_id}",
            json.dumps({"status": "completed", "result": result["dna_profile"]}),
            ex=3600,
        )

        metadata = {
            "total_tokens": result.get("total_tokens", 0),
            "total_cost": result.get("total_cost", 0.0),
            "agents_executed": result.get("agents_executed", []),
            "duration_ms": duration_ms,
        }

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            pipeline="content_dna",
            status="completed",
            result=result.get("dna_profile"),
            metadata=metadata,
        )

        logger.info("Pipeline completed", job_id=job_id, duration_ms=duration_ms)

    except Exception:
        duration_ms = int((time.monotonic() - start_time) * 1000)
        logger.exception("Pipeline failed", job_id=job_id, duration_ms=duration_ms)

        await redis_client.set(
            f"job:{job_id}",
            json.dumps({"status": "failed", "result": None}),
            ex=3600,
        )

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            pipeline="content_dna",
            status="failed",
            metadata={"duration_ms": duration_ms},
        )


async def _run_social_listening(
    body: SocialListeningRequest,
    job_id: str,
    redis_client: Any,
) -> None:
    """Execute the social-listening graph and send the callback."""
    from app.agents.social_listening.graph import build_social_listening_graph
    from app.services.callback import send_callback

    logger = get_logger(
        pipeline="social_listening",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )

    start_time = time.monotonic()

    try:
        graph = build_social_listening_graph()
        result = await graph.ainvoke({
            "organization_id": body.organization_id,
            "mention": body.mention,
            "brand_context": body.brand_context,
            "language": body.language,
            "classification": None,
            "sentiment_analysis": None,
            "suggested_response": None,
            "safety_result": None,
            "callback_url": body.callback_url,
            "correlation_id": body.correlation_id,
            "total_tokens": 0,
            "total_cost": 0.0,
            "agents_executed": [],
        })

        duration_ms = int((time.monotonic() - start_time) * 1000)

        # Build consolidated output for callback
        pipeline_result = {
            "classification": result.get("classification"),
            "sentiment_analysis": result.get("sentiment_analysis"),
            "suggested_response": result.get("suggested_response"),
            "safety_result": result.get("safety_result"),
        }

        await redis_client.set(
            f"job:{job_id}",
            json.dumps({"status": "completed", "result": pipeline_result}),
            ex=3600,
        )

        metadata = {
            "total_tokens": result.get("total_tokens", 0),
            "total_cost": result.get("total_cost", 0.0),
            "agents_executed": result.get("agents_executed", []),
            "duration_ms": duration_ms,
        }

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            pipeline="social_listening",
            status="completed",
            result=pipeline_result,
            metadata=metadata,
        )

        logger.info("Pipeline completed", job_id=job_id, duration_ms=duration_ms)

    except Exception:
        duration_ms = int((time.monotonic() - start_time) * 1000)
        logger.exception("Pipeline failed", job_id=job_id, duration_ms=duration_ms)

        await redis_client.set(
            f"job:{job_id}",
            json.dumps({"status": "failed", "result": None}),
            ex=3600,
        )

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            pipeline="social_listening",
            status="failed",
            metadata={"duration_ms": duration_ms},
        )


async def _run_visual_adaptation(
    body: VisualAdaptationRequest,
    job_id: str,
    redis_client: Any,
) -> None:
    """Execute the visual-adaptation graph and send the callback."""
    from app.agents.visual_adaptation.graph import build_visual_adaptation_graph
    from app.services.callback import send_callback

    logger = get_logger(
        pipeline="visual_adaptation",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )

    start_time = time.monotonic()

    try:
        graph = build_visual_adaptation_graph()
        result = await graph.ainvoke({
            "organization_id": body.organization_id,
            "image_url": body.image_url,
            "target_networks": body.target_networks,
            "brand_guidelines": body.brand_guidelines,
            "semantic_map": None,
            "crop_plans": None,
            "adapted_images": None,
            "quality_results": None,
            "quality_passed": False,
            "quality_feedback": None,
            "retry_count": 0,
            "callback_url": body.callback_url,
            "correlation_id": body.correlation_id,
            "total_tokens": 0,
            "total_cost": 0.0,
            "agents_executed": [],
        })

        duration_ms = int((time.monotonic() - start_time) * 1000)

        # Build result for callback — strip base64 from primary result
        pipeline_result = {
            "adapted_images": {
                k: {key: val for key, val in v.items() if key != "base64"}
                for k, v in (result.get("adapted_images") or {}).items()
            },
            "adapted_images_base64": result.get("adapted_images"),
            "quality_results": result.get("quality_results"),
            "semantic_map": result.get("semantic_map"),
        }

        await redis_client.set(
            f"job:{job_id}",
            json.dumps({"status": "completed", "result": pipeline_result}),
            ex=3600,
        )

        metadata = {
            "total_tokens": result.get("total_tokens", 0),
            "total_cost": result.get("total_cost", 0.0),
            "agents_executed": result.get("agents_executed", []),
            "retry_count": result.get("retry_count", 0),
            "duration_ms": duration_ms,
        }

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            pipeline="visual_adaptation",
            status="completed",
            result=pipeline_result,
            metadata=metadata,
        )

        logger.info("Pipeline completed", job_id=job_id, duration_ms=duration_ms)

    except Exception:
        duration_ms = int((time.monotonic() - start_time) * 1000)
        logger.exception("Pipeline failed", job_id=job_id, duration_ms=duration_ms)

        await redis_client.set(
            f"job:{job_id}",
            json.dumps({"status": "failed", "result": None}),
            ex=3600,
        )

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            pipeline="visual_adaptation",
            status="failed",
            metadata={"duration_ms": duration_ms},
        )
