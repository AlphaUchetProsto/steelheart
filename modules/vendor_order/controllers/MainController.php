<?php

namespace app\modules\vendor_order\controllers;

use app\models\logger\CleanerLogger;
use app\models\logger\DebugLogger;
use yii\base\BaseObject;
use yii\web\Controller;
use app\modules\vendor_order\models\Client;
use yii\web\Response;
use  app\modules\vendor_order\models\ProductModel;
use  app\modules\vendor_order\models\ProductRow;
use  app\modules\vendor_order\models\BenchmarkingRow;
use  app\modules\vendor_order\models\SupplierRow;
use  app\modules\vendor_order\models\AmoTable;
use  app\modules\vendor_order\models\OpenCartApi;
use  app\modules\vendor_order\models\import\ImportForm;
use yii\web\UploadedFile;
use app\modules\vendor_order\models\db\ProductTable;
use app\modules\vendor_order\models\db\SupplierTable;
use app\modules\vendor_order\models\db\ProductRowVariationTable;
use app\modules\vendor_order\models\Helpers\TypeDetailsHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;
use app\modules\vendor_order\models\document\CommercialOfferGenerator;

class MainController extends Controller
{
    public $layout = 'main';
    public $enableCsrfValidation = false;

    public function runAction($id, $params = [])
    {
        CleanerLogger::clear();

        $token = \Yii::$app->params['modules']['vendor_order']['token'] ?? null;
        $requestToken = \Yii::$app->request->get('token');

        if(empty($requestToken) || $token !== $requestToken)
        {
            return self::actionAccessDenied();
        }
        
        try {
            return parent::runAction($id, $params);
        } catch (\Throwable $t) {
            $logger = DebugLogger::instance('main-errors');
            $logger->save($t->getMessage());
            $logger->save($t->getTraceAsString());
            throw $t;
        }
    }

    public function actionIndex()
    {
        $dealId = json_decode(\Yii::$app->request->post('PLACEMENT_OPTIONS'), true)['ID'];
        $productRows = ProductRow::findByDealID($dealId);
        $importForm = new ImportForm(['scenario' => ImportForm::SCENARIO_SUPPLIER]);
        $logger = DebugLogger::instance('firstOrder_index');
        $logger->save($productRows, $productRows, '$productRows');

        return $this->render('index', ['dealId' => $dealId, 'productRows' => $productRows, 'importForm' => $importForm, 'typesDetails' => TypeDetailsHelper::getCollection()]);
    }

    public function actionImport()
    {
        $model = new ImportForm(['scenario' => ImportForm::SCENARIO_SUPPLIER]);

        if (\Yii::$app->request->isPost && $model->load(\Yii::$app->request->post())) {
            $model->file = UploadedFile::getInstance($model, 'file');

            if ($model->validate()) {
                return $this->renderAjax('import_product_rows', ['productRows' => $model->collectProductRowsSupplier()]);
            }
        }

        \Yii::$app->response->format = Response::FORMAT_JSON;

        return $model->getErrors();
    }

    public function actionTest()
    {
        $dealId = 60739;
        $productRows = ProductRow::findByDealID($dealId);

        $importForm = new ImportForm();

        return $this->render('index', ['dealId' => $dealId, 'productRows' => $productRows, 'importForm' => $importForm]);
    }

    public function actionApiTest()
    {
        $api = new OpenCartApi();

       /* dump($api->request("POST", "api/login", [
            "key" => "lqph9GkXGjogx0iMgB5JXDTjArRPJAgQNpPPd9OD1noWBSc0URPSnbgvlyg4kPqCgPLpE4WlfU3zmb6zTuDGGaUSYjrUSGhBg2NJWtbAL6uF0zLXVYRhwNUZS2GFLVpn3L9on8MzmFhu9PjtDRUePr1OePtOuu2NTmPRGEGCELnUK9DO9HLa7coojM58l4sVGgrR6t76GGMuLeHpLUnha9lNraStAoAQDfcrO8bwktLTfz01azZZP4utNjLzzlrz",
            "username" => "Default",
        ]));*/
        //dd($api->fetchAuthData());
        //dump(json_decode($api->test2(), true));

        $result = $api->request("POST", "api/product/find", ["model" => "RE47429"]);
        dump($result);

        dd("test");
    }

    public function actionTestpopup()
    {

        if (\Yii::$app->request->get('supplier')) {
            return $this->renderAjax('popup_suppliers_content', [
                'suppliers' => SupplierTable::find()->where(['LIKE', 'name', \Yii::$app->request->get('supplier')])->limit(20)->all(),
            ]);
        }
    }

    /*Подгрузка списка товаров при вводе названия или артикула*/
    public function actionPopupContent()
    {
        $logger = DebugLogger::instance('popup-content');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST Данные');

        if (\Yii::$app->request->post('supplier')) {
            return $this->renderAjax('popup_suppliers_content', [
                'suppliers' => SupplierTable::find()->where(['LIKE', 'name', \Yii::$app->request->post('supplier')])->limit(20)->all(),
            ]);
        }

        if (\Yii::$app->request->post('article')) {
            return $this->renderAjax('popup_product_content', [
                'products' => ProductTable::find()->where(['LIKE', 'article', \Yii::$app->request->post('article')])->limit(20)->all(),
            ]);
        }

        return $this->renderAjax('popup_product_content', [
            'products' => ProductTable::find()->where(['LIKE', 'name', \Yii::$app->request->post('name')])->limit(20)->all(),
        ]);
    }

    public function actionGetCategoryProduct()
    {
        $bitrix = new \app\models\BitrixCrm\Client\Client();
        $folders = $bitrix->crm()->productSections()->list(['filter' => ['SECTION_ID' => \Yii::$app->request->post('folderId')]]);

        return $this->renderAjax('folders_tree', ['folders' => $folders]);
    }

    public function actionGetProducts()
    {
        $bitrix = new \app\models\BitrixCrm\Client\Client();
        $products = $bitrix
            ->crm()
            ->products()
            ->list(['filter' => ['SECTION_ID' => \Yii::$app->request->post('folderId')], 'select' => ['*', 'PROPERTY_*']]);

        return $this->renderAjax('product_tree', ['products' => $products]);
    }

    /*Добавление нового ряда в Заказ поставщику*/
    public function actionAddRow()
    {
        $row = new ProductRow();

        if (!empty(\Yii::$app->request->post('productId'))) {
            $row->setProduct(ProductTable::findOne(\Yii::$app->request->post('productId')));
            $row->validate();
        }

        return $this->renderAjax('table_row', ['row' => $row, 'typesDetails' => TypeDetailsHelper::getCollection()]);
    }

    /*Сохранение ряда товара*/
    public function actionSaveRows()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('save-product');
        $logger->save(\Yii::$app->request->getRawBody(), \Yii::$app->request, 'POST данные');

        if (\Yii::$app->request->isPost) {
            $model = new ProductRow();
            $result = $model->multipleUpdate(\Yii::$app->request->getRawBody());
            $logger->save($result, $result, 'Работа функции multipleUpdate');
        }

        return 200;
    }

    /*Удаление ряда*/
    public function actionDeleteRow()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('delete-row');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST данные');

        $model = new ProductRow();
        $model->deleteRow(\Yii::$app->request->post('rowId'));

        return 200;
    }

    public function actionAddRowVariation()
    {
        if (\Yii::$app->request->isPost) {
            $model = SupplierRow::instanceRow(\Yii::$app->request->post('id'));
            $productRow = new ProductRow();
            $brand = [
                'object' => $model->brand,
                'collections' => $productRow->getCollectionBrand()
            ];
            return $this->renderAjax('variations.php', ['item' => $model, 'productName' => $model->productName, 'brand' => $brand]);
        }

        return 200;
    }
    
    public function actionDeleteVariation()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('delete-variation');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST данные');

        if (\Yii::$app->request->isPost) {
            $productVariationRow = ProductRowVariationTable::findOne(\Yii::$app->request->post('variationId'));

            if ($productVariationRow->variation_id) {
                $variation = $productVariationRow->variation;

                $productVariationRow->delete();
                $variation->delete();
            } else {
                $productVariationRow->delete();
            }
        }

        return 200;
    }

    public function actionGetCollectionSupplier()
    {
        $logger = DebugLogger::instance('get_collection_supplier');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request->post(), 'POST Данные');

        $collection = SupplierRow::getList(\Yii::$app->request->post('productRowId'));

        /*$notesAmo = AmoTable::find()
            ->where(['like', 'note_first', \Yii::$app->request->post('productName')])
            ->orWhere(['like', 'note_second', \Yii::$app->request->post('productName')])
            ->orWhere(['like', 'note_third', \Yii::$app->request->post('productName')])
            ->orWhere(['like', 'note_fourth', \Yii::$app->request->post('productName')])
            ->orWhere(['like', 'note_fifth', \Yii::$app->request->post('productName')])
            ->all();*/

        return $this->renderAjax('supplier', ['collection' => $collection/*, 'notesAmo' => $notesAmo*/]);
    }

    public function actionInstall()
    {
        $logger = DebugLogger::instance('install');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request->post(), 'POST Данные');

        if(\Yii::$app->request->isPost) {
            $client = Client::instance(\Yii::$app->request->post()['auth']);
            $client->updateConfig();
            $client->install();
        }

        return $this->render('install');
    }

    public function actionAccessDenied()
    {
        return $this->render("access_denied");
    }

    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $postData = \Yii::$app->request->post();

        if (!isset($postData['rows']) || empty($postData['rows'])) {
            return ['error' => 'Нет данных для импорта'];
        }

        $templatePath = \Yii::getAlias('@app/web/temp/export_template.xlsx');

        if (!file_exists($templatePath)) {
            return ['error' => 'Шаблон не найден: ' . $templatePath];
        }

        $fileName = 'export_' . uniqid() . '.xlsx';
        $filePath = \Yii::getAlias('@temp_export_order') . '/' . $fileName;


        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

//        if ($sheet->getHighestRow() > 1) {
//            $sheet->removeRow(2, $sheet->getHighestRow() - 1);
//        }

        $groupedData = [];
        foreach ($postData['rows'] as $item) {
            $key = ($item['name'] ?? '') . '|' . ($item['article'] ?? '');
            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [];
            }
            $groupedData[$key][] = $item;
        }

        $currentRow = 2;


        foreach ($groupedData as $productGroup) {
            $firstItem = reset($productGroup);

            $sheet->setCellValue('A' . $currentRow, $firstItem['name'] ?? '');
            $sheet->setCellValue('B' . $currentRow, $firstItem['article'] ?? '');
            $currentRow++;

            foreach ($productGroup as $item) {
                if (empty($item['supplier']) && empty($item['brand'])) {
                    continue;
                }

                $sheet->setCellValue('A' . $currentRow, $item['name'] ?? '');
                $sheet->setCellValue('B' . $currentRow, $item['article'] ?? '');
                $sheet->setCellValue('C' . $currentRow, ''); // Тип техники
                $sheet->setCellValue('D' . $currentRow, $item['supplier'] ?? '');
                $sheet->setCellValue('E' . $currentRow, $item['brand'] ?? '');
                $sheet->setCellValue('F' . $currentRow, $item['typeDetail'] ?? '');
                $sheet->setCellValue('G' . $currentRow, $item['weight'] ?? '');
                $sheet->setCellValue('H' . $currentRow, $item['deliveryTime'] ?? '');
                $sheet->setCellValue('I' . $currentRow, $item['amount'] ?? '');
                $sheet->setCellValue('J' . $currentRow, $item['price'] ?? '');
                $sheet->setCellValue('K' . $currentRow, $item['currency'] ?? '');

                $currentRow++;
            }
        }

        $lastRow = $currentRow - 1;
        if ($lastRow >= 2) {
            $styleArray = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ]
            ];

            $sheet->getStyle("A2:K{$lastRow}")->applyFromArray($styleArray);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return ['result' => 'https://steelheart.uchetprosto.ru/web/temp/export_order/' . $fileName];
    }

    public function actionExport_old_161225()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('export');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST Данные');

        $postData = \Yii::$app->request->post();

        $groupedPostData = collect($postData['rows'])->groupBy(1);

        foreach ($groupedPostData as $supplier => $tableData)
        {
            $fileName = $supplier . '_' . uniqid();
            $filePath = \Yii::getAlias('@temp_export_order') . '/' . $fileName . '.xlsx';
            $spreadsheet = new Spreadsheet();

            $tableHeader = [['Название', 'Артикул', 'Количество', 'Поставщик', 'Бренд', 'Тип детали', 'Вес', 'Срок поставки', 'Цена', 'Валюта']];
            $spreadsheet->getActiveSheet()->fromArray($tableHeader, NULL, 'A1');
            $spreadsheet->getActiveSheet()->fromArray($tableData->toArray(), NULL, 'A2');

            $styleArray = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];

            $currentRow = count($postData['rows']) + 1;

            $spreadsheet->getActiveSheet()->getStyle("A1:J{$currentRow}")->applyFromArray($styleArray);
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);

            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

        }

        return ['result' => 'https://steelheart.uchetprosto.ru/web/temp/export_order/' . $fileName . '.xlsx'];
    }

    public function actionExport_old()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('export');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST Данные');

        $postData = \Yii::$app->request->post();

        $zipName = uniqid() . ".zip";

        $zip = new ZipArchive();
        $zip->open(\Yii::getAlias('@temp_export_order') . '/' . $zipName, ZIPARCHIVE::CREATE);

        $groupedPostData = collect($postData['rows'])->groupBy(1);

        foreach ($groupedPostData as $supplier => $tableData)
        {
            $fileName = $supplier . '_' . uniqid();
            $filePath = \Yii::getAlias('@temp_export_order') . '/' . $fileName . '.csv';
            $spreadsheet = new Spreadsheet();

//            $tableHeader = [['Название','Поставщик', 'Производитель', 'Тип детали', 'Вес', 'Срок поставки', 'Цена', 'Состояние', 'Источник']];
            $tableHeader = [['Название','Артикул', 'Количество']];
            $spreadsheet->getActiveSheet()->fromArray($tableHeader, NULL, 'A1');
            $spreadsheet->getActiveSheet()->fromArray($tableData->toArray(), NULL, 'A2');

            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];

            $currentRow = count($postData['rows']) + 1;

//            $spreadsheet->getActiveSheet()->getStyle("A1:I{$currentRow}")->applyFromArray($styleArray);
            $spreadsheet->getActiveSheet()->getStyle("A1:C{$currentRow}")->applyFromArray($styleArray);
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
//            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
//            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
//            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
//            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
//            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
//            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
//            $spreadsheet->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);

            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            $zip->addFile($filePath, basename($filePath));
        }

        $zip->close();

        return ['result' => 'https://steelheart.uchetprosto.ru/web/temp/export_order/' . $zipName];
    }

    /*Обновленеи множественного поля для таблицы*/
    public function actionUpdateDocumentField()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $logger = DebugLogger::instance('update-document-field');
        $logger->save(\Yii::$app->request->post(), \Yii::$app->request, 'POST данные');

        $postData = \Yii::$app->request->post();

        if ($postData['mainItem']){
            $mainItem = json_decode($postData['mainItem'], true);
            $totalPrice = [];
            foreach ($postData['table'] as $itemTable){
                if (isset($totalPrice[$itemTable['article']])){
                    $totalPrice[$itemTable['article']] += $itemTable['price'];
                }else{
                    $totalPrice[$itemTable['article']] = 0;
                    $totalPrice[$itemTable['article']] += $itemTable['price'];
                }
            }

            foreach ($totalPrice as $article => $price){
                foreach ($mainItem as &$item){
                    if ($item['article'] == $article){
                        $item['price'] = $price;
                    }
                }
            }






//            foreach ($postData['table'] as $itemTable){
//                $price += $itemTable['price'];
//            }
//            $mainItem['price'] = $price;
            $logger->save($mainItem, $mainItem, 'Данные $mainItem');
            $result = (new ProductRow())->multipleUpdate(json_encode($mainItem));
            $logger->save($result, $result, 'Работа функции multipleUpdate');
        }
        
        $model = new CommercialOfferGenerator(\Yii::getAlias('@app') . '/web/temp/kp_template.docx');
        $file = $model->generate(['products' => $postData['table'], 'totalPrice' => $postData['totalPriceRow']], \Yii::getAlias('@temp_kp') . '/' . $postData['dealId'] . '.docx');

        $bitrix = new \app\models\BitrixCrm\Client\Client();

        $commands['update_deal'] = $bitrix->api()->buildCommand('crm.deal.update', [
            'ID' => $postData['dealId'],
            'FIELDS' => [
                'OPPORTUNITY' => $postData['totalPriceRow'],
                'UF_CRM_1740645691595' => $postData['totalPriceProduct'],
                'UF_CRM_1742543566' => $postData['rows'],
            ]
        ]);

        $commands['add_comment'] = $bitrix->api()->buildCommand('crm.timeline.comment.add', [
            'fields' => [
                'ENTITY_ID' => $postData['dealId'],
                'ENTITY_TYPE' => 'deal',
                'COMMENT' => 'Коммерческое предложение',
                'FILES' => [
                    [basename($file), base64_encode(file_get_contents($file))],
                ],
            ]
        ]);

        return $bitrix->api()->batchRequest($commands)->getResponse();
    }
}
