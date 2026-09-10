<?php

namespace app\modules\contract_field\controllers;

use app\models\logger\DebugLogger;
use app\modules\contract_field\models\Bitrix\Userfield\Mapper\BitrixEventMapper;
use app\modules\contract_field\models\Bitrix\Userfield\Mapper\ContractMapper;
use app\modules\contract_field\models\Bitrix\Userfield\Mapper\PlacementOptionsMapper;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\ContractProvider;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\CrmItemProvider;
use app\modules\contract_field\models\Bitrix\Userfield\Service\ContractFieldService;
use app\modules\contract_field\models\Bitrix\Userfield\Service\EventHandlerService;
use Yii;
use yii\web\Response;

class MainController extends BaseController
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionInstall()
    {
        $post = Yii::$app->request->post();
        $logger = DebugLogger::instance('contract-field-install');
        $logger->save($post, $post, 'Данные приложения');

        if (Yii::$app->request->isPost) {
            $this->module->install($post);
        }

        return $this->render('install');
    }

    public function actionField()
    {
        $post = Yii::$app->request->post();
        $logger = DebugLogger::instance('contract-field-field');
        $logger->save($post, $post, 'PLACEMENT POST');

        $this->module->appConfig->load($post);

        $crmItemProvider = new CrmItemProvider($this->module->client);
        $service = new ContractFieldService(
            new PlacementOptionsMapper(),
            new ContractProvider(new ContractMapper(), $crmItemProvider),
            $crmItemProvider
        );

        $placementOptions = $post['PLACEMENT_OPTIONS'] ?? null;
        if (is_array($placementOptions)) {
            $placementOptions = json_encode($placementOptions, JSON_UNESCAPED_UNICODE);
        }

        return $this->render('field', [
            'state' => $service->buildStateFromPlacementJson($placementOptions),
        ]);
    }

    public function actionEvent()
    {
        Yii::$app->response->format = Response::FORMAT_RAW;

        $service = new EventHandlerService(new BitrixEventMapper(), $this->module);
        $service->handle(Yii::$app->request->post());

        return 'ok';
    }
}
