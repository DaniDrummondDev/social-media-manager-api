<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Services;

use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Domain\SocialAccount\Contracts\SocialAuthenticatorInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\SocialAccount\Adapters\InstagramAuthenticator;
use App\Infrastructure\SocialAccount\Adapters\TikTokAuthenticator;
use App\Infrastructure\SocialAccount\Adapters\YouTubeAuthenticator;

final class SocialAdapterFactory implements SocialAccountAdapterFactoryInterface
{
    public function __construct(
        private readonly SocialTokenEncrypter $encrypter,
    ) {}

    public function make(SocialProvider $provider): SocialAuthenticatorInterface
    {
        /** @var array<string, mixed> $config */
        $config = config("social-media.{$provider->value}", []);

        return match ($provider) {
            SocialProvider::Instagram => new InstagramAuthenticator($this->encrypter, $config),
            SocialProvider::TikTok => new TikTokAuthenticator($this->encrypter, $config),
            SocialProvider::YouTube => new YouTubeAuthenticator($this->encrypter, $config),
        };
    }
}
