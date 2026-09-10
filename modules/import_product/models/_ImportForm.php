<?php

namespace app\modules\import_product\models;

use app\modules\vendor_order\models\db\ProductTable;
use app\modules\vendor_order\models\db\ProductVariationTable;

use yii\base\BaseObject;
use yii\base\Model;

use function Symfony\Component\String\u;

class ImportForm extends Model
{
    public $file;

    public function rules()
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => false, 'extensions' => 'xlsx'],
        ];
    }

    public function import()
    {
        $filePath = $this->file->tempName;

        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($filePath);
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $reader->setIncludeCharts(false);
        $reader->setReadDataOnly(true);

        $chunkFilter = new ChunkReadFilter();
        $startRow = 2;
        $chunkSize = 100;
        $finish = false;

        $db = \Yii::$app->db;

        do {
            $chunkFilter->setRows($startRow, $chunkSize);
            $reader->setReadFilter($chunkFilter);

            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestColumn = $worksheet->getHighestDataColumn();
            $nextRange = $startRow + $chunkSize - 1;
            $range = "A{$startRow}:{$highestColumn}{$nextRange}";

            $data = $worksheet->rangeToArray($range);
            $data = collect($data)->filter(function ($item) {
                // Проверяем базовые условия
                if (!$item[0] || $item[4] === null || $item[5] === null) {
                    return false;
                }

                // Заменяем запятую на точку в цене
                $price = str_replace(',', '.', $item[5]);

                // Проверяем, что после замены — валидное число
                return is_numeric($price);
            })->toArray();

            $startRow += $chunkSize;

            if (!empty($data)) {
                $productArticles = array_column($data, 0);
                $existingProducts = ProductTable::find()
                    ->select(['id', 'article'])
                    ->where(['article' => $productArticles])
                    ->indexBy('article')
                    ->asArray()
                    ->all();

                $newProducts = [];
                $newVariations = [];

                foreach ($data as $row) {
                    $article = $row[0];
                    $name = $row[1] ?? $article;
                    $supplier = $row[2];
                    $brand = $row[3];
                    $deliveryTime = $row[4];
                    $price = $row[5];
                    $currency = $row[6];
                    $weight = $row[7];

                    if (!isset($existingProducts[$article])) {
                        $newProducts[] = [$name, $article];
                    }
                }

                // Вставка новых продуктов
                if (!empty($newProducts)) {
                    $db->createCommand()
                        ->batchInsert(ProductTable::tableName(), ['name', 'article'], $newProducts)
                        ->execute();

                    // Повторно загружаем все продукты, включая только что вставленные
                    $existingProducts = ProductTable::find()
                        ->select(['id', 'article'])
                        ->where(['article' => $productArticles])
                        ->indexBy('article')
                        ->asArray()
                        ->all();
                }

                // Подготовка вариаций
                foreach ($data as $row) {
                    $article = $row[0];
                    $productId = $existingProducts[$article]['id'];

                    $variation = ProductVariationTable::find()
                        ->where(['supplier' => $row[2], 'product_id' => $productId])
                        ->one();

                    if (!$variation) {
                        $newVariations[] = [
                            'source' => 'Битрикс 24',
                            'currency' => $row[6],
                            'supplier' => $row[2],
                            'brand' => $row[3],
                            'price' => $row[5],
                            'delivery_time' => $row[4],
                            'weight' => $row[7],
                            'product_id' => $productId,
                        ];
                    }
                }

                // Вставка новых вариаций
                if (!empty($newVariations)) {
                    $db->createCommand()
                        ->batchInsert(ProductVariationTable::tableName(), [
                            'source', 'currency', 'supplier', 'brand',
                            'price', 'delivery_time', 'weight', 'product_id'
                        ], $newVariations)->execute();
                }

                // Освобождение ресурсов
                unset($spreadsheet, $worksheet);
                gc_collect_cycles();

                // Переподключение к БД, если соединение "отвалилось"
                if ($db->isActive === false) {
                    $db->close();
                    $db->open();
                }

            } else {
                $finish = true;
            }

        } while (!$finish);
    }
}