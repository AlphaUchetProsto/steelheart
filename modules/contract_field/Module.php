<?php

namespace app\modules\contract_field;

use app\models\BitrixCrm\Client\Client;
use app\models\BitrixCrm\Client\Config;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\UserfieldTypeProvider;
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

        $service = new UserfieldTypeService(new UserfieldTypeProvider($this->client));

        $token = $this->params['token'];
        $handler = \Yii::$app->urlManager->createAbsoluteUrl([
            '/contract-field/main/field',
            'token' => $token,
        ], 'https');

        $service->register($handler);
    }

    public function getAppConfigPath(): string
    {
        $folder = u($this->id)->replace('-', '_')->toString();

        return \Yii::getAlias('@app') . '/modules/' . $folder . '/config/app_config.php';
    }
}
