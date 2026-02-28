"""Social Listening Intelligence pipeline — LangGraph StateGraph composition."""

from __future__ import annotations

from langgraph.graph import END, START, StateGraph
from langgraph.graph.state import CompiledStateGraph

from app.agents.social_listening.mention_classifier import mention_classifier_node
from app.agents.social_listening.response_strategist import response_strategist_node
from app.agents.social_listening.safety_checker import safety_checker_node
from app.agents.social_listening.sentiment_analyzer import sentiment_analyzer_node
from app.agents.social_listening.state import SocialListeningState


def build_social_listening_graph() -> CompiledStateGraph:
    """Build and compile the Social Listening Intelligence StateGraph.

    Flow::

        START -> mention_classifier -> sentiment_analyzer -> response_strategist -> safety_checker -> END

    Fully linear — no conditional edges or retry loops.
    Crisis differentiation is handled by category-aware prompts within agents.
    """
    builder = StateGraph(SocialListeningState)

    builder.add_node("mention_classifier", mention_classifier_node)
    builder.add_node("sentiment_analyzer", sentiment_analyzer_node)
    builder.add_node("response_strategist", response_strategist_node)
    builder.add_node("safety_checker", safety_checker_node)

    builder.add_edge(START, "mention_classifier")
    builder.add_edge("mention_classifier", "sentiment_analyzer")
    builder.add_edge("sentiment_analyzer", "response_strategist")
    builder.add_edge("response_strategist", "safety_checker")
    builder.add_edge("safety_checker", END)

    return builder.compile()
