<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Services;

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final class JwtAuthTokenService implements AuthTokenServiceInterface
{
    private readonly string $privateKey;

    private readonly string $publicKey;

    private readonly string $algo;

    private readonly int $ttl;

    private readonly int $tempTtl;

    private readonly string $blacklistPrefix;

    private readonly string $issuer;

    public function __construct()
    {
        /** @var array<string, mixed> $config */
        $config = config('jwt');

        $privateKeyPath = base_path($config['keys']['private']);
        $publicKeyPath = base_path($config['keys']['public']);

        $this->privateKey = is_file($privateKeyPath)
            ? (string) file_get_contents($privateKeyPath)
            : $config['keys']['private'];

        $this->publicKey = is_file($publicKeyPath)
            ? (string) file_get_contents($publicKeyPath)
            : $config['keys']['public'];

        $this->algo = $config['algo'];
        $this->ttl = (int) $config['ttl'];
        $this->tempTtl = (int) $config['temp_ttl'];
        $this->blacklistPrefix = $config['blacklist_prefix'];
        $this->issuer = $config['issuer'];
    }

    /**
     * @return array{token: string, jti: string, expires_in: int}
     */
    public function generateAccessToken(string $userId, string $organizationId, string $email, string $role): array
    {
        $jti = (string) Str::uuid();
        $now = time();
        $expiresIn = $this->ttl * 60;

        $payload = [
            'iss' => $this->issuer,
            'sub' => $userId,
            'org' => $organizationId,
            'email' => $email,
            'role' => $role,
            'jti' => $jti,
            'iat' => $now,
            'exp' => $now + $expiresIn,
            'token_type' => 'access',
        ];

        return [
            'token' => $this->encode($payload),
            'jti' => $jti,
            'expires_in' => $expiresIn,
        ];
    }

    public function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function blacklistToken(string $jti, int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            return;
        }

        Redis::connection('cache')->command('setex', [
            $this->blacklistPrefix.$jti,
            $ttlSeconds,
            '1',
        ]);
    }

    /**
     * @return array{sub: string, org: string, email: string, role: string, jti: string}|null
     */
    public function validateAccessToken(string $token): ?array
    {
        $payload = $this->decode($token);

        if ($payload === null) {
            return null;
        }

        if (($payload['token_type'] ?? '') !== 'access') {
            return null;
        }

        if ($this->isBlacklisted($payload['jti'] ?? '')) {
            return null;
        }

        return [
            'sub' => $payload['sub'],
            'org' => $payload['org'],
            'email' => $payload['email'],
            'role' => $payload['role'],
            'jti' => $payload['jti'],
        ];
    }

    /**
     * @return array{token: string, expires_in: int}
     */
    public function generateTempToken(string $userId): array
    {
        $now = time();
        $expiresIn = $this->tempTtl * 60;

        $payload = [
            'iss' => $this->issuer,
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + $expiresIn,
            'token_type' => '2fa_temp',
        ];

        return [
            'token' => $this->encode($payload),
            'expires_in' => $expiresIn,
        ];
    }

    public function validateTempToken(string $token): ?string
    {
        $payload = $this->decode($token);

        if ($payload === null) {
            return null;
        }

        if (($payload['token_type'] ?? '') !== '2fa_temp') {
            return null;
        }

        return $payload['sub'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => $this->algo, 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));

        $signature = '';
        openssl_sign("$header.$body", $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return "$header.$body.".$this->base64UrlEncode($signature);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $body, $signature] = $parts;

        $decodedSignature = $this->base64UrlDecode($signature);
        $publicKey = openssl_pkey_get_public($this->publicKey);

        if ($publicKey === false) {
            return null;
        }

        $valid = openssl_verify("$header.$body", $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($valid !== 1) {
            return null;
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($this->base64UrlDecode($body), true);

        if ($payload === null) {
            return null;
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private function isBlacklisted(string $jti): bool
    {
        if ($jti === '') {
            return false;
        }

        return (bool) Redis::connection('cache')->command('exists', [$this->blacklistPrefix.$jti]);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}
