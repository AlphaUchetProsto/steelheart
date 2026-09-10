<?php

namespace app\modules\vendor_order\models\import;

use app\models\logger\DebugLogger;
use app\modules\vendor_order\models\BenchmarkingRow;
use app\modules\vendor_order\models\db\ProductTable;
use app\modules\vendor_order\models\ProductRow;
use Tightenco\Collect\Support\Collection;
use yii\base\BaseObject;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use app\modules\vendor_order\models\benchmarking\SupplierRow;
use app\modules\vendor_order\models\benchmarking\CollectionSupplierRow;
use app\modules\vendor_order\models\db\ProductVariationTable;
//use app\modules\vendor_order\models\db\CurrencyTable;
use app\modules\vendor_order\models\Helpers\CurrencyHelper;

use app\modules\vendor_order\models\db\BenchmarkingRowTable;
use app\modules\vendor_order\models\db\BenchmarkingRowVariationTable;
use app\modules\vendor_order\models\db\PropertyOptionsTable;
use app\modules\vendor_order\models\db\CurrencyTable;


class ImportForm extends Model
{
    public $dealId;
    public $file;

    const SCENARIO_SUPPLIER = 'supplier';
    const SCENARIO_BENCHMARKING = 'benchmarking';

    private $typeMachineMap = [
        'Тип техники 1' => 45,
        'Тип техники 2' => 47,
        'Тип техники 3' => 113,
    ];

    private $typeDetailMap = [
        'Б/У' => 1,
        'Реман' => 2,
        'Новая любая' => 3,
        'Новая аналог' => 4,
        'Новая оригинал' => 5,
        'Восстановленная' => 6,
        'Любое состояние' => 7,
    ];

    private $currencyMap = [
        'RUB' => 1,
        'USD' => 2,
        'EUR' => 3,
        'CNY' => 4,
        'KZT' => 5,
    ];

    private $brandMap = [
        'Copy' => 49,
        'Cat' => 51,
        'Liebherr' => 111,
        'John Deere' => 167,
        'Vogele' => 169,
        'Volvo' => 171,
        'JCB' => 173,
        'Hamm' => 175,
        'Manitou' => 177,
        'Wirtgen' => 179,
        'Bobcat' => 181,
        'Hubtex' => 183,
        'Hyster' => 185,
        'AGCO' => 187,
        'Blumaq' => 189,
        'Bomag' => 191,
        'Корея' => 193,
        'China' => 195,
        'OEM' => 197,
    ];

    public function rules()
    {
        return [
            [['dealId'], 'number'],
            [['dealId'], 'required', 'on' => self::SCENARIO_BENCHMARKING],
            [['file'], 'file', 'skipOnEmpty' => false, 'extensions' => 'xlsx'],
            [['file'], 'validationRow'],
        ];
    }

    private function mapBrand($text)
    {
        if (empty($text)) {
            return null;
        }

        $text = trim($text);

        // Прямое соответствие
        if (isset($this->brandMap[$text])) {
            return $this->brandMap[$text];
        }

        // Попробуем найти по частичному совпадению (case-insensitive)
        foreach ($this->brandMap as $brandName => $brandId) {
            if (strcasecmp($text, $brandName) === 0) {
                return $brandId;
            }
        }

        return null;
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_SUPPLIER => ['file'],
            self::SCENARIO_BENCHMARKING => ['dealId', 'file'],
        ];
    }

    private function getDataFile()
    {
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($this->file->tempName);
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $reader->setIncludeCharts(true);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($this->file->tempName);
        $worksheet = $spreadsheet->getActiveSheet();

        return $worksheet->toArray();
    }

    public function validationRow($attribute, $params)
    {
        $data = $this->getDataFile();

        // Проверяем, что файл не пустой
        if (empty($data) || count($data) < 2) {
            $this->addError($attribute, 'Файл пустой или содержит только заголовки');
            return;
        }

        // Проверяем заголовки
        $expectedHeaders = [
            'товар', 'артикул', 'тип техники', 'поставщик', 'бренд',
            'тип детали', 'вес (кг)', 'срок поставки', 'количество', 'цена', 'валюта'
        ];

        $actualHeaders = array_map('mb_strtolower', array_map('trim', $data[0]));

        if (count($actualHeaders) < count($expectedHeaders)) {
            $this->addError($attribute, 'Неверное количество колонок в файле');
            return;
        }

        $hasErrors = false;
        $errors = [];

        // Проверяем каждую строку
        foreach ($data as $index => $row) {
            if ($index === 0) continue; // Пропускаем заголовок

            $rowNumber = $index + 1;

            // Проверяем артикул (колонка 1)
            if (empty(trim($row[1] ?? ''))) {
                $errors[] = "Строка {$rowNumber}: отсутствует артикул";
                $hasErrors = true;
            }

            // Для вариаций проверяем бренд
            if (!empty($row[4])){
                $brandText = $row[4];
                $brand = $this->mapBrand($brandText);

                if ($brand === null) {
                    $errors[] = "Строка {$rowNumber}: неизвестный бренд '{$brandText}'";
                    $hasErrors = true;
                }
            }

            // Проверяем тип техники (если указан)
            if (!empty($row[2])) {
                $typeMachineText = trim($row[2]);
                if (!isset($this->typeMachineMap[$typeMachineText])) {
                    $errors[] = "Строка {$rowNumber}: неизвестный тип техники '{$typeMachineText}'";
                    $hasErrors = true;
                }
            }

            // Для строк с поставщиком (вариации) проверяем обязательные поля
            if (!empty(trim($row[3] ?? ''))) {
                // Проверяем тип детали
                if (empty($row[5])) {
                    $errors[] = "Строка {$rowNumber}: для вариации необходимо указать тип детали";
                    $hasErrors = true;
                } elseif (!isset($this->typeDetailMap[trim($row[5])])) {
                    $errors[] = "Строка {$rowNumber}: неизвестный тип детали '{$row[5]}'";
                    $hasErrors = true;
                }

                // Проверяем валюту
                if (empty($row[10])) {
                    $errors[] = "Строка {$rowNumber}: для вариации необходимо указать валюту";
                    $hasErrors = true;
                } elseif (!isset($this->currencyMap[trim($row[10])])) {
                    $errors[] = "Строка {$rowNumber}: неизвестная валюта '{$row[10]}'";
                    $hasErrors = true;
                }

                // Проверяем цену
                if (empty($row[9]) || !is_numeric($row[9])) {
                    $errors[] = "Строка {$rowNumber}: для вариации необходимо указать цену (число)";
                    $hasErrors = true;
                }
            }
        }

        if ($hasErrors) {
            $errorMessage = "Файл содержит ошибки:\n" . implode("\n", array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $errorMessage .= "\n... и еще " . (count($errors) - 10) . " ошибок";
            }
            $this->addError($attribute, $errorMessage);
        }
    }

    public function uploadDataBenchmarking()
    {
        $result = [
            'logs' => [],
            'stats' => [
                'products_created' => 0,
                'products_updated' => 0,
                'variations_created' => 0,
                'variations_updated' => 0,
                'errors' => [],
                'warnings' => []
            ],
            'data' => [],
        ];

        // Получаем данные из файла
        $data = $this->getDataFile();

        // Убираем заголовок
        $data = collect($data)->slice(1)->toArray();

        $result['logs'][] = 'Начало обработки файла, строк: ' . count($data);

        // Собираем товары и вариации
        $products = []; // артикул => product_id
        $benchmarkingRows = []; // артикул => benchmarking_row_id
        $productRows = []; // артикул => product_row_id
        $variationsToSave = [];

        // Сначала обрабатываем все строки, чтобы собрать товары
        foreach ($data as $index => $row) {
            $rowNumber = $index + 2;

            // Чистим значения
            $name = trim($row[0] ?? '');
            $article = trim($row[1] ?? '');
            $typeMachineText = trim($row[2] ?? '');
            $supplier = trim($row[3] ?? '');
            $brandText = trim($row[4] ?? '');
            $typeDetailText = trim($row[5] ?? '');
            $weight = !empty($row[6]) && is_numeric($row[6]) ? (float)$row[6] : null;
            $deliveryTime = trim($row[7] ?? '');
            $quantity = !empty($row[8]) && is_numeric($row[8]) ? (float)$row[8] : null;
            $price = !empty($row[9]) && is_numeric($row[9]) ? (float)$row[9] : null;
            $currencyText = trim($row[10] ?? '');

            // Преобразуем текстовые значения в ID
            $typeMachine = !empty($typeMachineText) ? ($this->typeMachineMap[$typeMachineText] ?? null) : null;
            $brand = $this->mapBrand($brandText);
            $typeDetailId = !empty($typeDetailText) ? ($this->typeDetailMap[$typeDetailText] ?? null) : null;
            $currency = !empty($currencyText) ? ($this->currencyMap[$currencyText] ?? null) : null;

            // Проверяем артикул
            if (empty($article)) {
                $result['stats']['errors'][] = "Строка {$rowNumber}: отсутствует артикул";
                continue;
            }

            // Для вариаций проверяем бренд
            if (!empty($supplier) && !empty($brandText) && $brand === null) {
                $result['stats']['errors'][] = "Строка {$rowNumber}: неизвестный бренд '{$brandText}'";
                continue;
            }

            // Если это товар (без поставщика) ИЛИ первое упоминание товара
            if (empty($supplier) || !isset($products[$article])) {

                // Ищем или создаем товар
                $product = ProductTable::findOne(['article' => $article]);

                if (!$product) {
                    // Создаем новый товар
                    $product = new ProductTable();
                    $product->name = !empty($name) ? $name : $article;
                    $product->article = $article;

                    if ($product->save()) {
                        $result['stats']['products_created']++;
                        $result['logs'][] = "Создан товар: {$article} (ID: {$product->id})";
                    } else {
                        $result['stats']['errors'][] = "Строка {$rowNumber}: ошибка создания товара - " . implode(', ', $product->getFirstErrors());
                        continue;
                    }
                } else {
                    // Обновляем название если оно пустое в БД или отличается
                    if (!empty($name) && $product->name !== $name) {
                        $product->name = $name;
                        $product->save();
                    }
                    $result['stats']['products_updated']++;
                }

                // Сохраняем ID товара
                $products[$article] = $product->id;

                // Ищем или создаем benchmarking_row
                $benchmarkingRow = BenchmarkingRowTable::findOne([
                    'product_id' => $product->id,
                    'deal_id' => $this->dealId
                ]);

                if (!$benchmarkingRow) {
                    $benchmarkingRow = new BenchmarkingRowTable();
                    $benchmarkingRow->product_id = $product->id;
                    $benchmarkingRow->deal_id = $this->dealId;
                    $benchmarkingRow->status = 1; // Импортированный товар

                    // ГАРАНТИРОВАННО СОЗДАЕМ PRODUCT_ROW ДЛЯ КАЖДОГО ТОВАРА
                    $productRow = \app\modules\vendor_order\models\db\ProductRowTable::findOne([
                        'product_id' => $product->id,
                        'deal_id' => $this->dealId
                    ]);

                    if (!$productRow) {
                        $productRow = new \app\modules\vendor_order\models\db\ProductRowTable();
                        $productRow->product_id = $product->id;
                        $productRow->deal_id = $this->dealId;
                        $productRow->brand = $brand; // Может быть null
                        $productRow->type_machine = $typeMachine; // Может быть null

                        if ($productRow->save()) {
                            $benchmarkingRow->product_row_id = $productRow->id;
                            $productRows[$article] = $productRow->id;
                            $result['logs'][] = "Создан product_row для товара: {$article} (ID: {$productRow->id})";
                        } else {
                            $result['stats']['errors'][] = "Строка {$rowNumber}: ошибка создания product_row - " . implode(', ', $productRow->getFirstErrors());
                            // Продолжаем без product_row
                        }
                    } else {
                        $productRows[$article] = $productRow->id;
                        $benchmarkingRow->product_row_id = $productRow->id;
                    }

                    if ($benchmarkingRow->save()) {
                        $benchmarkingRows[$article] = $benchmarkingRow->id;
                        $result['logs'][] = "Создана запись в benchmarking_row для товара: {$article} (status=1)";
                    } else {
                        $result['stats']['errors'][] = "Строка {$rowNumber}: ошибка создания benchmarking_row - " . implode(', ', $benchmarkingRow->getFirstErrors());
                        continue;
                    }
                } else {
                    // Если benchmarking_row уже существует, обновляем статус
                    if ($benchmarkingRow->status != 1) {
                        $benchmarkingRow->status = 1;
                        $benchmarkingRow->save();
                    }
                    $benchmarkingRows[$article] = $benchmarkingRow->id;

                    // Убедимся что есть product_row
                    if (!$benchmarkingRow->product_row_id) {
                        $productRow = \app\modules\vendor_order\models\db\ProductRowTable::findOne([
                            'product_id' => $product->id,
                            'deal_id' => $this->dealId
                        ]);

                        if (!$productRow) {
                            $productRow = new \app\modules\vendor_order\models\db\ProductRowTable();
                            $productRow->product_id = $product->id;
                            $productRow->deal_id = $this->dealId;
                            $productRow->brand = $brand;
                            $productRow->type_machine = $typeMachine;
                            $productRow->save();

                            $benchmarkingRow->product_row_id = $productRow->id;
                            $benchmarkingRow->save();
                        }
                    }

                    $productRows[$article] = $benchmarkingRow->product_row_id;
                }

                $result['logs'][] = "Обработан товар: {$article}";

            }

            // Если это вариация (есть поставщик)
            if (!empty($supplier)) {
                // Проверяем обязательные поля для вариации
                $validationErrors = [];

                if (empty($price)) {
                    $validationErrors[] = "цена не указана";
                }

                if ($currency === null) {
                    $validationErrors[] = "неизвестная валюта '{$currencyText}'";
                }

                if ($typeDetailId === null) {
                    $validationErrors[] = "неизвестный тип детали '{$typeDetailText}'";
                }

                if (!empty($validationErrors)) {
                    $result['stats']['errors'][] = "Строка {$rowNumber}: " . implode(', ', $validationErrors);
                    continue;
                }

                // Проверяем тип детали
                if ($typeDetailId < 1 || $typeDetailId > 7) {
                    $result['stats']['errors'][] = "Строка {$rowNumber}: неверный тип детали ({$typeDetailId})";
                    continue;
                }

                // Проверяем валюту
                if ($currency < 1 || $currency > 5) {
                    $result['stats']['errors'][] = "Строка {$rowNumber}: неверная валюта ({$currency})";
                    continue;
                }

                // Сохраняем вариацию
                $variationsToSave[] = [
                    'row_number' => $rowNumber,
                    'article' => $article,
                    'supplier' => $supplier,
                    'brand' => $brand,
                    'brand_text' => $brandText,
                    'type_detail_id' => $typeDetailId,
                    'type_detail_text' => $typeDetailText,
                    'weight' => $weight,
                    'delivery_time' => $deliveryTime,
                    'quantity' => $quantity,
                    'price' => $price,
                    'currency' => $currency,
                    'currency_text' => $currencyText,
                    'type_machine' => $typeMachine,
                    'type_machine_text' => $typeMachineText
                ];

                $result['logs'][] = "Подготовлена вариация для товара {$article}";
            }
        }

        $result['logs'][] = 'Товары обработаны. Найдено вариаций для сохранения: ' . count($variationsToSave);

        // Обрабатываем вариации
        foreach ($variationsToSave as $variationData) {
            $article = $variationData['article'];

            if (!isset($products[$article])) {
                $result['stats']['errors'][] = "Строка {$variationData['row_number']}: товар с артикулом {$article} не найден";
                continue;
            }

            if (!isset($benchmarkingRows[$article])) {
                $result['stats']['errors'][] = "Строка {$variationData['row_number']}: benchmarking_row для товара {$article} не найден";
                continue;
            }

            $productId = $products[$article];
            $benchmarkingRowId = $benchmarkingRows[$article];

            // Ищем существующую вариацию
            $productVariation = ProductVariationTable::find()
                ->where([
                    'product_id' => $productId,
                    'supplier' => $variationData['supplier'],
                    'brand' => $variationData['brand'],
                    'type_detail_id' => $variationData['type_detail_id'],
                    'currency' => $variationData['currency']
                ])
                ->one();

            if (!$productVariation) {
                $productVariation = new ProductVariationTable();
                $result['stats']['variations_created']++;
                $result['logs'][] = "Создана новая вариация для товара {$article}";
            } else {
                $result['stats']['variations_updated']++;
                $result['logs'][] = "Обновлена существующая вариация для товара {$article}";
            }

            // Заполняем/обновляем данные вариации
            $productVariation->product_id = $productId;
            $productVariation->supplier = $variationData['supplier'];
            $productVariation->brand = $variationData['brand'];
            $productVariation->price = $variationData['price'];
            $productVariation->delivery_time = $variationData['delivery_time'];
            $productVariation->weight = $variationData['weight'];
            $productVariation->type_detail_id = $variationData['type_detail_id'];
            $productVariation->quantity = $variationData['quantity'];
            $productVariation->currency = $variationData['currency'];
            $productVariation->source = 'Импорт из XLSX';

            if ($productVariation->save()) {
                // Создаем/обновляем связь в benchmarking_row_variation
                $benchmarkingVariation = BenchmarkingRowVariationTable::findOne([
                    'benchmarking_row_id' => $benchmarkingRowId,
                    'variation_id' => $productVariation->id
                ]);

                if (!$benchmarkingVariation) {
                    $benchmarkingVariation = new BenchmarkingRowVariationTable();
                    $benchmarkingVariation->benchmarking_row_id = $benchmarkingRowId;
                    $benchmarkingVariation->variation_id = $productVariation->id;
                    $benchmarkingVariation->product_id = $productId;

                    if ($benchmarkingVariation->save()) {
                        $result['logs'][] = "Создана связь в benchmarking_row_variation";
                    } else {
                        $result['stats']['errors'][] = "Строка {$variationData['row_number']}: ошибка создания связи";
                    }
                }
            } else {
                $result['stats']['errors'][] = "Строка {$variationData['row_number']}: ошибка сохранения вариации";
            }
        }

        // Итоговая статистика
        $result['logs'][] = '=== ИТОГ ИМПОРТА ===';
        $result['logs'][] = 'Создано товаров: ' . $result['stats']['products_created'];
        $result['logs'][] = 'Обновлено товаров: ' . $result['stats']['products_updated'];
        $result['logs'][] = 'Создано вариаций: ' . $result['stats']['variations_created'];
        $result['logs'][] = 'Обновлено вариаций: ' . $result['stats']['variations_updated'];
        $result['logs'][] = 'Ошибок: ' . count($result['stats']['errors']);

        return $result;
    }

    public function collectProductRowsSupplier()
    {
        $result = new Collection();
        $data = $this->getDataFile();

        $collectionRow = collect($data)->slice(1);

        $bitrix = new \app\models\BitrixCrm\Client\Client();
        $fields = $bitrix->api()->request('crm.company.fields')->getResponse();

        $collectionBrand = ArrayHelper::map($fields['UF_CRM_1724304462480']['items'], 'VALUE', 'ID');
        $collectionTypeMachine = ArrayHelper::map($fields['UF_CRM_1724304514706']['items'], 'VALUE', 'ID');

        foreach ($collectionRow as $rowData)
        {
            $product = ProductTable::find()->where(['=', 'article', $rowData[1]])->one();

            $model = new ProductRow();
            $model->name = $rowData[0];

            if (!empty($rowData[0])) {
                $model->name = $rowData[0];
            }else {
                $model->name = $rowData[1];
            }

            $model->article = $rowData[1];

            if (isset($collectionBrand[$rowData[2]])) {
                $model->brand = $collectionBrand[$rowData[2]];
            }

            if (isset($collectionTypeMachine[$rowData[3]])) {
                $model->typeMachine = $collectionTypeMachine[$rowData[3]];
            }

            if ($product) {
                $model->productId = $product->id;
            }

            if ($model->validate()) {
                $result = $result->push($model);
            }
        }

        return $result;
    }
}