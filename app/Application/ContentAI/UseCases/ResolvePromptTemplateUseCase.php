<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\Contracts\PromptTemplateResolverInterface;
use App\Application\ContentAI\DTOs\ResolvedPromptOutput;
use App\Application\ContentAI\DTOs\ResolvePromptTemplateInput;

final class ResolvePromptTemplateUseCase
{
    public function __construct(
        private readonly PromptTemplateResolverInterface $resolver,
    ) {}

    public function execute(ResolvePromptTemplateInput $input): ResolvedPromptOutput
    {
        $result = $this->resolver->resolve(
            organizationId: $input->organizationId,
            generationType: $input->generationType,
        );

        return ResolvedPromptOutput::fromResult($result);
    }
}
