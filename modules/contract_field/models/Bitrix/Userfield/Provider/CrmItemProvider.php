<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Provider;

use app\models\BitrixCrm\Client\Client;

class CrmItemProvider
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getItem(int $entityTypeId, int $id): array
    {
        $result = $this->client->api()->request('crm.item.get', [
            'entityTypeId' => $entityTypeId,
            'id' => $id,
        ])->getResponse();

        return $result['item'] ?? [];
    }

    /**
     * @return array[]
     */
    public function listByCompany(int $entityTypeId, int $companyId): array
    {
        $items = [];
        $start = 0;

        do {
            $response = $this->client->api()->request('crm.item.list', [
                'entityTypeId' => $entityTypeId,
                'filter' => [
                    'companyId' => $companyId,
                ],
                'select' => ['id', 'title', 'companyId'],
                'order' => ['id' => 'DESC'],
                'start' => $start,
            ]);

            $pageItems = $response->getResponse()['items'] ?? [];
            $items = array_merge($items, $pageItems);
            $start = $response->getPage();
        } while ($start > 0);

        return $items;
    }

    public function resolveEntityTypeId(string $entityId): ?int
    {
        $systemMap = [
            'CRM_SMART_INVOICE' => 31,
        ];

        if (isset($systemMap[$entityId])) {
            return $systemMap[$entityId];
        }

        if (!preg_match('/^CRM_(\d+)$/', $entityId, $matches)) {
            return null;
        }

        $numericId = (int)$matches[1];

        $types = $this->client->api()->request('crm.type.list', [
            'filter' => ['id' => $numericId],
        ])->getResponse();

        $type = $types['types'][0] ?? null;
        if ($type) {
            return (int)($type['entityTypeId'] ?? 0) ?: null;
        }

        $types = $this->client->api()->request('crm.type.list', [
            'filter' => ['entityTypeId' => $numericId],
        ])->getResponse();

        $type = $types['types'][0] ?? null;
        if ($type) {
            return (int)($type['entityTypeId'] ?? $numericId);
        }

        return $numericId;
    }
}
