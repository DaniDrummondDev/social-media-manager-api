<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Identity\Contracts\TwoFactorServiceInterface;

final class FakeTwoFactorService implements TwoFactorServiceInterface
{
    public const TEST_SECRET = 'JBSWY3DPEHPK3PXP';

    public const TEST_QR_URI = 'otpauth://totp/TestApp:test@example.com?secret=JBSWY3DPEHPK3PXP&issuer=TestApp';

    public const TEST_QR_SVG = '<svg>test-qr</svg>';

    public function generateSecret(): string
    {
        return self::TEST_SECRET;
    }

    public function generateQrCodeUri(string $secret, string $email): string
    {
        return self::TEST_QR_URI;
    }

    public function generateQrCodeSvg(string $uri): string
    {
        return self::TEST_QR_SVG;
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return $code === '123456';
    }

    /**
     * @return string[]
     */
    public function generateRecoveryCodes(): array
    {
        return [
            'AAAA-BBBB',
            'CCCC-DDDD',
            'EEEE-FFFF',
            'GGGG-HHHH',
            'IIII-JJJJ',
            'KKKK-LLLL',
            'MMMM-NNNN',
            'OOOO-PPPP',
        ];
    }

    public function encryptSecret(string $plainSecret): string
    {
        return base64_encode('fake-encrypted:'.$plainSecret);
    }

    public function decryptSecret(string $encryptedSecret): string
    {
        $decoded = base64_decode($encryptedSecret, true) ?: '';

        return str_replace('fake-encrypted:', '', $decoded);
    }
}
