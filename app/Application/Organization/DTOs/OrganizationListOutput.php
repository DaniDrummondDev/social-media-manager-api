<?php

declare(strict_types=1);

namespace App\Application\Organization\DTOs;

final readonly class OrganizationListOutput
{
    /**
     * @param  OrganizationOutput[]  $organizations
     */
    public function __construct(
        public array $organizations,
    ) {}
}
