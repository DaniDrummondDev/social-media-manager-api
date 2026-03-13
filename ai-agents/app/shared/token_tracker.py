"""Token tracking utilities for LLM calls using LangChain callbacks."""

from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any

from langchain_core.callbacks import BaseCallbackHandler
from langchain_core.outputs import LLMResult


@dataclass
class TokenUsage:
    """Container for token usage statistics."""

    prompt_tokens: int = 0
    completion_tokens: int = 0
    total_tokens: int = 0
    model: str = ""

    def to_dict(self) -> dict[str, Any]:
        return {
            "prompt_tokens": self.prompt_tokens,
            "completion_tokens": self.completion_tokens,
            "total_tokens": self.total_tokens,
            "model": self.model,
        }


class TokenTrackingCallback(BaseCallbackHandler):
    """LangChain callback handler that tracks token usage from LLM responses.

    Usage::

        tracker = TokenTrackingCallback()
        llm = get_llm(callbacks=[tracker])
        await llm.ainvoke(messages)
        print(tracker.usage.total_tokens)
    """

    usage: TokenUsage = field(default_factory=TokenUsage)

    def __init__(self) -> None:
        super().__init__()
        self.usage = TokenUsage()

    def on_llm_end(self, response: LLMResult, **kwargs: Any) -> None:
        """Extract token usage from LLM response."""
        if not response.llm_output:
            return

        # OpenAI format
        token_usage = response.llm_output.get("token_usage", {})
        if token_usage:
            self.usage.prompt_tokens += token_usage.get("prompt_tokens", 0)
            self.usage.completion_tokens += token_usage.get("completion_tokens", 0)
            self.usage.total_tokens += token_usage.get("total_tokens", 0)

        # Anthropic format
        usage = response.llm_output.get("usage", {})
        if usage:
            self.usage.prompt_tokens += usage.get("input_tokens", 0)
            self.usage.completion_tokens += usage.get("output_tokens", 0)
            self.usage.total_tokens += usage.get("input_tokens", 0) + usage.get(
                "output_tokens", 0
            )

        # Model name
        model = response.llm_output.get("model_name") or response.llm_output.get(
            "model", ""
        )
        if model:
            self.usage.model = model

    def reset(self) -> None:
        """Reset usage counters."""
        self.usage = TokenUsage()


def estimate_cost(usage: TokenUsage) -> float:
    """Estimate cost in USD based on model and token counts.

    Prices are approximate and should be updated periodically.
    """
    # Pricing per 1M tokens (as of 2024)
    pricing = {
        # OpenAI
        "gpt-4o": {"input": 2.50, "output": 10.00},
        "gpt-4o-mini": {"input": 0.15, "output": 0.60},
        "gpt-4-turbo": {"input": 10.00, "output": 30.00},
        # Anthropic
        "claude-3-5-sonnet": {"input": 3.00, "output": 15.00},
        "claude-3-opus": {"input": 15.00, "output": 75.00},
        "claude-3-haiku": {"input": 0.25, "output": 1.25},
    }

    # Find matching model
    model_key = None
    for key in pricing:
        if key in usage.model.lower():
            model_key = key
            break

    if not model_key:
        # Default to gpt-4o pricing
        model_key = "gpt-4o"

    rates = pricing[model_key]
    input_cost = (usage.prompt_tokens / 1_000_000) * rates["input"]
    output_cost = (usage.completion_tokens / 1_000_000) * rates["output"]

    return input_cost + output_cost
