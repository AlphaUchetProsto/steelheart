<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Service;

use app\modules\contract_field\models\Bitrix\Userfield\Provider\EventProvider;

class EventBindService
{
    private const EVENTS = [
        'ONCRMDYNAMICITEMADD',
        'ONCRMDYNAMICITEMUPDATE',
        'ONCRMCOMPANYADD',
        'ONCRMCOMPANYUPDATE',
    ];

    private EventProvider $provider;

    public function __construct(EventProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return array<string, array>
     */
    public function register(string $handler): array
    {
        $results = [];

        foreach (self::EVENTS as $event) {
            try {
                $results[$event] = $this->provider->bind($event, $handler);
            } catch (\Throwable $e) {
                $results[$event] = [
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
