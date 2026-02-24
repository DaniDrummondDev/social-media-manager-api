<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\ValueObjects;

enum Language: string
{
    case PtBR = 'pt_BR';
    case EnUS = 'en_US';
    case EsES = 'es_ES';

    public function label(): string
    {
        return match ($this) {
            self::PtBR => 'Português (Brasil)',
            self::EnUS => 'English (US)',
            self::EsES => 'Español (España)',
        };
    }
}
