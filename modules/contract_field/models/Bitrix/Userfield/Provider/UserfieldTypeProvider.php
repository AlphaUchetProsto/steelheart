<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Provider;

use app\models\BitrixCrm\Client\Client;

class UserfieldTypeProvider
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function add(string $userTypeId, string $handler, string $title, string $description = '', int $height = 80): array
    {
        return $this->client->api()->request('userfieldtype.add', [
            'USER_TYPE_ID' => $userTypeId,
            'HANDLER' => $handler,
            'TITLE' => $title,
            'DESCRIPTION' => $description,
            'OPTIONS' => [
                'height' => $height,
            ],
        ])->getFullResponse();
    }

    public function update(string $userTypeId, string $handler, string $title, string $description = '', int $height = 80): array
    {
        return $this->client->api()->request('userfieldtype.update', [
            'USER_TYPE_ID' => $userTypeId,
            'HANDLER' => $handler,
            'TITLE' => $title,
            'DESCRIPTION' => $description,
            'OPTIONS' => [
                'height' => $height,
            ],
        ])->getFullResponse();
    }

    public function list(): array
    {
        return $this->client->api()->request('userfieldtype.list')->getResponse();
    }
}
