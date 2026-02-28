<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Contracts;

interface LangGraphClientInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{result: array<string, mixed>, metadata: array<string, mixed>}
     */
    public function dispatch(string $pipeline, array $payload): array;
}
