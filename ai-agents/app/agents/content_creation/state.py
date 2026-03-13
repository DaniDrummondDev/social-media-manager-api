"""State definition for the Content Creation pipeline."""

from __future__ import annotations

import operator
from typing import Annotated, Any, TypedDict


def _sum_reducer(a: int | float, b: int | float) -> int | float:
    """Reducer that sums numeric values across nodes."""
    return (a or 0) + (b or 0)


class ContentCreationState(TypedDict):
    """Typed state that flows through every node in the Content Creation graph.

    Nodes receive the full state and return a partial dict with only the keys
    they update. Reducers accumulate values across the pipeline:
    - ``agents_executed``: list append (tracks which agents ran)
    - ``total_tokens``: sum (cumulative token usage)
    - ``total_cost``: sum (cumulative cost in USD)
    """

    # -- Input (provided by the caller) ------------------------------------
    organization_id: str
    topic: str
    provider: str  # e.g. "instagram_feed", "tiktok", "youtube"
    tone: str  # e.g. "casual", "professional", "witty"
    keywords: list[str]
    language: str  # e.g. "pt-BR", "en-US"
    style_profile: dict[str, Any] | None
    rag_examples: list[dict[str, Any]]

    # -- Pipeline intermediary state ----------------------------------------
    brief: dict[str, Any] | None
    draft: str | None
    review_passed: bool
    review_feedback: str | None
    retry_count: int

    # -- Output (produced by the optimizer) ---------------------------------
    final_content: dict[str, Any] | None

    # -- Metadata (with reducers for accumulation) --------------------------
    callback_url: str
    correlation_id: str
    total_tokens: Annotated[int, _sum_reducer]  # Sum tokens across all nodes
    total_cost: Annotated[float, _sum_reducer]  # Sum costs across all nodes
    agents_executed: Annotated[list[str], operator.add]  # Concatenate agent lists
