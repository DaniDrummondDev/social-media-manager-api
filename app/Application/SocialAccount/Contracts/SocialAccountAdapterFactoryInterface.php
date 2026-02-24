<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\Contracts;

use App\Domain\SocialAccount\Contracts\SocialAuthenticatorInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

interface SocialAccountAdapterFactoryInterface
{
    public function make(SocialProvider $provider): SocialAuthenticatorInterface;
}
