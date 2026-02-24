<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Services;

use App\Application\Identity\Contracts\TwoFactorServiceInterface;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

final class TwoFactorService implements TwoFactorServiceInterface
{
    private readonly Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function generateQrCodeUri(string $secret, string $email): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name', 'Social Media Manager'),
            $email,
            $secret,
        );
    }

    public function generateQrCodeSvg(string $uri): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd,
        );

        $writer = new Writer($renderer);

        return $writer->writeString($uri);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * @return string[]
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $codes[] = sprintf(
                '%s-%s',
                strtoupper(bin2hex(random_bytes(3))),
                strtoupper(bin2hex(random_bytes(3))),
            );
        }

        return $codes;
    }

    public function encryptSecret(string $plainSecret): string
    {
        return encrypt($plainSecret);
    }

    public function decryptSecret(string $encryptedSecret): string
    {
        return decrypt($encryptedSecret);
    }
}
