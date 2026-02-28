<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Services;

use App\Application\Engagement\Contracts\WebhookHttpClientInterface;
use App\Application\Engagement\Exceptions\SsrfException;
use Illuminate\Support\Facades\Http;

final class LaravelWebhookHttpClient implements WebhookHttpClientInterface
{
    /**
     * Private/reserved CIDR ranges that must be blocked (SSRF prevention).
     *
     * @var array<int, string>
     */
    private const BLOCKED_CIDR_RANGES = [
        '127.0.0.0/8',      // Loopback
        '10.0.0.0/8',       // Private (Class A)
        '172.16.0.0/12',    // Private (Class B)
        '192.168.0.0/16',   // Private (Class C)
        '169.254.0.0/16',   // Link-local
        '0.0.0.0/8',        // "This" network
    ];

    /**
     * Blocked IPv6 addresses.
     *
     * @var array<int, string>
     */
    private const BLOCKED_IPV6 = [
        '::1', // IPv6 loopback
    ];

    /**
     * @param  array<string, string>  $headers
     * @return array{status: int, body: string, time_ms: int}
     *
     * @throws SsrfException
     */
    public function post(string $url, array $headers, string $payload): array
    {
        $this->guardAgainstSsrf($url);

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

    /**
     * Resolve the webhook URL's host to an IP address and verify it is not
     * within any private or reserved range.
     *
     * @throws SsrfException
     */
    private function guardAgainstSsrf(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null || $host === false || $host === '') {
            throw new SsrfException($url);
        }

        // Resolve hostname to IP address
        $ip = gethostbyname($host);

        // gethostbyname returns the original hostname on failure
        if ($ip === $host && filter_var($host, FILTER_VALIDATE_IP) === false) {
            throw new SsrfException($url);
        }

        // Check IPv6 loopback (host may be an IPv6 literal)
        $cleanIp = trim($ip, '[]');
        foreach (self::BLOCKED_IPV6 as $blockedIpv6) {
            if ($cleanIp === $blockedIpv6) {
                throw new SsrfException($url);
            }
        }

        // Check IPv4 private/reserved ranges
        if ($this->isIpInBlockedRange($cleanIp)) {
            throw new SsrfException($url);
        }
    }

    /**
     * Check whether an IP address falls within any blocked CIDR range.
     */
    private function isIpInBlockedRange(string $ip): bool
    {
        $ipLong = ip2long($ip);

        if ($ipLong === false) {
            // Not a valid IPv4 — could be IPv6; block for safety
            return true;
        }

        foreach (self::BLOCKED_CIDR_RANGES as $cidr) {
            [$subnet, $mask] = explode('/', $cidr);
            $subnetLong = ip2long($subnet);
            $maskBits = (int) $mask;

            if ($subnetLong === false) {
                continue;
            }

            // Create bitmask: e.g. /8 → 0xFF000000
            $bitmask = -1 << (32 - $maskBits);

            if (($ipLong & $bitmask) === ($subnetLong & $bitmask)) {
                return true;
            }
        }

        return false;
    }
}
