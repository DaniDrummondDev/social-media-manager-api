# Error Paths Test Suite

This directory contains comprehensive error handling tests for the Social Media Manager API.

## Test File

- `ErrorPathsTest.php` - Complete error path testing covering circuit breakers, timeouts, queue failures, and API responses

## Test Coverage (12 Tests Total)

### Circuit Breaker Tests (4 tests)

1. **Opens circuit after consecutive failures** - Verifies circuit opens after 3 consecutive failures
2. **Allows half-open state after timeout expires** - Tests transition from open to half-open state
3. **Closes circuit after successful requests in half-open** - Verifies circuit fully closes after success
4. **Fails fast when circuit is open** - Ensures no requests are made when circuit is open

### Timeout Handling Tests (3 tests)

1. **HTTP client timeout with retry** - Tests graceful handling of HTTP timeouts
2. **Database query timeout** - Verifies PostgreSQL query timeout handling
3. **Redis connection timeout with fallback** - Tests Redis connection failure scenarios

### Queue/Job Failure Tests (3 tests)

1. **Retry with exponential backoff** - Verifies job retry configuration (3 attempts, backoff: 30s, 120s, 300s)
2. **Max retries sends to failed_jobs** - Tests job failure after all retries exhausted
3. **Error logging** - Ensures exceptions are properly logged

### API Error Response Tests (2 tests)

1. **Validation errors return proper JSON** - Tests 422 response structure
2. **Production errors hide sensitive data** - Verifies no stack traces or secrets leak in production mode

## Additional Coverage

- **Webhook delivery retry logic** - Tests webhook job retry settings and network timeout handling
- **Analytics sync error handling** - Tests rate limit (429) and OAuth token expiration (401) handling

## Running Tests

```bash
# Run all error path tests
./vendor/bin/pest tests/Feature/ErrorHandling/ErrorPathsTest.php

# Run specific test groups
./vendor/bin/pest tests/Feature/ErrorHandling/ErrorPathsTest.php --filter="Circuit Breaker"
./vendor/bin/pest tests/Feature/ErrorHandling/ErrorPathsTest.php --filter="Timeout Handling"
./vendor/bin/pest tests/Feature/ErrorHandling/ErrorPathsTest.php --filter="Queue Job Failure"
./vendor/bin/pest tests/Feature/ErrorHandling/ErrorPathsTest.php --filter="API Error Response"
```

## Key Components Tested

- `AiAgentsCircuitBreaker` - Circuit breaker implementation
- `ProcessScheduledPostJob` - Publishing job with retry logic
- `DeliverWebhookJob` - Webhook delivery with retry
- `SyncPostMetricsJob` - Analytics sync job
- HTTP Client - Connection and request timeouts
- Database - Query timeouts
- Redis - Connection timeouts
- API Error Responses - Validation and production error handling

## Dependencies

- Laravel HTTP Client facade for HTTP mocking
- Queue facade for job testing
- Cache facade for circuit breaker state
- Database facade for query timeout testing
- Mockery for service mocking

## Notes

- All tests use proper isolation with `beforeEach` setup
- Tests follow Pest PHP conventions with `describe` and `it` blocks
- Circuit breaker tests use Redis cache (flushed before each test)
- Production error tests temporarily switch `app.env` to test safe error responses
- Database timeout tests use PostgreSQL `statement_timeout` setting
