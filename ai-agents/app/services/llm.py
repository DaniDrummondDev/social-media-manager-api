"""LLM factory — creates LangChain chat model instances."""

from __future__ import annotations

from typing import Sequence

from langchain_anthropic import ChatAnthropic
from langchain_core.callbacks import BaseCallbackHandler
from langchain_core.language_models import BaseChatModel
from langchain_openai import ChatOpenAI

from app.config import get_settings


def get_llm(
    model: str = "gpt-4o",
    temperature: float = 0.7,
    callbacks: Sequence[BaseCallbackHandler] | None = None,
) -> BaseChatModel:
    """Return a LangChain chat model for the requested provider.

    Models starting with ``claude`` use Anthropic; everything else uses OpenAI.

    Args:
        model: Model identifier (e.g., "gpt-4o", "claude-3-5-sonnet-20241022")
        temperature: Sampling temperature (0.0-1.0)
        callbacks: Optional list of LangChain callbacks (e.g., TokenTrackingCallback)

    Returns:
        Configured LangChain chat model instance
    """
    settings = get_settings()
    callback_list = list(callbacks) if callbacks else None

    if model.startswith("claude"):
        return ChatAnthropic(
            api_key=settings.anthropic_api_key,
            model=model,
            temperature=temperature,
            callbacks=callback_list,
        )

    return ChatOpenAI(
        api_key=settings.openai_api_key,
        model=model,
        temperature=temperature,
        callbacks=callback_list,
    )
