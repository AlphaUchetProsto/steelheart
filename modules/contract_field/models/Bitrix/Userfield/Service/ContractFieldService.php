<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Service;

use app\modules\contract_field\models\Bitrix\Userfield\Dto\ContractFieldStateDto;
use app\modules\contract_field\models\Bitrix\Userfield\Dto\PlacementOptionsDto;
use app\modules\contract_field\models\Bitrix\Userfield\Mapper\PlacementOptionsMapper;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\ContractProvider;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\CrmItemProvider;
use app\modules\contract_field\Module;

class ContractFieldService
{
    private PlacementOptionsMapper $placementOptionsMapper;
    private ContractProvider $contractProvider;
    private CrmItemProvider $crmItemProvider;
    private FieldSyncService $fieldSyncService;

    public function __construct(
        PlacementOptionsMapper $placementOptionsMapper,
        ContractProvider $contractProvider,
        CrmItemProvider $crmItemProvider,
        FieldSyncService $fieldSyncService
    ) {
        $this->placementOptionsMapper = $placementOptionsMapper;
        $this->contractProvider = $contractProvider;
        $this->crmItemProvider = $crmItemProvider;
        $this->fieldSyncService = $fieldSyncService;
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
            if ($placement->entityId === 'CRM_COMPANY') {
                $state->entityTypeId = Module::COMPANY_ENTITY_TYPE_ID;
            } else {
                $state->entityTypeId = $this->crmItemProvider->resolveEntityTypeId($placement->entityId);
            }

            if ($state->entityTypeId) {
                $state->itemFieldName = $this->fieldSyncService->toCrmItemFieldName($placement->fieldName);

                $pair = Module::getFieldPair($state->entityTypeId);
                if ($pair && $pair->native !== '') {
                    $state->nativeFieldName = $this->fieldSyncService->toCrmItemFieldName($pair->native);
                }

                // 1С может не слать CRM-событие — выравниваем при открытии поля
                if ($placement->entityValueId > 0) {
                    $this->fieldSyncService->syncFromNative($state->entityTypeId, $placement->entityValueId);
                }
            }

            $companyId = $this->resolveCompanyId($placement);
            $state->companyId = $companyId;

            if (!$companyId) {
                $state->error = 'Не удалось определить компанию для выбора договора';
                return $state;
            }

            $state->contracts = $this->contractProvider->listByCompanyId($companyId);

            $selectedId = null;
            if ($state->entityTypeId && $placement->entityValueId > 0) {
                $selectedId = $this->fieldSyncService->resolveNativeContractId(
                    $state->entityTypeId,
                    $placement->entityValueId
                );
            }

            if ($selectedId === null) {
                $selectedId = $this->fieldSyncService->normalizeContractId($placement->value);
            }

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
}
