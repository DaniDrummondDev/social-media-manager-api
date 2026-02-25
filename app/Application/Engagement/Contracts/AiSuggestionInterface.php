<?php

declare(strict_types=1);

namespace App\Application\Engagement\Contracts;

interface AiSuggestionInterface
{
    /**
     * @return array<string>
     */
    public function suggestReply(string $commentText, string $contentTitle): array;
}
