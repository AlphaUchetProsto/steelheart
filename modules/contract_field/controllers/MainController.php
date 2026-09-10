<?php

namespace app\modules\contract_field\controllers;

use app\models\logger\DebugLogger;
use app\modules\contract_field\models\Bitrix\Userfield\Mapper\ContractMapper;
use app\modules\contract_field\models\Bitrix\Userfield\Mapper\PlacementOptionsMapper;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\ContractProvider;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\CrmItemProvider;
use app\modules\contract_field\models\Bitrix\Userfield\Service\ContractFieldService;
use app\modules\contract_field\Module;
use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class MainController extends Controller
{
    public $layout = 'main';

    /** @var Module */
    public $module;

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    public function runAction($id, $params = [])
    {
        if ($id !== 'install') {
            $token = $this->module->params['token'] ?? null;
            $requestToken = Yii::$app->request->get('token');

            if (empty($requestToken) || $token !== $requestToken) {
                throw new ForbiddenHttpException('Access denied');
            }
        }

        return parent::runAction($id, $params);
    }

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

        $client = $this->module->client;
        $crmItemProvider = new CrmItemProvider($client);
        $contractMapper = new ContractMapper();
        $contractProvider = new ContractProvider($contractMapper, $crmItemProvider);
        $service = new ContractFieldService(
            new PlacementOptionsMapper(),
            $contractProvider,
            $crmItemProvider
        );

        $placementOptions = $post['PLACEMENT_OPTIONS'] ?? null;
        if (is_array($placementOptions)) {
            $placementOptions = json_encode($placementOptions, JSON_UNESCAPED_UNICODE);
        }

        $state = $service->buildStateFromPlacementJson($placementOptions);

        return $this->render('field', [
            'state' => $state,
        ]);
    }
}
