<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Provider;

use app\models\BitrixCrm\Client\Client;

class EventProvider
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function bind(string $event, string $handler): array
    {
        return $this->client->api()->request('event.bind', [
            'event' => $event,
            'handler' => $handler,
        ])->getFullResponse();
    }

    public function unbind(string $event, string $handler): array
    {
        return $this->client->api()->request('event.unbind', [
            'event' => $event,
            'handler' => $handler,
        ])->getFullResponse();
    }
}
