<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Mapper;

use app\modules\contract_field\models\Bitrix\Userfield\Dto\ContractDto;

class ContractMapper
{
    public function fromArray(array $data): ContractDto
    {
        $dto = new ContractDto();
        $dto->id = (int)($data['id'] ?? $data['ID'] ?? 0);
        $dto->title = (string)($data['title'] ?? $data['TITLE'] ?? '');

        return $dto;
    }

    /**
     * @param array $items
     * @return ContractDto[]
     */
    public function fromList(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $dto = $this->fromArray($item);
            if ($dto->id > 0) {
                $result[] = $dto;
            }
        }

        return $result;
    }
}
