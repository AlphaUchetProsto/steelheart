<?php

namespace app\modules\contract_field\controllers;

use app\modules\contract_field\Module;
use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * @property Module $module
 */
abstract class BaseController extends Controller
{
    public $layout = 'main';

    /** @var list<string> */
    protected array $actionsWithoutToken = ['install'];

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    public function runAction($id, $params = [])
    {
        if (!in_array($id, $this->actionsWithoutToken, true)) {
            $this->assertToken();
        }

        return parent::runAction($id, $params);
    }

    protected function assertToken(): void
    {
        $token = $this->module->params['token'] ?? null;
        $requestToken = Yii::$app->request->get('token');

        if (empty($requestToken) || $token !== $requestToken) {
            throw new ForbiddenHttpException('Access denied');
        }
    }
}
