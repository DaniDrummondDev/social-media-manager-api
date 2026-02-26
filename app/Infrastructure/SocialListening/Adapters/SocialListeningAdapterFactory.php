<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Adapters;

use App\Application\SocialListening\Contracts\SocialListeningAdapterInterface;
use InvalidArgumentException;

final class SocialListeningAdapterFactory
{
    public function make(string $platform): SocialListeningAdapterInterface
    {
        return match ($platform) {
            'instagram' => new InstagramListeningAdapter,
            'tiktok' => new TikTokListeningAdapter,
            'youtube' => new YouTubeListeningAdapter,
            default => throw new InvalidArgumentException("Plataforma não suportada: {$platform}"),
        };
    }
}
