"""Content Creation pipeline — LangGraph StateGraph composition."""

from __future__ import annotations

from langgraph.graph import END, START, StateGraph
from langgraph.graph.state import CompiledStateGraph

from app.agents.content_creation.optimizer import optimizer_node
from app.agents.content_creation.planner import planner_node
from app.agents.content_creation.reviewer import review_router, reviewer_node
from app.agents.content_creation.state import ContentCreationState
from app.agents.content_creation.writer import writer_node


def build_content_creation_graph() -> CompiledStateGraph:
    """Build and compile the Content Creation StateGraph.

    Flow::

        START -> planner -> writer -> reviewer --(conditional)--> optimizer -> END
                              ^                        |
                              |-------- retry ---------|

    The reviewer may send the draft back to the writer up to 2 times.
    After that the best available draft is forwarded to the optimizer.
    """
    builder = StateGraph(ContentCreationState)

    # Nodes
    builder.add_node("planner", planner_node)
    builder.add_node("writer", writer_node)
    builder.add_node("reviewer", reviewer_node)
    builder.add_node("optimizer", optimizer_node)

    # Edges
    builder.add_edge(START, "planner")
    builder.add_edge("planner", "writer")
    builder.add_edge("writer", "reviewer")
    builder.add_conditional_edges(
        "reviewer",
        review_router,
        {"writer": "writer", "optimizer": "optimizer"},
    )
    builder.add_edge("optimizer", END)

    return builder.compile()
