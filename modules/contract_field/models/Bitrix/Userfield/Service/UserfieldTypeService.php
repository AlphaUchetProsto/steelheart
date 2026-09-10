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
        return $this->provider->add(
            Module::USER_TYPE_ID,
            $handler,
            'Договор',
            'Выбор договора, привязанного к компании',
            90
        );
    }
}
