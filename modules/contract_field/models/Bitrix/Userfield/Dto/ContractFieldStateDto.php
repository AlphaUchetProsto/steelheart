<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Dto;

class ContractFieldStateDto
{
    public PlacementOptionsDto $placement;
    public ?int $companyId = null;
    public ?int $entityTypeId = null;
    /** @var ContractDto[] */
    public array $contracts = [];
    public ?ContractDto $selectedContract = null;
    public string $error = '';
}
