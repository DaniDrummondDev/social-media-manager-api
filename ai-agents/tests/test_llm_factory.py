"""Tests for the LLM factory (app/services/llm.py).

get_llm routes requests to ChatAnthropic for ``claude-*`` models and to
ChatOpenAI for all other model names.
"""

from __future__ import annotations

from unittest.mock import MagicMock, patch

from langchain_anthropic import ChatAnthropic
from langchain_openai import ChatOpenAI

from app.services.llm import get_llm


@patch("app.services.llm.get_settings")
def test_get_llm_returns_openai_for_gpt_model(mock_settings):
    """get_llm with a GPT model should return a ChatOpenAI instance."""
    mock_settings.return_value = MagicMock(
        openai_api_key="sk-test-key",
        anthropic_api_key="ant-test-key",
    )

    llm = get_llm(model="gpt-4o")

    assert isinstance(llm, ChatOpenAI)


@patch("app.services.llm.get_settings")
def test_get_llm_returns_anthropic_for_claude_model(mock_settings):
    """get_llm with a claude-* model should return a ChatAnthropic instance."""
    mock_settings.return_value = MagicMock(
        openai_api_key="sk-test-key",
        anthropic_api_key="ant-test-key",
    )

    llm = get_llm(model="claude-3-5-sonnet-20241022")

    assert isinstance(llm, ChatAnthropic)


@patch("app.services.llm.get_settings")
def test_get_llm_respects_temperature(mock_settings):
    """Temperature parameter should be forwarded to the instantiated model."""
    mock_settings.return_value = MagicMock(
        openai_api_key="sk-test-key",
        anthropic_api_key="ant-test-key",
    )

    llm = get_llm(model="gpt-4o", temperature=0.3)

    assert isinstance(llm, ChatOpenAI)
    assert llm.temperature == 0.3


@patch("app.services.llm.get_settings")
def test_get_llm_anthropic_respects_temperature(mock_settings):
    """Temperature should also be forwarded to ChatAnthropic."""
    mock_settings.return_value = MagicMock(
        openai_api_key="sk-test-key",
        anthropic_api_key="ant-test-key",
    )

    llm = get_llm(model="claude-opus-4-6", temperature=0.1)

    assert isinstance(llm, ChatAnthropic)
    assert llm.temperature == 0.1


@patch("app.services.llm.get_settings")
def test_get_llm_default_model_is_openai(mock_settings):
    """Calling get_llm with no arguments should return a ChatOpenAI (default model gpt-4o)."""
    mock_settings.return_value = MagicMock(
        openai_api_key="sk-test-key",
        anthropic_api_key="ant-test-key",
    )

    llm = get_llm()

    assert isinstance(llm, ChatOpenAI)


@patch("app.services.llm.get_settings")
def test_get_llm_claude_prefix_routes_to_anthropic(mock_settings):
    """Any model name starting with 'claude' routes to Anthropic, regardless of version."""
    mock_settings.return_value = MagicMock(
        openai_api_key="sk-test-key",
        anthropic_api_key="ant-test-key",
    )

    for model in ("claude-3-haiku-20240307", "claude-3-opus-20240229", "claude-sonnet-4-6"):
        llm = get_llm(model=model)
        assert isinstance(llm, ChatAnthropic), f"Expected ChatAnthropic for model={model}"
