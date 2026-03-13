"""FastAPI application for the AI Agents microservice."""

from __future__ import annotations

from contextlib import asynccontextmanager
from typing import AsyncGenerator

import asyncpg
import redis.asyncio as aioredis
from fastapi import FastAPI

from app.api.routes import router
from app.config import get_settings
from app.shared.logging import get_logger, setup_logging


@asynccontextmanager
async def lifespan(application: FastAPI) -> AsyncGenerator[None, None]:
    """Manage application lifecycle — init and close external connections."""
    settings = get_settings()
    setup_logging(settings.log_level)
    logger = get_logger()

    # Initialize Redis (DB 4) with connection pool for better concurrency
    logger.info("Connecting to Redis", url=settings.redis_url)
    application.state.redis = aioredis.from_url(
        settings.redis_url,
        decode_responses=True,
        max_connections=20,
    )

    # Initialize PostgreSQL connection pool
    logger.info("Connecting to PostgreSQL")
    application.state.pg_pool = await asyncpg.create_pool(
        settings.database_url,
        min_size=2,
        max_size=10,
    )

    logger.info("AI Agents microservice started", environment=settings.environment)

    yield

    # Shutdown
    logger.info("Shutting down AI Agents microservice")
    await application.state.redis.close()
    await application.state.pg_pool.close()
    logger.info("Connections closed")


app = FastAPI(
    title="AI Agents — Social Media Manager",
    description="Multi-agent AI pipelines (LangGraph) for content creation, DNA analysis, social listening and visual adaptation.",
    version="0.1.0",
    lifespan=lifespan,
)

app.include_router(router)
