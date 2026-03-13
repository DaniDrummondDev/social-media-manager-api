"""State definition for the Social Listening Intelligence pipeline."""

from __future__ import annotations

import operator
from typing import Annotated, Any, TypedDict


def _sum_reducer(a: int | float, b: int | float) -> int | float:
    """Reducer that sums numeric values from parallel branches."""
    return (a or 0) + (b or 0)


class SocialListeningState(TypedDict):
    """Typed state that flows through the Social Listening Intelligence graph.

    The MentionClassifier sets ``classification`` (including ``is_crisis``),
    which downstream agents use to adjust processing depth.
    Fully linear flow — no conditional edges or retry loops.
    """

    # -- Input (provided by Laravel) ----------------------------------------
    organization_id: str
    mention: dict[str, Any]
    # {id, content, platform, author_username, author_display_name,
    #  author_follower_count, url, published_at}
    brand_context: dict[str, Any]
    # {brand_name, industry, guidelines, tone_preferences, blacklisted_words}
    language: str  # e.g. "pt-BR", "en-US"

    # -- Intermediate analysis results --------------------------------------
    classification: dict[str, Any] | None    # MentionClassification output
    sentiment_analysis: dict[str, Any] | None  # DeepSentimentAnalysis output
    suggested_response: dict[str, Any] | None  # SuggestedResponse output

    # -- Output -------------------------------------------------------------
    safety_result: dict[str, Any] | None     # SafetyCheckResult output (final)

    # -- Metadata -----------------------------------------------------------
    callback_url: str
    correlation_id: str
    total_tokens: Annotated[int, _sum_reducer]
    total_cost: Annotated[float, _sum_reducer]
    agents_executed: Annotated[list[str], operator.add]
