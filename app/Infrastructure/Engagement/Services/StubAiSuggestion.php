<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Services;

use App\Application\Engagement\Contracts\AiSuggestionInterface;

final class StubAiSuggestion implements AiSuggestionInterface
{
    /**
     * @return array<string>
     */
    public function suggestReply(string $commentText, string $contentTitle): array
    {
        return [
            'Obrigado pelo seu comentário! 😊',
            'Agradecemos o feedback! Que bom que gostou.',
            'Ficamos felizes com seu comentário! Continue acompanhando.',
        ];
    }
}
