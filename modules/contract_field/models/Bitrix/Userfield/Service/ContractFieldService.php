<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Service;

use app\modules\contract_field\models\Bitrix\Userfield\Dto\ContractFieldStateDto;
use app\modules\contract_field\models\Bitrix\Userfield\Dto\PlacementOptionsDto;
use app\modules\contract_field\models\Bitrix\Userfield\Mapper\PlacementOptionsMapper;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\ContractProvider;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\CrmItemProvider;

class ContractFieldService
{
    private PlacementOptionsMapper $placementOptionsMapper;
    private ContractProvider $contractProvider;
    private CrmItemProvider $crmItemProvider;

    public function __construct(
        PlacementOptionsMapper $placementOptionsMapper,
        ContractProvider $contractProvider,
        CrmItemProvider $crmItemProvider
    ) {
        $this->placementOptionsMapper = $placementOptionsMapper;
        $this->contractProvider = $contractProvider;
        $this->crmItemProvider = $crmItemProvider;
    }

    public function buildStateFromPlacementJson(?string $placementOptionsJson): ContractFieldStateDto
    {
        $placement = $this->placementOptionsMapper->fromJson($placementOptionsJson);

        return $this->buildState($placement);
    }

    public function buildState(PlacementOptionsDto $placement): ContractFieldStateDto
    {
        $state = new ContractFieldStateDto();
        $state->placement = $placement;

        try {
            $companyId = $this->resolveCompanyId($placement);
            $state->companyId = $companyId;

            if (!$companyId) {
                $state->error = 'Не удалось определить компанию для выбора договора';
                return $state;
            }

            $state->contracts = $this->contractProvider->listByCompanyId($companyId);

            $selectedId = $this->extractSelectedId($placement->value);
            if ($selectedId) {
                foreach ($state->contracts as $contract) {
                    if ($contract->id === $selectedId) {
                        $state->selectedContract = $contract;
                        break;
                    }
                }

                if (!$state->selectedContract) {
                    $state->selectedContract = $this->contractProvider->getById($selectedId);
                }
            }
        } catch (\Throwable $e) {
            $state->error = $e->getMessage();
        }

        return $state;
    }

    private function resolveCompanyId(PlacementOptionsDto $placement): ?int
    {
        if ($placement->entityValueId <= 0) {
            return null;
        }

        if ($placement->entityId === 'CRM_COMPANY') {
            return $placement->entityValueId;
        }

        $entityTypeId = $this->crmItemProvider->resolveEntityTypeId($placement->entityId);
        if (!$entityTypeId) {
            return null;
        }

        $item = $this->crmItemProvider->getItem($entityTypeId, $placement->entityValueId);
        $companyId = (int)($item['companyId'] ?? 0);

        return $companyId > 0 ? $companyId : null;
    }

    private function extractSelectedId($value): ?int
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        $id = (int)$value;

        return $id > 0 ? $id : null;
    }
}
