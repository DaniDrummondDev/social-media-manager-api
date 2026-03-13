"""Pydantic request/response schemas for the AI Agents API.

This module defines all request/response models with security validations:
- Input length limits to prevent resource exhaustion
- UUID format validation for organization_id
- URL validation for callbacks
- Array size limits
"""

from __future__ import annotations

import uuid
from typing import Any

from pydantic import BaseModel, Field, field_validator


# ---------------------------------------------------------------------------
# Constants for Input Limits
# ---------------------------------------------------------------------------

MAX_TOPIC_LENGTH = 1000
MAX_KEYWORD_LENGTH = 100
MAX_KEYWORDS_COUNT = 50
MAX_LANGUAGE_LENGTH = 10
MAX_TONE_LENGTH = 50
MAX_PROVIDER_LENGTH = 50
MAX_RAG_EXAMPLES_COUNT = 10
MAX_PUBLISHED_CONTENTS_COUNT = 100
MAX_MENTIONS_CONTENT_LENGTH = 5000
MAX_BRAND_NAME_LENGTH = 200
MAX_TARGET_NETWORKS_COUNT = 5
MAX_URL_LENGTH = 2048
MAX_CORRELATION_ID_LENGTH = 100


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
# Base Request with Common Validations
# ---------------------------------------------------------------------------


class BasePipelineRequest(BaseModel):
    """Base class with common fields and validations for all pipeline requests."""

    organization_id: str = Field(
        min_length=36,
        max_length=36,
        description="Organization UUID (36 chars with hyphens)",
    )
    correlation_id: str = Field(
        min_length=1,
        max_length=MAX_CORRELATION_ID_LENGTH,
        description="Correlation ID for request tracing",
    )
    callback_url: str = Field(
        min_length=1,
        max_length=MAX_URL_LENGTH,
        description="URL to POST results when pipeline completes",
    )

    @field_validator("organization_id")
    @classmethod
    def validate_organization_id(cls, v: str) -> str:
        """Validate organization_id is a valid UUID format."""
        try:
            uuid.UUID(v)
        except ValueError as e:
            raise ValueError("organization_id must be a valid UUID") from e
        return v

    @field_validator("correlation_id")
    @classmethod
    def validate_correlation_id(cls, v: str) -> str:
        """Validate correlation_id format (should be UUID or safe string)."""
        # Allow UUIDs or alphanumeric with hyphens/underscores
        try:
            uuid.UUID(v)
            return v
        except ValueError:
            pass

        # Allow safe alphanumeric strings
        import re
        if not re.match(r"^[a-zA-Z0-9_-]+$", v):
            raise ValueError("correlation_id must be UUID or alphanumeric with hyphens/underscores")
        return v

    @field_validator("callback_url")
    @classmethod
    def validate_callback_url(cls, v: str) -> str:
        """Basic URL format validation (SSRF check done at runtime)."""
        from urllib.parse import urlparse

        try:
            parsed = urlparse(v)
            if parsed.scheme not in ("http", "https"):
                raise ValueError("callback_url must use http or https scheme")
            if not parsed.hostname:
                raise ValueError("callback_url must have a hostname")
        except Exception as e:
            raise ValueError(f"Invalid callback_url format: {e}") from e
        return v


# ---------------------------------------------------------------------------
# Content Creation Pipeline
# ---------------------------------------------------------------------------


class ContentCreationRequest(BasePipelineRequest):
    """Request body for POST /api/v1/pipelines/content-creation."""

    topic: str = Field(
        min_length=1,
        max_length=MAX_TOPIC_LENGTH,
        description="Content topic or brief",
    )
    provider: str = Field(
        min_length=1,
        max_length=MAX_PROVIDER_LENGTH,
        description="Target social network, e.g. instagram_feed, tiktok, youtube",
    )
    tone: str = Field(
        default="professional",
        max_length=MAX_TONE_LENGTH,
    )
    keywords: list[str] = Field(
        default_factory=list,
        max_length=MAX_KEYWORDS_COUNT,
    )
    language: str = Field(
        default="pt-BR",
        max_length=MAX_LANGUAGE_LENGTH,
    )
    style_profile: dict[str, Any] | None = None
    rag_examples: list[dict[str, Any]] = Field(
        default_factory=list,
        max_length=MAX_RAG_EXAMPLES_COUNT,
    )

    @field_validator("keywords")
    @classmethod
    def validate_keywords(cls, v: list[str]) -> list[str]:
        """Validate keywords count and length."""
        if len(v) > MAX_KEYWORDS_COUNT:
            raise ValueError(f"Maximum {MAX_KEYWORDS_COUNT} keywords allowed")
        for i, kw in enumerate(v):
            if len(kw) > MAX_KEYWORD_LENGTH:
                raise ValueError(f"Keyword {i} exceeds max length of {MAX_KEYWORD_LENGTH}")
        return v

    @field_validator("rag_examples")
    @classmethod
    def validate_rag_examples(cls, v: list[dict[str, Any]]) -> list[dict[str, Any]]:
        """Validate RAG examples count."""
        if len(v) > MAX_RAG_EXAMPLES_COUNT:
            raise ValueError(f"Maximum {MAX_RAG_EXAMPLES_COUNT} RAG examples allowed")
        return v


# ---------------------------------------------------------------------------
# Content DNA Pipeline
# ---------------------------------------------------------------------------


class ContentDNARequest(BasePipelineRequest):
    """Request body for POST /api/v1/pipelines/content-dna."""

    time_window: str = Field(
        default="last_90_days",
        max_length=50,
    )
    published_contents: list[dict[str, Any]] = Field(
        max_length=MAX_PUBLISHED_CONTENTS_COUNT,
        description="Published contents: [{id, title, body, provider, hashtags, published_at}]",
    )
    metrics: list[dict[str, Any]] = Field(
        max_length=MAX_PUBLISHED_CONTENTS_COUNT,
        description="Engagement metrics: [{content_id, impressions, reach, likes, comments, shares, saves, engagement_rate}]",
    )
    current_style_profile: dict[str, Any] | None = None

    @field_validator("published_contents")
    @classmethod
    def validate_published_contents(cls, v: list[dict[str, Any]]) -> list[dict[str, Any]]:
        """Validate published contents count."""
        if len(v) > MAX_PUBLISHED_CONTENTS_COUNT:
            raise ValueError(f"Maximum {MAX_PUBLISHED_CONTENTS_COUNT} published contents allowed")
        return v

    @field_validator("metrics")
    @classmethod
    def validate_metrics(cls, v: list[dict[str, Any]]) -> list[dict[str, Any]]:
        """Validate metrics count."""
        if len(v) > MAX_PUBLISHED_CONTENTS_COUNT:
            raise ValueError(f"Maximum {MAX_PUBLISHED_CONTENTS_COUNT} metrics allowed")
        return v


# ---------------------------------------------------------------------------
# Social Listening Pipeline
# ---------------------------------------------------------------------------


class SocialListeningRequest(BasePipelineRequest):
    """Request body for POST /api/v1/pipelines/social-listening."""

    mention: dict[str, Any] = Field(
        description="Mention data: {id, content, platform, author_username, author_display_name, author_follower_count, url, published_at}",
    )
    brand_context: dict[str, Any] = Field(
        description="Brand context: {brand_name, industry, guidelines, tone_preferences, blacklisted_words}",
    )
    language: str = Field(
        default="pt-BR",
        max_length=MAX_LANGUAGE_LENGTH,
    )

    @field_validator("mention")
    @classmethod
    def validate_mention(cls, v: dict[str, Any]) -> dict[str, Any]:
        """Validate mention content length."""
        content = v.get("content", "")
        if isinstance(content, str) and len(content) > MAX_MENTIONS_CONTENT_LENGTH:
            raise ValueError(f"Mention content exceeds max length of {MAX_MENTIONS_CONTENT_LENGTH}")
        return v

    @field_validator("brand_context")
    @classmethod
    def validate_brand_context(cls, v: dict[str, Any]) -> dict[str, Any]:
        """Validate brand context."""
        brand_name = v.get("brand_name", "")
        if isinstance(brand_name, str) and len(brand_name) > MAX_BRAND_NAME_LENGTH:
            raise ValueError(f"Brand name exceeds max length of {MAX_BRAND_NAME_LENGTH}")
        return v


# ---------------------------------------------------------------------------
# Visual Adaptation Pipeline
# ---------------------------------------------------------------------------


class VisualAdaptationRequest(BasePipelineRequest):
    """Request body for POST /api/v1/pipelines/visual-adaptation."""

    image_url: str = Field(
        min_length=1,
        max_length=MAX_URL_LENGTH,
        description="URL of the source image to adapt",
    )
    target_networks: list[str] = Field(
        max_length=MAX_TARGET_NETWORKS_COUNT,
        description="Target platforms: instagram, tiktok, youtube",
    )
    brand_guidelines: dict[str, Any] | None = None

    @field_validator("target_networks")
    @classmethod
    def validate_target_networks(cls, v: list[str]) -> list[str]:
        """Validate target networks."""
        if len(v) > MAX_TARGET_NETWORKS_COUNT:
            raise ValueError(f"Maximum {MAX_TARGET_NETWORKS_COUNT} target networks allowed")

        allowed_networks = {"instagram", "tiktok", "youtube", "instagram_story", "instagram_reel"}
        for network in v:
            if network.lower() not in allowed_networks:
                raise ValueError(f"Invalid network: {network}. Allowed: {allowed_networks}")
        return v


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
