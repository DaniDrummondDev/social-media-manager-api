<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\DTOs\CreatePromptTemplateInput;
use App\Application\ContentAI\DTOs\PromptTemplateOutput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\PromptTemplateRepositoryInterface;
use App\Domain\ContentAI\Entities\PromptTemplate;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreatePromptTemplateUseCase
{
    public function __construct(
        private readonly PromptTemplateRepositoryInterface $templateRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreatePromptTemplateInput $input): PromptTemplateOutput
    {
        $organizationId = $input->organizationId !== null
            ? Uuid::fromString($input->organizationId)
            : null;

        $template = PromptTemplate::create(
            organizationId: $organizationId,
            generationType: $input->generationType,
            version: $input->version,
            name: $input->name,
            systemPrompt: $input->systemPrompt,
            userPromptTemplate: $input->userPromptTemplate,
            variables: $input->variables,
            isDefault: $input->isDefault,
            createdBy: Uuid::fromString($input->userId),
        );

        $this->templateRepository->create($template);
        $this->eventDispatcher->dispatch(...$template->domainEvents);

        return PromptTemplateOutput::fromEntity($template);
    }
}
