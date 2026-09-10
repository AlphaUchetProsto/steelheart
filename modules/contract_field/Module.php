<?php

namespace app\modules\contract_field;

use app\models\BitrixCrm\Client\Client;
use app\models\BitrixCrm\Client\Config;
use app\modules\contract_field\models\Bitrix\Userfield\Dto\FieldPairDto;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\EventProvider;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\UserfieldTypeProvider;
use app\modules\contract_field\models\Bitrix\Userfield\Service\EventBindService;
use app\modules\contract_field\models\Bitrix\Userfield\Service\UserfieldTypeService;

use function Symfony\Component\String\u;

/**
 * @property Config $appConfig
 * @property Client $client
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\contract_field\controllers';

    /** entityTypeId смарт-процесса «Договоры» */
    public const CONTRACT_ENTITY_TYPE_ID = 1038;

    /** Компания */
    public const COMPANY_ENTITY_TYPE_ID = 4;

    /** Новые счета */
    public const SMART_INVOICE_ENTITY_TYPE_ID = 31;

    /** УПД */
    public const UPD_ENTITY_TYPE_ID = 1042;

    public const USER_TYPE_ID = 'contract';

    public function init()
    {
        parent::init();

        $config = Config::getInstanceByPath($this->getAppConfigPath(), true);

        $this->setComponents([
            'appConfig' => $config,
            'client' => new Client($config),
        ]);

        $this->params = [
            'token' => 'c7e2f1a0-9b4d-4e8a-a1c3-5d6f7e8a9b0c',
        ];
    }

    public function install(array $data): void
    {
        $auth = $data['auth'] ?? $data;

        $config = Config::getInstanceByPath($this->getAppConfigPath(), true);
        $config->load(is_array($auth) ? $auth : []);
        $config->save();

        $this->setComponents([
            'appConfig' => $config,
            'client' => new Client($config),
        ]);

        $token = $this->params['token'];

        $fieldHandler = \Yii::$app->urlManager->createAbsoluteUrl([
            '/contract-field/main/field',
            'token' => $token,
        ], 'https');

        $eventHandler = \Yii::$app->urlManager->createAbsoluteUrl([
            '/contract-field/main/event',
            'token' => $token,
        ], 'https');

        $userfieldTypeService = new UserfieldTypeService(new UserfieldTypeProvider($this->client));
        $userfieldTypeService->register($fieldHandler);

        $eventBindService = new EventBindService(new EventProvider($this->client));
        $eventBindService->register($eventHandler);
    }

    /**
     * @return array<int, array{native: string, custom: string}>
     */
    public static function getFieldMap(): array
    {
        $path = \Yii::getAlias('@app') . '/modules/contract_field/config/field_map.php';
        if (!is_file($path)) {
            return [];
        }

        $map = require $path;

        return is_array($map) ? $map : [];
    }

    public static function getFieldPair(int $entityTypeId): ?FieldPairDto
    {
        $map = self::getFieldMap();
        if (!isset($map[$entityTypeId]) || !is_array($map[$entityTypeId])) {
            return null;
        }

        $row = $map[$entityTypeId];
        $dto = new FieldPairDto();
        $dto->entityTypeId = $entityTypeId;
        $dto->native = trim((string)($row['native'] ?? ''));
        $dto->custom = trim((string)($row['custom'] ?? ''));

        return $dto;
    }

    public function getAppConfigPath(): string
    {
        $folder = u($this->id)->replace('-', '_')->toString();

        return \Yii::getAlias('@app') . '/modules/' . $folder . '/config/app_config.php';
    }
}
