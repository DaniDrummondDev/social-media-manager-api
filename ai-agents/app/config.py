"""Application settings loaded from environment variables.

Security Notes:
- internal_secret MUST be set in production
- Use 256-bit (32 bytes) secrets: openssl rand -hex 32
- Never commit actual secrets to version control
"""

from __future__ import annotations

from functools import lru_cache

from pydantic import field_validator
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """AI Agents microservice configuration.

    All settings are loaded from environment variables.
    Defaults are suitable for local Docker development.

    Security-Critical Settings:
    - internal_secret: MUST be set for production
    - openai_api_key / anthropic_api_key: Required for LLM operations
    """

    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    # AI Provider Keys
    openai_api_key: str = ""
    anthropic_api_key: str = ""

    # Database (PostgreSQL — shared with Laravel)
    database_url: str = (
        "postgresql://social_media:secret@postgres:5432/social_media_manager"
    )

    # Redis (DB 4 — dedicated for AI Agents)
    redis_url: str = "redis://redis:6379/4"

    # Callback URL (Laravel internal endpoint)
    callback_base_url: str = "http://nginx:80/api/v1/internal"

    # Internal secret for service-to-service auth (REQUIRED in production)
    # Generate with: openssl rand -hex 32
    internal_secret: str = ""

    # Application
    log_level: str = "info"
    workers: int = 2
    environment: str = "development"

    # Rate Limiting (requests per minute per organization)
    rate_limit_content_creation: int = 10
    rate_limit_content_dna: int = 5
    rate_limit_social_listening: int = 30
    rate_limit_visual_adaptation: int = 10

    @field_validator("internal_secret")
    @classmethod
    def validate_internal_secret(cls, v: str, info) -> str:
        """Warn if internal_secret is not set in production."""
        # Note: We can't access 'environment' here directly, so we check env var
        import os

        env = os.getenv("ENVIRONMENT", "development")

        if env == "production" and not v:
            raise ValueError(
                "INTERNAL_SECRET must be set in production. "
                "Generate with: openssl rand -hex 32"
            )

        if v and len(v) < 32:
            raise ValueError(
                "INTERNAL_SECRET should be at least 32 characters (256 bits). "
                "Generate with: openssl rand -hex 32"
            )

        return v


@lru_cache
def get_settings() -> Settings:
    """Return cached settings instance."""
    return Settings()
