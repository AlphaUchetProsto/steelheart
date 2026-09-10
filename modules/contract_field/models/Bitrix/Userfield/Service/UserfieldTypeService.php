<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Service;

use app\modules\contract_field\models\Bitrix\Userfield\Provider\UserfieldTypeProvider;
use app\modules\contract_field\Module;

class UserfieldTypeService
{
    private UserfieldTypeProvider $provider;

    public function __construct(UserfieldTypeProvider $provider)
    {
        $this->provider = $provider;
    }

    public function register(string $handler): array
    {
        $title = 'Договор';
        $description = 'Выбор договора, привязанного к компании';
        $height = 36;

        if ($this->isRegistered(Module::USER_TYPE_ID)) {
            return $this->provider->update(
                Module::USER_TYPE_ID,
                $handler,
                $title,
                $description,
                $height
            );
        }

        return $this->provider->add(
            Module::USER_TYPE_ID,
            $handler,
            $title,
            $description,
            $height
        );
    }

    private function isRegistered(string $userTypeId): bool
    {
        $list = $this->provider->list();

        if (!is_array($list)) {
            return false;
        }

        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (($item['USER_TYPE_ID'] ?? null) === $userTypeId) {
                return true;
            }
        }

        return false;
    }
}
