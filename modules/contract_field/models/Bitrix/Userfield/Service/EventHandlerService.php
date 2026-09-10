<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Service;

use app\models\BitrixCrm\Client\Client;
use app\models\logger\DebugLogger;
use app\modules\contract_field\models\Bitrix\Userfield\Dto\BitrixEventDto;
use app\modules\contract_field\models\Bitrix\Userfield\Mapper\BitrixEventMapper;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\CrmItemProvider;
use app\modules\contract_field\Module;

class EventHandlerService
{
    private BitrixEventMapper $mapper;
    private Module $module;

    public function __construct(BitrixEventMapper $mapper, Module $module)
    {
        $this->mapper = $mapper;
        $this->module = $module;
    }

    public function handle(array $post): void
    {
        $logger = DebugLogger::instance('contract-field-event');
        $logger->save($post, $post, 'EVENT POST');

        $event = $this->mapper->fromPost($post);
        $this->applyAuth($event);

        if ($event->itemId <= 0 || !$event->entityTypeId) {
            return;
        }

        $pair = Module::getFieldPair($event->entityTypeId);
        if (!$pair || $pair->native === '' || $pair->custom === '') {
            return;
        }

        $fieldSyncService = new FieldSyncService(new CrmItemProvider($this->module->client));

        try {
            $updated = $fieldSyncService->syncFromNative($event->entityTypeId, $event->itemId);
            $logger->save(
                [
                    'entityTypeId' => $event->entityTypeId,
                    'itemId' => $event->itemId,
                    'updated' => $updated,
                ],
                $post,
                'FIELD SYNC'
            );
        } catch (\Throwable $e) {
            $logger->save($e->getMessage(), $post, 'FIELD SYNC ERROR');
        }
    }

    private function applyAuth(BitrixEventDto $event): void
    {
        if (!$event->auth) {
            return;
        }

        $this->module->appConfig->load($event->auth);
        $this->module->setComponents([
            'client' => new Client($this->module->appConfig),
        ]);
    }
}
