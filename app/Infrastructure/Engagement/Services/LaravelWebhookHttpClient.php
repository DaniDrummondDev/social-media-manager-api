<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Services;

use App\Application\Engagement\Contracts\WebhookHttpClientInterface;
use Illuminate\Support\Facades\Http;

final class LaravelWebhookHttpClient implements WebhookHttpClientInterface
{
    /**
     * @param  array<string, string>  $headers
     * @return array{status: int, body: string, time_ms: int}
     */
    public function post(string $url, array $headers, string $payload): array
    {
        $start = hrtime(true);

        $response = Http::timeout(10)
            ->withHeaders($headers)
            ->withBody($payload, 'application/json')
            ->post($url);

        $elapsed = (int) ((hrtime(true) - $start) / 1_000_000);

        return [
            'status' => $response->status(),
            'body' => $response->body(),
            'time_ms' => $elapsed,
        ];
    }
}
