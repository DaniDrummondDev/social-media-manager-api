<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class EmbeddingGenerationFailedException extends ApplicationException
{
    public function __construct(string $message = 'Embedding generation failed.')
    {
        parent::__construct($message, 'EMBEDDING_GENERATION_FAILED');
    }
}
