<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Dto;

class BitrixEventDto
{
    public string $event = '';
    public int $itemId = 0;
    public ?int $entityTypeId = null;
    public array $auth = [];
}
