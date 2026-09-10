<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Mapper;

use app\modules\contract_field\models\Bitrix\Userfield\Dto\BitrixEventDto;
use app\modules\contract_field\Module;

class BitrixEventMapper
{
    public function fromPost(array $post): BitrixEventDto
    {
        $dto = new BitrixEventDto();
        $dto->event = strtoupper((string)($post['event'] ?? ''));
        $dto->auth = is_array($post['auth'] ?? null) ? $post['auth'] : [];

        $fields = $post['data']['FIELDS'] ?? [];
        if (!is_array($fields)) {
            $fields = [];
        }

        $dto->itemId = (int)($fields['ID'] ?? 0);

        if (in_array($dto->event, ['ONCRMCOMPANYADD', 'ONCRMCOMPANYUPDATE'], true)) {
            $dto->entityTypeId = Module::COMPANY_ENTITY_TYPE_ID;
        } elseif (in_array($dto->event, ['ONCRMDYNAMICITEMADD', 'ONCRMDYNAMICITEMUPDATE'], true)) {
            $entityTypeId = (int)($fields['ENTITY_TYPE_ID'] ?? 0);
            $dto->entityTypeId = $entityTypeId > 0 ? $entityTypeId : null;
        }

        return $dto;
    }
}
