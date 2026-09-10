<?php

namespace app\modules\import_product\controllers;

use app\models\BitrixCrm\Models\Tasks\TaskModel;
use app\models\logger\DebugLogger;
use app\modules\import_product\models\ChunkReadFilter;
use Yii;
use yii\web\Controller;
use app\modules\import_product\models\ImportForm;

use yii\web\UploadedFile;

use function Symfony\Component\String\u;


class DefaultController extends Controller
{
    public $layout = 'main';
    public $enableCsrfValidation = false;
    
    public function runAction($id, $params = [])
    {
        $token = \Yii::$app->params['modules']['import_product']['token'] ?? null;
        $requestToken = \Yii::$app->request->get('token');

        if(empty($requestToken) || $token !== $requestToken)
        {
            return self::actionAccessDenied();
        }

        return parent::runAction($id, $params);
    }

    public function actionIndex()
    {
        ini_set("memory_limit","4096M");

        $model = new ImportForm();

        if (\Yii::$app->request->isPost && $model->load(\Yii::$app->request->post())) {
            $model->file = UploadedFile::getInstance($model, 'file');

            try {
                if ($model->validate()) {
                    $model->import();
                }

                Yii::$app->session->setFlash('success', "Импорт успешно завершен.");
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('index', ['model' => $model]);
    }

    public function actionAccessDenied()
    {
        return $this->render("access_denied");
    }

    public function actionInstall()
    {
        $logger = DebugLogger::instance('install');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request->post(), 'POST Данные');

        return $this->render('install');
    }
}
