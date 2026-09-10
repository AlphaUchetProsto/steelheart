<?php

namespace app\modules\vendor_order\controllers;

use app\models\logger\DebugLogger;
use app\modules\vendor_order\models\Client;
use app\modules\vendor_order\models\db\ProductRowVariationTable;
use yii\base\BaseObject;
use yii\web\Response;
use  app\modules\vendor_order\models\ProductRow;
use  app\modules\vendor_order\models\CollectionProductRow;

class BasController extends MainController
{
    public function actionAddSupplier()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('add-supplier');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST Данные');

        if (\Yii::$app->request->isPost) {
            $postData = \Yii::$app->request->post();

            $model = new ProductRowVariationTable();
            $model->price = preg_replace('/[^0-9.]/', '', $postData['price']);
            $model->manufacturer = $postData['manufacturer'];
            $model->product_row_id = $postData['productRowId'];
            $model->source = $postData['siteName'];
            $model->product_name = $postData['productName'];

            if (isset($postData['supplier'])) {
                $model->supplier = $postData['supplier'];
            }

            $model->delivery_time = $postData['deliveryTime'];

            if (isset($postData['weight'])) {
                $model->weight = preg_replace('/[^0-9.]/', '', $postData['price']);
            }

            $model->save();
        }

        return 200;
    }

    public function actionSearchArticle()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('search-article');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST Данные');

        if (\Yii::$app->request->isPost) {
//            $productRows = ProductRow::findByDealID(\Yii::$app->request->post('dealId'));
//            $productRows->deleteSupplier()->parseBitrix()-
            return CollectionProductRow::parseApi_searchArticle(\Yii::$app->request->post('article'));
        }

        return 200;
    }

    public function actionSearchSupplier()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('search-supplier');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST Данные');

        if (\Yii::$app->request->isPost) {
            $productRows = ProductRow::findByDealID(\Yii::$app->request->post('dealId'));
            $productRows->deleteSupplier()->parseBitrix()->parseApi();
        }

        return 200;
    }

    public function actionMoveProductBenchmarking()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('search-supplier');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST Данные');

        if (\Yii::$app->request->isPost) {
            $productRows = ProductRow::findByDealID(\Yii::$app->request->post('dealId'));
            $result = $productRows->moveToBenchmarking();
            $logger->save($productRows, $productRows, 'Содержимое $productRows');
            $logger->save($result, $result, 'Результат moveToBenchmarking()');
        }

        return 200;
    }

    public function actionSearchSupplierTest()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('search-supplier');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST Данные');

        $productRows = ProductRow::findByDealID(60757);
        dd($productRows->deleteSupplier()->parseBitrix()->parseApi());

        return 200;
    }

    public function actionProcessorSupplier()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('processor-supplier');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST Данные');

        $productRows = ProductRow::findByDealID(\Yii::$app->request->post('dealId'));
        $productRows->processorSupplier();

        return 200;
    }
}