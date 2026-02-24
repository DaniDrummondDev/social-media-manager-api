<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\Contracts;

interface SocialPublisherInterface
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function publish(array $params): array;

    /**
     * @return array<string, mixed>
     */
    public function getPostStatus(string $externalPostId): array;

    public function deletePost(string $externalPostId): void;
}
