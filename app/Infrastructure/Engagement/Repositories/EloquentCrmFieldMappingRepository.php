<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Repositories;

use App\Domain\Engagement\Repositories\CrmFieldMappingRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmFieldMapping;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Models\CrmFieldMappingModel;
use Illuminate\Support\Facades\DB;

final class EloquentCrmFieldMappingRepository implements CrmFieldMappingRepositoryInterface
{
    private const array DEFAULT_MAPPINGS = [
        'hubspot' => [
            ['smm_field' => 'name', 'crm_field' => 'firstname', 'transform' => null, 'position' => 0],
            ['smm_field' => 'external_id', 'crm_field' => 'hs_additional_id', 'transform' => null, 'position' => 1],
            ['smm_field' => 'email', 'crm_field' => 'email', 'transform' => 'lowercase', 'position' => 2],
            ['smm_field' => 'network', 'crm_field' => 'hs_content_source', 'transform' => null, 'position' => 3],
            ['smm_field' => 'sentiment', 'crm_field' => 'hs_sentiment', 'transform' => null, 'position' => 4],
        ],
        'rdstation' => [
            ['smm_field' => 'name', 'crm_field' => 'name', 'transform' => null, 'position' => 0],
            ['smm_field' => 'external_id', 'crm_field' => 'cf_social_id', 'transform' => null, 'position' => 1],
            ['smm_field' => 'email', 'crm_field' => 'email', 'transform' => 'lowercase', 'position' => 2],
            ['smm_field' => 'network', 'crm_field' => 'cf_social_network', 'transform' => null, 'position' => 3],
            ['smm_field' => 'sentiment', 'crm_field' => 'cf_sentiment', 'transform' => null, 'position' => 4],
        ],
        'pipedrive' => [
            ['smm_field' => 'name', 'crm_field' => 'name', 'transform' => null, 'position' => 0],
            ['smm_field' => 'external_id', 'crm_field' => 'social_id', 'transform' => null, 'position' => 1],
            ['smm_field' => 'email', 'crm_field' => 'email', 'transform' => 'lowercase', 'position' => 2],
            ['smm_field' => 'network', 'crm_field' => 'social_network', 'transform' => null, 'position' => 3],
            ['smm_field' => 'sentiment', 'crm_field' => 'sentiment', 'transform' => null, 'position' => 4],
        ],
        'salesforce' => [
            ['smm_field' => 'name', 'crm_field' => 'FirstName', 'transform' => null, 'position' => 0],
            ['smm_field' => 'external_id', 'crm_field' => 'Social_Media_Id__c', 'transform' => null, 'position' => 1],
            ['smm_field' => 'email', 'crm_field' => 'Email', 'transform' => 'lowercase', 'position' => 2],
            ['smm_field' => 'network', 'crm_field' => 'Social_Network__c', 'transform' => null, 'position' => 3],
            ['smm_field' => 'sentiment', 'crm_field' => 'Sentiment__c', 'transform' => null, 'position' => 4],
        ],
        'activecampaign' => [
            ['smm_field' => 'name', 'crm_field' => 'firstName', 'transform' => null, 'position' => 0],
            ['smm_field' => 'external_id', 'crm_field' => 'fieldValues.social_id', 'transform' => null, 'position' => 1],
            ['smm_field' => 'email', 'crm_field' => 'email', 'transform' => 'lowercase', 'position' => 2],
            ['smm_field' => 'network', 'crm_field' => 'fieldValues.social_network', 'transform' => null, 'position' => 3],
            ['smm_field' => 'sentiment', 'crm_field' => 'fieldValues.sentiment', 'transform' => null, 'position' => 4],
        ],
    ];

    public function __construct(
        private readonly CrmFieldMappingModel $model,
    ) {}

    /**
     * @return array<CrmFieldMapping>
     */
    public function findByConnectionId(Uuid $connectionId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, CrmFieldMappingModel> $records */
        $records = $this->model->newQuery()
            ->where('crm_connection_id', (string) $connectionId)
            ->orderBy('position')
            ->get();

        return $records->map(fn (CrmFieldMappingModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @param  array<CrmFieldMapping>  $mappings
     */
    public function saveForConnection(Uuid $connectionId, array $mappings): void
    {
        DB::transaction(function () use ($connectionId, $mappings): void {
            $this->model->newQuery()
                ->where('crm_connection_id', (string) $connectionId)
                ->delete();

            foreach ($mappings as $mapping) {
                $this->model->newQuery()->create([
                    'id' => (string) Uuid::generate(),
                    'crm_connection_id' => (string) $connectionId,
                    'smm_field' => $mapping->smmField,
                    'crm_field' => $mapping->crmField,
                    'transform' => $mapping->transform,
                    'position' => $mapping->position,
                ]);
            }
        });
    }

    public function resetToDefault(Uuid $connectionId, CrmProvider $provider): void
    {
        $defaults = $this->findDefaultByProvider($provider);
        $this->saveForConnection($connectionId, $defaults);
    }

    /**
     * @return array<CrmFieldMapping>
     */
    public function findDefaultByProvider(CrmProvider $provider): array
    {
        $defaults = self::DEFAULT_MAPPINGS[$provider->value] ?? [];

        return array_map(
            fn (array $d) => CrmFieldMapping::create(
                smmField: $d['smm_field'],
                crmField: $d['crm_field'],
                transform: $d['transform'],
                position: $d['position'],
            ),
            $defaults,
        );
    }

    private function toDomain(CrmFieldMappingModel $model): CrmFieldMapping
    {
        return CrmFieldMapping::create(
            smmField: $model->getAttribute('smm_field'),
            crmField: $model->getAttribute('crm_field'),
            transform: $model->getAttribute('transform'),
            position: (int) $model->getAttribute('position'),
        );
    }
}
