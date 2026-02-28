"""Pydantic request/response schemas for the AI Agents API."""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field


# ---------------------------------------------------------------------------
# Health / Readiness
# ---------------------------------------------------------------------------


class HealthResponse(BaseModel):
    """Liveness check response."""

    status: str
    service: str
    version: str
    pipelines: list[str]


class ReadinessResponse(BaseModel):
    """Readiness check response — verifies external dependencies."""

    ready: bool
    redis: str
    postgres: str


# ---------------------------------------------------------------------------
# Content Creation Pipeline
# ---------------------------------------------------------------------------


class ContentCreationRequest(BaseModel):
    """Request body for POST /api/v1/pipelines/content-creation."""

    organization_id: str
    correlation_id: str
    callback_url: str
    topic: str
    provider: str = Field(
        description="Target social network, e.g. instagram_feed, tiktok, youtube",
    )
    tone: str = "professional"
    keywords: list[str] = Field(default_factory=list)
    language: str = "pt-BR"
    style_profile: dict[str, Any] | None = None
    rag_examples: list[dict[str, Any]] = Field(default_factory=list)


# ---------------------------------------------------------------------------
# Content DNA Pipeline
# ---------------------------------------------------------------------------


class ContentDNARequest(BaseModel):
    """Request body for POST /api/v1/pipelines/content-dna."""

    organization_id: str
    correlation_id: str
    callback_url: str
    time_window: str = "last_90_days"
    published_contents: list[dict[str, Any]] = Field(
        description="Published contents: [{id, title, body, provider, hashtags, published_at}]",
    )
    metrics: list[dict[str, Any]] = Field(
        description="Engagement metrics: [{content_id, impressions, reach, likes, comments, shares, saves, engagement_rate}]",
    )
    current_style_profile: dict[str, Any] | None = None


# ---------------------------------------------------------------------------
# Social Listening Pipeline
# ---------------------------------------------------------------------------


class SocialListeningRequest(BaseModel):
    """Request body for POST /api/v1/pipelines/social-listening."""

    organization_id: str
    correlation_id: str
    callback_url: str
    mention: dict[str, Any] = Field(
        description="Mention data: {id, content, platform, author_username, author_display_name, author_follower_count, url, published_at}",
    )
    brand_context: dict[str, Any] = Field(
        description="Brand context: {brand_name, industry, guidelines, tone_preferences, blacklisted_words}",
    )
    language: str = "pt-BR"


# ---------------------------------------------------------------------------
# Visual Adaptation Pipeline
# ---------------------------------------------------------------------------


class VisualAdaptationRequest(BaseModel):
    """Request body for POST /api/v1/pipelines/visual-adaptation."""

    organization_id: str
    correlation_id: str
    callback_url: str
    image_url: str
    target_networks: list[str] = Field(
        description="Target platforms: instagram, tiktok, youtube",
    )
    brand_guidelines: dict[str, Any] | None = None


# ---------------------------------------------------------------------------
# Jobs
# ---------------------------------------------------------------------------


class JobAcceptedResponse(BaseModel):
    """202 Accepted — returned when a pipeline job is enqueued."""

    job_id: str


class JobStatusResponse(BaseModel):
    """Current status of a background pipeline job."""

    job_id: str
    status: str  # "running", "completed", "failed"
    result: dict[str, Any] | None = None
