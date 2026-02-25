<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\ClientOutput;
use App\Application\ClientFinance\DTOs\CreateClientInput;
use App\Domain\ClientFinance\Entities\Client;
use App\Domain\ClientFinance\Repositories\ClientRepositoryInterface;
use App\Domain\ClientFinance\ValueObjects\Address;
use App\Domain\ClientFinance\ValueObjects\TaxId;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreateClientUseCase
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    public function execute(CreateClientInput $input): ClientOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $taxId = $input->taxId !== null
            ? TaxId::fromString($input->taxId)
            : null;

        $billingAddress = $input->billingAddress !== null
            ? Address::fromArray($input->billingAddress)
            : null;

        $client = Client::create(
            organizationId: $organizationId,
            name: $input->name,
            userId: $input->userId,
            email: $input->email,
            phone: $input->phone,
            companyName: $input->companyName,
            taxId: $taxId,
            billingAddress: $billingAddress,
            notes: $input->notes,
        );

        $this->clientRepository->create($client);

        return ClientOutput::fromEntity($client);
    }
}
