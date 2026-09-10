<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Dto;

class PlacementOptionsDto
{
    public string $mode = 'view';
    public string $entityId = '';
    public string $fieldName = '';
    public int $entityValueId = 0;
    public $value = null;
    public bool $multiple = false;
    public bool $mandatory = false;
    public ?string $xmlId = null;
}
