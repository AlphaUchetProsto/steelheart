<?php

namespace app\modules\contract_field\controllers;

use app\models\logger\DebugLogger;
use app\modules\contract_field\models\Bitrix\Userfield\Mapper\ContractMapper;
use app\modules\contract_field\models\Bitrix\Userfield\Mapper\PlacementOptionsMapper;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\ContractProvider;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\CrmItemProvider;
use app\modules\contract_field\models\Bitrix\Userfield\Service\ContractFieldService;
use app\modules\contract_field\models\Bitrix\Userfield\Service\FieldSyncService;
use app\modules\contract_field\Module;
use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

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
        if (!in_array($id, ['install', 'event'], true)) {
            $token = $this->module->params['token'] ?? null;
            $requestToken = Yii::$app->request->get('token');

            if (empty($requestToken) || $token !== $requestToken) {
                throw new ForbiddenHttpException('Access denied');
            }
        }

        return parent::runAction($id, $params);
    }

    /**
     * Обработчик ONCRMDYNAMICITEM* / ONCRMCOMPANY*
     */
    public function actionEvent()
    {
        Yii::$app->response->format = Response::FORMAT_RAW;

        $post = Yii::$app->request->post();
        $logger = DebugLogger::instance('contract-field-event');
        $logger->save($post, $post, 'EVENT POST');

        $token = $this->module->params['token'] ?? null;
        $requestToken = Yii::$app->request->get('token');
        if (empty($requestToken) || $token !== $requestToken) {
            throw new ForbiddenHttpException('Access denied');
        }

        $auth = $post['auth'] ?? [];
        if (is_array($auth) && $auth) {
            $this->module->appConfig->load($auth);
            $this->module->setComponents([
                'client' => new \app\models\BitrixCrm\Client\Client($this->module->appConfig),
            ]);
        }

        $event = strtoupper((string)($post['event'] ?? ''));
        $fields = $post['data']['FIELDS'] ?? [];
        if (!is_array($fields)) {
            $fields = [];
        }

        $itemId = (int)($fields['ID'] ?? 0);
        if ($itemId <= 0) {
            return 'ok';
        }

        if (in_array($event, ['ONCRMCOMPANYADD', 'ONCRMCOMPANYUPDATE'], true)) {
            $entityTypeId = Module::COMPANY_ENTITY_TYPE_ID;
        } elseif (in_array($event, ['ONCRMDYNAMICITEMADD', 'ONCRMDYNAMICITEMUPDATE'], true)) {
            $entityTypeId = (int)($fields['ENTITY_TYPE_ID'] ?? 0);
        } else {
            return 'ok';
        }

        if ($entityTypeId <= 0) {
            return 'ok';
        }

        $pair = Module::getFieldPair($entityTypeId);
        if (!$pair || $pair->native === '' || $pair->custom === '') {
            return 'ok';
        }

        try {
            $syncService = new FieldSyncService(new CrmItemProvider($this->module->client));
            $updated = $syncService->syncFromNative($entityTypeId, $itemId);
            $logger->save(
                ['entityTypeId' => $entityTypeId, 'itemId' => $itemId, 'updated' => $updated],
                $post,
                'FIELD SYNC'
            );
        } catch (\Throwable $e) {
            $logger->save($e->getMessage(), $post, 'FIELD SYNC ERROR');
        }

        return 'ok';
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
