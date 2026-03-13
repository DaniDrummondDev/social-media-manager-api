"""State definition for the Content DNA Deep Analysis pipeline."""

from __future__ import annotations

import operator
from typing import Annotated, Any, TypedDict


def _sum_reducer(a: int | float, b: int | float) -> int | float:
    """Reducer that sums numeric values from parallel branches."""
    return (a or 0) + (b or 0)


class ContentDNAState(TypedDict):
    """Typed state that flows through the Content DNA analysis graph.

    The pipeline uses parallel execution for style_analyzer and engagement_analyzer,
    then converges at the synthesizer. State fields use reducers to merge results
    from parallel branches.

    Data is injected by Laravel: published contents + engagement metrics.
    """

    # -- Input (provided by Laravel) ----------------------------------------
    organization_id: str
    published_contents: list[dict[str, Any]]  # [{id, title, body, provider, hashtags, published_at}]
    metrics: list[dict[str, Any]]  # [{content_id, impressions, reach, likes, comments, shares, saves, engagement_rate}]
    current_style_profile: dict[str, Any] | None  # Existing OrgStyleProfile if available
    time_window: str  # e.g. "last_90_days", "last_180_days"

    # -- Intermediate analysis results --------------------------------------
    style_patterns: dict[str, Any] | None
    engagement_correlations: dict[str, Any] | None

    # -- Output -------------------------------------------------------------
    dna_profile: dict[str, Any] | None

    # -- Metadata (with reducers for parallel branch merging) ---------------
    callback_url: str
    correlation_id: str
    total_tokens: Annotated[int, _sum_reducer]  # Sum tokens from parallel nodes
    total_cost: Annotated[float, _sum_reducer]  # Sum costs from parallel nodes
    agents_executed: Annotated[list[str], operator.add]  # Concatenate agent lists
