<?php

declare(strict_types=1);

namespace App\Application\Engagement\Contracts;

interface WebhookHttpClientInterface
{
    /**
     * @param  array<string, string>  $headers
     * @return array{status: int, body: string, time_ms: int}
     *
     * @throws \Throwable
     */
    public function post(string $url, array $headers, string $payload): array;
}
