"""Visual Adaptation pipeline — LangGraph StateGraph composition."""

from __future__ import annotations

from langgraph.graph import END, START, StateGraph
from langgraph.graph.state import CompiledStateGraph

from app.agents.visual_adaptation.crop_strategist import crop_strategist_node
from app.agents.visual_adaptation.network_adapters import network_adapters_node
from app.agents.visual_adaptation.quality_checker import (
    quality_checker_node,
    quality_router,
)
from app.agents.visual_adaptation.state import VisualAdaptationState
from app.agents.visual_adaptation.vision_analyzer import vision_analyzer_node


def build_visual_adaptation_graph() -> CompiledStateGraph:
    """Build and compile the Visual Adaptation StateGraph.

    Flow::

        START -> vision_analyzer -> crop_strategist -> network_adapters -> quality_checker --(conditional)--> END
                                         ^                                        |
                                         |--------------- retry ------------------|

    The quality_checker may send back to the crop_strategist up to 2 times.
    After that the best available result is forwarded to END.
    """
    builder = StateGraph(VisualAdaptationState)

    # Nodes
    builder.add_node("vision_analyzer", vision_analyzer_node)
    builder.add_node("crop_strategist", crop_strategist_node)
    builder.add_node("network_adapters", network_adapters_node)
    builder.add_node("quality_checker", quality_checker_node)

    # Edges
    builder.add_edge(START, "vision_analyzer")
    builder.add_edge("vision_analyzer", "crop_strategist")
    builder.add_edge("crop_strategist", "network_adapters")
    builder.add_edge("network_adapters", "quality_checker")
    builder.add_conditional_edges(
        "quality_checker",
        quality_router,
        {"crop_strategist": "crop_strategist", "end": END},
    )

    return builder.compile()
