"""Writer agent — generates content draft following the Planner's brief."""

from __future__ import annotations

from typing import Any

from app.agents.content_creation.prompts import WRITER_SYSTEM_PROMPT
from app.agents.content_creation.state import ContentCreationState
from app.services.llm import get_llm
from app.shared.logging import get_logger
from app.shared.token_tracker import TokenTrackingCallback, estimate_cost


async def writer_node(state: ContentCreationState) -> dict[str, Any]:
    """Generate (or regenerate) a content draft based on the brief."""
    logger = get_logger(
        pipeline="content_creation",
        agent="writer",
        correlation_id=state["correlation_id"],
        organization_id=state["organization_id"],
    )
    is_retry = state.get("review_feedback") is not None and state["retry_count"] > 0
    logger.info("Writer started", retry=is_retry, retry_count=state["retry_count"])

    # Inject reviewer feedback when retrying
    feedback_block = ""
    if is_retry:
        feedback_block = (
            "IMPORTANT — The reviewer rejected your previous draft with "
            "the following feedback. Address every point:\n\n"
            f"{state['review_feedback']}"
        )

    system_prompt = WRITER_SYSTEM_PROMPT.format(feedback_block=feedback_block)

    # Build human message
    parts: list[str] = [
        f"Brief:\n{state['brief']}",
        f"Topic: {state['topic']}",
        f"Language: {state['language']}",
    ]
    if state.get("style_profile"):
        parts.append(f"Brand style profile: {state['style_profile']}")
    if state.get("rag_examples"):
        parts.append(
            "Reference examples:\n"
            + "\n---\n".join(str(ex) for ex in state["rag_examples"])
        )

    human_message = "\n\n".join(parts)

    tracker = TokenTrackingCallback()
    llm = get_llm(temperature=0.7, callbacks=[tracker])
    response = await llm.ainvoke([
        ("system", system_prompt),
        ("human", human_message),
    ])

    draft = response.content if hasattr(response, "content") else str(response)
    token_cost = estimate_cost(tracker.usage)

    logger.info(
        "Writer finished",
        draft_length=len(draft),
        tokens=tracker.usage.total_tokens,
        cost_usd=token_cost,
    )

    return {
        "draft": draft,
        "agents_executed": ["writer"],
        "total_tokens": tracker.usage.total_tokens,
        "total_cost": token_cost,
    }
