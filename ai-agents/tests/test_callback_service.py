"""Tests for the callback service that POSTs results back to Laravel.

Security Features Tested:
- X-Internal-Secret header for service-to-service auth
- X-Signature-SHA256 HMAC signature for payload verification
- Organization ID included in all callbacks
"""

from __future__ import annotations

from unittest.mock import AsyncMock, MagicMock, patch

import httpx
import pytest

from app.services.callback import send_callback

TEST_ORGANIZATION_ID = "550e8400-e29b-41d4-a716-446655440000"


@pytest.mark.asyncio
@patch("app.services.callback.get_settings")
@patch("app.services.callback.httpx.AsyncClient")
async def test_send_callback_posts_correct_payload(mock_client_cls, mock_settings):
    """Verify the callback sends correct JSON payload to the callback URL."""
    mock_settings.return_value = MagicMock(internal_secret="test-secret-32-characters-long!!")

    mock_response = MagicMock()
    mock_response.status_code = 202
    mock_response.raise_for_status = MagicMock()

    mock_client = AsyncMock()
    mock_client.post = AsyncMock(return_value=mock_response)
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=False)
    mock_client_cls.return_value = mock_client

    await send_callback(
        callback_url="http://nginx:80/api/v1/internal/agent-callback",
        correlation_id="test-corr-123",
        job_id="job-456",
        organization_id=TEST_ORGANIZATION_ID,
        pipeline="content_creation",
        status="completed",
        result={"title": "Generated Title"},
        metadata={"total_tokens": 500, "total_cost": 0.05},
    )

    mock_client.post.assert_called_once()
    call_args = mock_client.post.call_args
    payload = call_args.kwargs.get("json") or call_args[1].get("json")

    assert payload["correlation_id"] == "test-corr-123"
    assert payload["job_id"] == "job-456"
    assert payload["organization_id"] == TEST_ORGANIZATION_ID
    assert payload["pipeline"] == "content_creation"
    assert payload["status"] == "completed"
    assert payload["result"] == {"title": "Generated Title"}
    assert payload["metadata"]["total_tokens"] == 500


@pytest.mark.asyncio
@patch("app.services.callback.get_settings")
@patch("app.services.callback.httpx.AsyncClient")
async def test_send_callback_includes_internal_secret_header(mock_client_cls, mock_settings):
    """Verify X-Internal-Secret header is sent when configured."""
    mock_settings.return_value = MagicMock(internal_secret="my-secret-key-32-characters-long")

    mock_response = MagicMock()
    mock_response.status_code = 202
    mock_response.raise_for_status = MagicMock()

    mock_client = AsyncMock()
    mock_client.post = AsyncMock(return_value=mock_response)
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=False)
    mock_client_cls.return_value = mock_client

    await send_callback(
        callback_url="http://nginx:80/api/v1/internal/agent-callback",
        correlation_id="corr-1",
        job_id="job-1",
        organization_id=TEST_ORGANIZATION_ID,
        pipeline="content_creation",
        status="completed",
    )

    call_args = mock_client.post.call_args
    headers = call_args.kwargs.get("headers") or call_args[1].get("headers")
    assert headers["X-Internal-Secret"] == "my-secret-key-32-characters-long"


@pytest.mark.asyncio
@patch("app.services.callback.get_settings")
@patch("app.services.callback.httpx.AsyncClient")
async def test_send_callback_includes_hmac_signature(mock_client_cls, mock_settings):
    """Verify X-Signature-SHA256 HMAC header is sent when secret is configured."""
    mock_settings.return_value = MagicMock(internal_secret="my-secret-key-32-characters-long")

    mock_response = MagicMock()
    mock_response.status_code = 202
    mock_response.raise_for_status = MagicMock()

    mock_client = AsyncMock()
    mock_client.post = AsyncMock(return_value=mock_response)
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=False)
    mock_client_cls.return_value = mock_client

    await send_callback(
        callback_url="http://nginx:80/api/v1/internal/agent-callback",
        correlation_id="corr-1",
        job_id="job-1",
        organization_id=TEST_ORGANIZATION_ID,
        pipeline="content_creation",
        status="completed",
    )

    call_args = mock_client.post.call_args
    headers = call_args.kwargs.get("headers") or call_args[1].get("headers")

    # Verify HMAC signature header is present
    assert "X-Signature-SHA256" in headers

    # Verify it's a valid hex string (64 chars for SHA256)
    signature = headers["X-Signature-SHA256"]
    assert len(signature) == 64
    assert all(c in "0123456789abcdef" for c in signature)


@pytest.mark.asyncio
@patch("app.services.callback.get_settings")
@patch("app.services.callback.httpx.AsyncClient")
async def test_send_callback_omits_secret_header_when_empty(mock_client_cls, mock_settings):
    """Verify X-Internal-Secret header is omitted when internal_secret is empty."""
    mock_settings.return_value = MagicMock(internal_secret="")

    mock_response = MagicMock()
    mock_response.status_code = 202
    mock_response.raise_for_status = MagicMock()

    mock_client = AsyncMock()
    mock_client.post = AsyncMock(return_value=mock_response)
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=False)
    mock_client_cls.return_value = mock_client

    await send_callback(
        callback_url="http://nginx:80/api/v1/internal/agent-callback",
        correlation_id="corr-2",
        job_id="job-2",
        organization_id=TEST_ORGANIZATION_ID,
        pipeline="content_creation",
        status="completed",
    )

    call_args = mock_client.post.call_args
    headers = call_args.kwargs.get("headers") or call_args[1].get("headers")
    assert "X-Internal-Secret" not in headers
    assert "X-Signature-SHA256" not in headers


@pytest.mark.asyncio
@patch("app.services.callback.get_settings")
@patch("app.services.callback.httpx.AsyncClient")
async def test_send_callback_defaults_result_and_metadata_to_empty_dicts(mock_client_cls, mock_settings):
    """Verify result and metadata default to empty dicts when not provided."""
    mock_settings.return_value = MagicMock(internal_secret="secret-32-characters-exactly!!")

    mock_response = MagicMock()
    mock_response.status_code = 202
    mock_response.raise_for_status = MagicMock()

    mock_client = AsyncMock()
    mock_client.post = AsyncMock(return_value=mock_response)
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=False)
    mock_client_cls.return_value = mock_client

    await send_callback(
        callback_url="http://nginx:80/api/v1/internal/agent-callback",
        correlation_id="corr-3",
        job_id="job-3",
        organization_id=TEST_ORGANIZATION_ID,
        pipeline="content_dna",
        status="completed",
        # result and metadata intentionally omitted
    )

    call_args = mock_client.post.call_args
    payload = call_args.kwargs.get("json") or call_args[1].get("json")
    assert payload["result"] == {}
    assert payload["metadata"] == {}


@pytest.mark.asyncio
@patch("app.services.callback.get_settings")
@patch("app.services.callback.httpx.AsyncClient")
async def test_send_callback_handles_http_error_gracefully(mock_client_cls, mock_settings):
    """Verify HTTP errors (httpx.HTTPError) are caught and logged, not raised."""
    mock_settings.return_value = MagicMock(internal_secret="secret-32-characters-exactly!!")

    mock_client = AsyncMock()
    mock_client.post = AsyncMock(
        side_effect=httpx.HTTPStatusError(
            "Server Error",
            request=MagicMock(),
            response=MagicMock(status_code=500),
        )
    )
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=False)
    mock_client_cls.return_value = mock_client

    # Should NOT raise — httpx.HTTPStatusError is a subclass of httpx.HTTPError
    await send_callback(
        callback_url="http://nginx:80/api/v1/internal/agent-callback",
        correlation_id="corr-err",
        job_id="job-err",
        organization_id=TEST_ORGANIZATION_ID,
        pipeline="content_creation",
        status="completed",
    )


@pytest.mark.asyncio
@patch("app.services.callback.get_settings")
@patch("app.services.callback.httpx.AsyncClient")
async def test_send_callback_handles_timeout(mock_client_cls, mock_settings):
    """Verify timeout errors (httpx.TimeoutException) are caught gracefully.

    httpx.TimeoutException is a subclass of httpx.HTTPError, so the except
    clause in send_callback catches it without re-raising.
    """
    mock_settings.return_value = MagicMock(internal_secret="secret-32-characters-exactly!!")

    mock_client = AsyncMock()
    mock_client.post = AsyncMock(
        side_effect=httpx.TimeoutException("Connection timed out")
    )
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=False)
    mock_client_cls.return_value = mock_client

    # Should NOT raise
    await send_callback(
        callback_url="http://nginx:80/api/v1/internal/agent-callback",
        correlation_id="corr-timeout",
        job_id="job-timeout",
        organization_id=TEST_ORGANIZATION_ID,
        pipeline="content_creation",
        status="failed",
    )


@pytest.mark.asyncio
@patch("app.services.callback.get_settings")
@patch("app.services.callback.httpx.AsyncClient")
async def test_send_callback_handles_request_error(mock_client_cls, mock_settings):
    """Verify general request errors are caught gracefully."""
    mock_settings.return_value = MagicMock(internal_secret="secret-32-characters-exactly!!")

    mock_client = AsyncMock()
    mock_client.post = AsyncMock(
        side_effect=httpx.RequestError("Connection refused")
    )
    mock_client.__aenter__ = AsyncMock(return_value=mock_client)
    mock_client.__aexit__ = AsyncMock(return_value=False)
    mock_client_cls.return_value = mock_client

    # Should NOT raise
    await send_callback(
        callback_url="http://nginx:80/api/v1/internal/agent-callback",
        correlation_id="corr-connref",
        job_id="job-connref",
        organization_id=TEST_ORGANIZATION_ID,
        pipeline="content_creation",
        status="failed",
    )
