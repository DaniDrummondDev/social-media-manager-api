"""Structured logging setup compatible with Laravel JSON log format."""

from __future__ import annotations

import logging
import sys

import structlog


def setup_logging(log_level: str = "info") -> None:
    """Configure structlog for JSON output compatible with Laravel's log format.

    Log format:
    {
        "timestamp": "2026-02-27T10:30:00Z",
        "level": "info",
        "service": "ai-agents",
        "pipeline": null,
        "correlation_id": null,
        "organization_id": null,
        "message": "..."
    }
    """
    structlog.configure(
        processors=[
            structlog.contextvars.merge_contextvars,
            structlog.stdlib.filter_by_level,
            structlog.stdlib.add_logger_name,
            structlog.stdlib.add_log_level,
            structlog.processors.TimeStamper(fmt="iso"),
            structlog.processors.StackInfoRenderer(),
            structlog.processors.format_exc_info,
            structlog.processors.UnicodeDecoder(),
            _add_service_context,
            structlog.processors.JSONRenderer(),
        ],
        context_class=dict,
        logger_factory=structlog.stdlib.LoggerFactory(),
        wrapper_class=structlog.stdlib.BoundLogger,
        cache_logger_on_first_use=True,
    )

    logging.basicConfig(
        format="%(message)s",
        stream=sys.stdout,
        level=getattr(logging, log_level.upper(), logging.INFO),
    )


def _add_service_context(
    logger: structlog.types.WrappedLogger,
    method_name: str,
    event_dict: structlog.types.EventDict,
) -> structlog.types.EventDict:
    """Add default service context fields to every log entry."""
    event_dict.setdefault("service", "ai-agents")
    event_dict.setdefault("pipeline", None)
    event_dict.setdefault("correlation_id", None)
    event_dict.setdefault("organization_id", None)
    return event_dict


def get_logger(**kwargs: object) -> structlog.stdlib.BoundLogger:
    """Get a structlog logger with optional initial context."""
    return structlog.get_logger(**kwargs)
