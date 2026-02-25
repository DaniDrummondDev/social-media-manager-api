<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\AdminUserDetailOutput;
use App\Application\PlatformAdmin\Exceptions\AdminUserNotFoundException;

final class GetUserDetailUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
    ) {}

    public function execute(string $userId): AdminUserDetailOutput
    {
        $data = $this->queryService->getUserDetail($userId);

        if ($data === null) {
            throw new AdminUserNotFoundException;
        }

        return AdminUserDetailOutput::fromArray($data);
    }
}
