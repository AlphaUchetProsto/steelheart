<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Dto;

class ContractFieldStateDto
{
    public PlacementOptionsDto $placement;
    public ?int $companyId = null;
    public ?int $entityTypeId = null;
    /** Код штатного поля привязки в формате crm.item (для dual-write из UI) */
    public string $nativeFieldName = '';
    /** Код кастомного поля в формате crm.item */
    public string $itemFieldName = '';
    /** @var ContractDto[] */
    public array $contracts = [];
    public ?ContractDto $selectedContract = null;
    public string $error = '';
}
