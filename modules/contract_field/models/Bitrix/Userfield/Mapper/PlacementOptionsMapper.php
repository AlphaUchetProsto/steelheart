<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Mapper;

use app\modules\contract_field\models\Bitrix\Userfield\Dto\PlacementOptionsDto;

class PlacementOptionsMapper
{
    public function fromArray(array $data): PlacementOptionsDto
    {
        $dto = new PlacementOptionsDto();
        $dto->mode = (string)($data['MODE'] ?? 'view');
        $dto->entityId = (string)($data['ENTITY_ID'] ?? '');
        $dto->fieldName = (string)($data['FIELD_NAME'] ?? '');
        $dto->entityValueId = (int)($data['ENTITY_VALUE_ID'] ?? 0);
        $dto->value = $data['VALUE'] ?? null;
        $dto->multiple = ($data['MULTIPLE'] ?? 'N') === 'Y';
        $dto->mandatory = ($data['MANDATORY'] ?? 'N') === 'Y';
        $dto->xmlId = isset($data['XML_ID']) ? (string)$data['XML_ID'] : null;

        return $dto;
    }

    public function fromJson(?string $json): PlacementOptionsDto
    {
        $data = [];

        if (!empty($json)) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        return $this->fromArray($data);
    }
}
