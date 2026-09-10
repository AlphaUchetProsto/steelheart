<?php

namespace app\modules\vendor_order\controllers;

use app\models\logger\DebugLogger;
use app\modules\vendor_order\models\Client;
use app\modules\vendor_order\models\db\ProductTable;
use app\modules\vendor_order\models\db\ProductVariationTable;
use app\modules\vendor_order\models\db\SupplierTable;
use yii\base\BaseObject;
use yii\web\Controller;
use yii\web\Response;
use  app\modules\vendor_order\models\ProductRow;
use  app\modules\vendor_order\models\BenchmarkingRow;
use  app\modules\vendor_order\models\import\ImportForm;
use  app\modules\vendor_order\models\benchmarking\SupplierRow;
use app\modules\vendor_order\models\Helpers\TypeDetailsHelper;
use yii\web\UploadedFile;

class BenchmarkingController extends Controller
{
    public $layout = 'main';
    public $enableCsrfValidation = false;

    public function runAction($id, $params = [])
    {
        $token = \Yii::$app->params['modules']['vendor_order']['token'] ?? null;
        $requestToken = \Yii::$app->request->get('token');

        if(empty($requestToken) || $token !== $requestToken)
        {
            return self::actionAccessDenied();
        }

        return parent::runAction($id, $params);
    }

    public function actionIndex()
    {
        $logger = DebugLogger::instance('index');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST Данные');
        $dealId = json_decode(\Yii::$app->request->post('PLACEMENT_OPTIONS'), true)['ID'];

        $productRows = BenchmarkingRow::findByDealId($dealId);
        $productVariations = BenchmarkingRow::getVariationsFromDeal($dealId);
        $logger->save($productRows, $productRows, 'Содержимое $productRows');
        $logger->save($productVariations, $productVariations, 'Содержимое $productVariations');

        $importForm = new ImportForm(['scenario' => ImportForm::SCENARIO_BENCHMARKING]);
        $importForm->dealId = $dealId;

        return $this->render('index', ['dealId' => $dealId, 'productRows' => $productRows, 'importForm' => $importForm]);
    }

    public function actionPopupContent()
    {
        $logger = DebugLogger::instance('popup-content');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST Данные');

        if (\Yii::$app->request->post('supplier')) {
            return $this->renderAjax('popup_suppliers_content', [
                'suppliers' => SupplierTable::find()->where(['LIKE', 'name', \Yii::$app->request->post('supplier')])->limit(20)->all(),
            ]);
        }
    }

    public function actionTest()
    {
        $dealId = 60683;

        $productRows = BenchmarkingRow::findByDealId($dealId);
        
        $importForm = new ImportForm(['scenario' => ImportForm::SCENARIO_BENCHMARKING]);
        $importForm->dealId = $dealId;

        return $this->render('index', ['dealId' => $dealId, 'productRows' => $productRows, 'importForm' => $importForm]);
    }

    public function actionImport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('import');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST данные');

        $model = new ImportForm(['scenario' => ImportForm::SCENARIO_BENCHMARKING]);

        if (\Yii::$app->request->isPost && $model->load(\Yii::$app->request->post())) {
            $model->file = UploadedFile::getInstance($model, 'file');

            if ($model->validate()) {
                $res = $model->uploadDataBenchmarking();
                $logger->save($res, $res, 'Результат uploadDataBenchmarking()');
                return 200;
            }
        }

        return $model->getErrors();

    }

    public function actionSaveRows()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('save-rows');
//        $logger->save(\Yii::$app->request->getRawBody(), \Yii::$app->request, 'POST данные');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST данные');

        $model = new BenchmarkingRow();
//        $model->multipleUpdate(\Yii::$app->request->getRawBody());
        $resultMultipleUpdate = $model->multipleUpdate(\Yii::$app->request->post('data'));
//        $logger->save($resultMultipleUpdate, $resultMultipleUpdate, 'Результат $resultMultipleUpdate');

        $productRows = BenchmarkingRow::findByDealId(\Yii::$app->request->post('dealId'));
        $resultLookForPosting = $productRows->lookForPosting();
//        $logger->save($productRows, $productRows, 'Результат $productRows');
        $logger->save($resultLookForPosting, $resultLookForPosting, 'Результат lookForPosting()');

        return 200;
    }

    public function actionDeleteRow()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('delete-row');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST данные');

        $model = new BenchmarkingRow();
        $model->deleteRow(\Yii::$app->request->post('rowId'));

        return 200;
    }

    public function actionDeleteVariation()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('delete-variation');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST данные');

        if (\Yii::$app->request->isPost) {
            $model = SupplierRow::findOne(\Yii::$app->request->post('id'));
            $model->delete();
        }

        return 200;
    }

    public function actionGetCollectionSupplier()
    {
        $logger = DebugLogger::instance('get_collection_supplier');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request->post(), 'POST Данные');

        $rows = SupplierRow::getList(\Yii::$app->request->post('productRowId'));

        return $this->render('supplier', ['rows' => $rows]);
    }

    public function actionSearch()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('delete-row');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST данные');

        $productRows = BenchmarkingRow::findByDealId(\Yii::$app->request->post('dealId'));
        $productRows->lookForPosting();

        /*$productRows = ProductRow::findByDealID(\Yii::$app->request->post('dealId'));
        $productRows->moveToProductRow();*/

        return 200;
    }

    public function actionAccessDenied()
    {
        return $this->render("access_denied");
    }

    public function actionAddRowVariation()
    {
        if (\Yii::$app->request->isPost) {
            $model = SupplierRow::instanceRow(\Yii::$app->request->post('id'));
            return $this->renderAjax('variations.php', ['item' => $model, 'typesDetails' => TypeDetailsHelper::getCollection()]);
        }

        return 200;
    }
}
