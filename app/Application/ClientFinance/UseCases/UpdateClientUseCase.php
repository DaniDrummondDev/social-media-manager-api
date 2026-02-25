<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\ClientOutput;
use App\Application\ClientFinance\DTOs\UpdateClientInput;
use App\Application\ClientFinance\Exceptions\ClientNotFoundException;
use App\Domain\ClientFinance\Repositories\ClientRepositoryInterface;
use App\Domain\ClientFinance\ValueObjects\Address;
use App\Domain\ClientFinance\ValueObjects\ClientStatus;
use App\Domain\ClientFinance\ValueObjects\TaxId;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdateClientUseCase
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    public function execute(UpdateClientInput $input): ClientOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $clientId = Uuid::fromString($input->clientId);

        $client = $this->clientRepository->findByIdAndOrganization($clientId, $organizationId);

        if ($client === null) {
            throw new ClientNotFoundException();
        }

        $taxId = $input->taxId !== null
            ? TaxId::fromString($input->taxId)
            : null;

        $billingAddress = $input->billingAddress !== null
            ? Address::fromArray($input->billingAddress)
            : null;

        $status = $input->status !== null
            ? ClientStatus::from($input->status)
            : null;

        $client = $client->update(
            userId: $input->userId,
            name: $input->name,
            email: $input->email,
            phone: $input->phone,
            companyName: $input->companyName,
            taxId: $taxId,
            billingAddress: $billingAddress,
            notes: $input->notes,
            status: $status,
        );

        $this->clientRepository->update($client);

        return ClientOutput::fromEntity($client);
    }
}
