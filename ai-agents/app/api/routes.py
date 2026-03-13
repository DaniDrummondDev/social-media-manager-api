"""HTTP endpoints for the AI Agents microservice.

Security Controls Applied:
- Authentication via X-Internal-Secret header (verify_internal_secret)
- Rate limiting per organization (RateLimiter)
- SSRF protection for callback URLs (validate_callback_url)
- Job ID namespacing by organization (generate_namespaced_job_id)
- Input sanitization for LLM prompts (sanitize_for_prompt)
"""

from __future__ import annotations

import asyncio
import json
import time
from typing import Annotated, Any

from fastapi import APIRouter, Depends, HTTPException, Request
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
from app.middleware.auth import verify_internal_secret
from app.middleware.rate_limiter import RateLimiter, get_rate_limiter
from app.shared.logging import get_logger
from app.shared.security import (
    SSRFProtectionError,
    generate_namespaced_job_id,
    get_job_redis_key,
    sanitize_dict_for_prompt,
    sanitize_for_prompt,
    validate_callback_url,
    validate_job_ownership,
)

router = APIRouter()

VERSION = "0.2.0"  # Bumped for security hardening

REGISTERED_PIPELINES: list[str] = ["content_creation", "content_dna", "social_listening", "visual_adaptation"]


# ---------------------------------------------------------------------------
# Type Aliases for Dependencies
# ---------------------------------------------------------------------------

AuthDep = Annotated[str, Depends(verify_internal_secret)]
RateLimiterDep = Annotated[RateLimiter, Depends(get_rate_limiter)]


# ---------------------------------------------------------------------------
# Health / Readiness (Public - No Auth Required)
# ---------------------------------------------------------------------------


@router.get("/health", response_model=HealthResponse)
async def health() -> HealthResponse:
    """Liveness check — returns 200 if the application is running.

    This endpoint is intentionally public for container orchestration.
    Sensitive information (pipeline list, version) moved to /health/detailed.
    """
    return HealthResponse(
        status="healthy",
        service="ai-agents",
        version=VERSION,
        pipelines=[],  # Hide pipeline list from public endpoint
    )


@router.get("/health/detailed", response_model=HealthResponse)
async def health_detailed(_auth: AuthDep) -> HealthResponse:
    """Detailed health check — requires authentication."""
    return HealthResponse(
        status="healthy",
        service="ai-agents",
        version=VERSION,
        pipelines=REGISTERED_PIPELINES,
    )


@router.get("/ready", response_model=ReadinessResponse)
async def ready(request: Request) -> ReadinessResponse:
    """Readiness check — verifies Redis and PostgreSQL connectivity.

    This endpoint is intentionally public for container orchestration.
    """
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
    _auth: AuthDep,
    rate_limiter: RateLimiterDep,
) -> JobAcceptedResponse:
    """Accept a content-creation pipeline request and process it in the background.

    Security:
    - Requires X-Internal-Secret authentication
    - Rate limited per organization
    - Callback URL validated against SSRF
    - Inputs sanitized before LLM processing
    """
    logger = get_logger(
        pipeline="content_creation",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )

    # Validate callback URL (SSRF protection)
    try:
        validate_callback_url(body.callback_url)
    except SSRFProtectionError as e:
        logger.warning("SSRF protection blocked callback URL", error=str(e))
        raise HTTPException(status_code=400, detail=str(e))

    # Check rate limits
    await rate_limiter.check_rate_limit(body.organization_id, "content_creation")

    # Generate namespaced job ID
    job_id = generate_namespaced_job_id(body.organization_id)
    logger.info("Pipeline job accepted", job_id=job_id)

    # Store initial status in Redis (namespaced key)
    redis_client = request.app.state.redis
    redis_key = get_job_redis_key(body.organization_id, job_id)
    await redis_client.set(
        redis_key,
        json.dumps({"status": "running", "result": None, "organization_id": body.organization_id}),
        ex=3600,
    )

    # Increment rate limit counters
    await rate_limiter.increment(body.organization_id, "content_creation")

    # Fire-and-forget background task
    asyncio.create_task(
        _run_content_creation(body, job_id, redis_client),
    )

    return JobAcceptedResponse(job_id=job_id)


@router.get("/api/v1/jobs/{job_id}", response_model=JobStatusResponse)
async def job_status(
    job_id: str,
    organization_id: str,
    request: Request,
    _auth: AuthDep,
) -> JSONResponse:
    """Query the current status of a background pipeline job.

    Security:
    - Requires X-Internal-Secret authentication
    - Validates job belongs to requesting organization
    """
    logger = get_logger(organization_id=organization_id)

    # Validate job ownership
    if not validate_job_ownership(job_id, organization_id):
        logger.warning(
            "Job ownership validation failed",
            job_id=job_id,
            organization_id=organization_id,
        )
        return JSONResponse(status_code=404, content={"detail": "Job not found"})

    redis_client = request.app.state.redis
    redis_key = get_job_redis_key(organization_id, job_id)
    raw = await redis_client.get(redis_key)

    if raw is None:
        return JSONResponse(status_code=404, content={"detail": "Job not found"})

    data = json.loads(raw)

    # Double-check organization_id matches (defense in depth)
    if data.get("organization_id") != organization_id:
        logger.warning(
            "Job organization mismatch",
            job_id=job_id,
            expected_org=organization_id,
            actual_org=data.get("organization_id"),
        )
        return JSONResponse(status_code=404, content={"detail": "Job not found"})

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
    _auth: AuthDep,
    rate_limiter: RateLimiterDep,
) -> JobAcceptedResponse:
    """Accept a content-DNA analysis request and process it in the background."""
    logger = get_logger(
        pipeline="content_dna",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )

    # Validate callback URL
    try:
        validate_callback_url(body.callback_url)
    except SSRFProtectionError as e:
        raise HTTPException(status_code=400, detail=str(e))

    # Check rate limits
    await rate_limiter.check_rate_limit(body.organization_id, "content_dna")

    job_id = generate_namespaced_job_id(body.organization_id)
    logger.info("Pipeline job accepted", job_id=job_id)

    redis_client = request.app.state.redis
    redis_key = get_job_redis_key(body.organization_id, job_id)
    await redis_client.set(
        redis_key,
        json.dumps({"status": "running", "result": None, "organization_id": body.organization_id}),
        ex=3600,
    )

    await rate_limiter.increment(body.organization_id, "content_dna")

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
    _auth: AuthDep,
    rate_limiter: RateLimiterDep,
) -> JobAcceptedResponse:
    """Accept a social-listening pipeline request and process it in the background."""
    logger = get_logger(
        pipeline="social_listening",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )

    # Validate callback URL
    try:
        validate_callback_url(body.callback_url)
    except SSRFProtectionError as e:
        raise HTTPException(status_code=400, detail=str(e))

    # Check rate limits
    await rate_limiter.check_rate_limit(body.organization_id, "social_listening")

    job_id = generate_namespaced_job_id(body.organization_id)
    logger.info("Pipeline job accepted", job_id=job_id)

    redis_client = request.app.state.redis
    redis_key = get_job_redis_key(body.organization_id, job_id)
    await redis_client.set(
        redis_key,
        json.dumps({"status": "running", "result": None, "organization_id": body.organization_id}),
        ex=3600,
    )

    await rate_limiter.increment(body.organization_id, "social_listening")

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
    _auth: AuthDep,
    rate_limiter: RateLimiterDep,
) -> JobAcceptedResponse:
    """Accept a visual-adaptation pipeline request and process it in the background."""
    logger = get_logger(
        pipeline="visual_adaptation",
        correlation_id=body.correlation_id,
        organization_id=body.organization_id,
    )

    # Validate callback URL
    try:
        validate_callback_url(body.callback_url)
    except SSRFProtectionError as e:
        raise HTTPException(status_code=400, detail=str(e))

    # Check rate limits
    await rate_limiter.check_rate_limit(body.organization_id, "visual_adaptation")

    job_id = generate_namespaced_job_id(body.organization_id)
    logger.info("Pipeline job accepted", job_id=job_id)

    redis_client = request.app.state.redis
    redis_key = get_job_redis_key(body.organization_id, job_id)
    await redis_client.set(
        redis_key,
        json.dumps({"status": "running", "result": None, "organization_id": body.organization_id}),
        ex=3600,
    )

    await rate_limiter.increment(body.organization_id, "visual_adaptation")

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
    redis_key = get_job_redis_key(body.organization_id, job_id)

    try:
        # Sanitize inputs before passing to LLM
        sanitized_topic = sanitize_for_prompt(body.topic, field_name="topic")
        sanitized_keywords = [
            sanitize_for_prompt(kw, field_name="keyword")
            for kw in body.keywords
        ]
        sanitized_style_profile = (
            sanitize_dict_for_prompt(body.style_profile)
            if body.style_profile
            else None
        )
        sanitized_rag_examples = [
            sanitize_dict_for_prompt(ex)
            for ex in body.rag_examples
        ]

        graph = build_content_creation_graph()
        result = await graph.ainvoke({
            "organization_id": body.organization_id,
            "topic": sanitized_topic,
            "provider": body.provider,
            "tone": body.tone,
            "keywords": sanitized_keywords,
            "language": body.language,
            "style_profile": sanitized_style_profile,
            "rag_examples": sanitized_rag_examples,
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
            redis_key,
            json.dumps({
                "status": "completed",
                "result": result["final_content"],
                "organization_id": body.organization_id,
            }),
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
            organization_id=body.organization_id,
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
            redis_key,
            json.dumps({
                "status": "failed",
                "result": None,
                "organization_id": body.organization_id,
            }),
            ex=3600,
        )

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            organization_id=body.organization_id,
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
    redis_key = get_job_redis_key(body.organization_id, job_id)

    try:
        # Sanitize current style profile if provided
        sanitized_style_profile = (
            sanitize_dict_for_prompt(body.current_style_profile)
            if body.current_style_profile
            else None
        )

        graph = build_content_dna_graph()
        result = await graph.ainvoke({
            "organization_id": body.organization_id,
            "published_contents": body.published_contents,
            "metrics": body.metrics,
            "current_style_profile": sanitized_style_profile,
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
            redis_key,
            json.dumps({
                "status": "completed",
                "result": result["dna_profile"],
                "organization_id": body.organization_id,
            }),
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
            organization_id=body.organization_id,
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
            redis_key,
            json.dumps({
                "status": "failed",
                "result": None,
                "organization_id": body.organization_id,
            }),
            ex=3600,
        )

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            organization_id=body.organization_id,
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
    redis_key = get_job_redis_key(body.organization_id, job_id)

    try:
        # Sanitize mention content (high risk - external user input)
        sanitized_mention = sanitize_dict_for_prompt(body.mention)
        sanitized_brand_context = sanitize_dict_for_prompt(body.brand_context)

        graph = build_social_listening_graph()
        result = await graph.ainvoke({
            "organization_id": body.organization_id,
            "mention": sanitized_mention,
            "brand_context": sanitized_brand_context,
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
            redis_key,
            json.dumps({
                "status": "completed",
                "result": pipeline_result,
                "organization_id": body.organization_id,
            }),
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
            organization_id=body.organization_id,
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
            redis_key,
            json.dumps({
                "status": "failed",
                "result": None,
                "organization_id": body.organization_id,
            }),
            ex=3600,
        )

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            organization_id=body.organization_id,
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
    redis_key = get_job_redis_key(body.organization_id, job_id)

    try:
        # Sanitize brand guidelines if provided
        sanitized_brand_guidelines = (
            sanitize_dict_for_prompt(body.brand_guidelines)
            if body.brand_guidelines
            else None
        )

        graph = build_visual_adaptation_graph()
        result = await graph.ainvoke({
            "organization_id": body.organization_id,
            "image_url": body.image_url,
            "target_networks": body.target_networks,
            "brand_guidelines": sanitized_brand_guidelines,
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
            redis_key,
            json.dumps({
                "status": "completed",
                "result": pipeline_result,
                "organization_id": body.organization_id,
            }),
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
            organization_id=body.organization_id,
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
            redis_key,
            json.dumps({
                "status": "failed",
                "result": None,
                "organization_id": body.organization_id,
            }),
            ex=3600,
        )

        await send_callback(
            callback_url=body.callback_url,
            correlation_id=body.correlation_id,
            job_id=job_id,
            organization_id=body.organization_id,
            pipeline="visual_adaptation",
            status="failed",
            metadata={"duration_ms": duration_ms},
        )
