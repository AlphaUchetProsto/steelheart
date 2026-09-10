<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Provider;

use app\models\BitrixCrm\Client\Client;
use app\modules\contract_field\models\Bitrix\Userfield\Mapper\ContractMapper;
use app\modules\contract_field\models\Bitrix\Userfield\Dto\ContractDto;
use app\modules\contract_field\Module;

class ContractProvider
{
    private ContractMapper $mapper;
    private CrmItemProvider $crmItemProvider;

    public function __construct(ContractMapper $mapper, CrmItemProvider $crmItemProvider)
    {
        $this->mapper = $mapper;
        $this->crmItemProvider = $crmItemProvider;
    }

    /**
     * @return ContractDto[]
     */
    public function listByCompanyId(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $items = $this->crmItemProvider->listByCompany(Module::CONTRACT_ENTITY_TYPE_ID, $companyId);

        return $this->mapper->fromList($items);
    }

    public function getById(int $id): ?ContractDto
    {
        if ($id <= 0) {
            return null;
        }

        $item = $this->crmItemProvider->getItem(Module::CONTRACT_ENTITY_TYPE_ID, $id);
        if (empty($item)) {
            return null;
        }

        return $this->mapper->fromArray($item);
    }
}
