<?php

namespace app\modules\import_product\models;

use app\modules\vendor_order\models\db\ProductTable;
use app\modules\vendor_order\models\db\ProductVariationTable;
use yii\base\Model;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yii;
use app\modules\vendor_order\models\Helpers\{CurrencyHelper, BrandHelper};
use app\modules\vendor_order\models\db\CurrencyTable;
use yii\db\Exception;

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

        $inputFileType = IOFactory::identify($filePath);
        $reader = IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);

        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $startRow = 2;
            $chunkSize = 500;
            $finish = false;

            do {
                $chunkFilter->setRows($startRow, $chunkSize);

                $spreadsheet = $reader->load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();

                $highestColumn = $worksheet->getHighestDataColumn();
                $nextRange = $startRow + $chunkSize - 1;
                $range = "A{$startRow}:{$highestColumn}{$nextRange}";

                $data = $worksheet->rangeToArray($range, null, true, true, false);

                // Очистка памяти
                unset($spreadsheet, $worksheet);
                gc_collect_cycles();

                $data = array_filter($data, function ($item) {
                    if (empty($item[0]) || $item[4] === null || $item[5] === null) {
                        return false;
                    }
                    $price = str_replace(',', '.', $item[5]);
                    return is_numeric($price);
                });

                if (empty($data)) {
                    $finish = true;
                } else {
                    // -------- Работаем с chunk --------
                    $productArticles = array_column($data, 0);
                    $productArticles = array_map(function ($article) {
                        return mb_strtolower(trim($article));
                    }, $productArticles);


                    // Загружаем продукты по этому чанку
                    $existingProducts = ProductTable::find()
                        ->select(['id', 'article'])
                        ->where(['article' => $productArticles])
                        ->indexBy('article')
                        ->asArray()
                        ->all();

                    $newProducts = [];
                    $seenArticles = [];

                    foreach ($data as $row) {
                        $article = trim($row[0]);
                        $article = mb_strtolower($article);

                        $name = $row[1] ?: $article;

                        if (!isset($existingProducts[$article]) && !isset($seenArticles[$article])) {
                            $newProducts[] = [$name, $article];
                            $seenArticles[$article] = true; // помечаем, что артикул уже добавлен
                        }
                    }

                    if (!empty($newProducts)) {
                        $db->createCommand()
                            ->batchInsert(ProductTable::tableName(), ['name', 'article'], $newProducts)
                            ->execute();

                        // Перезагружаем после вставки
                        $existingProducts = ProductTable::find()
                            ->select(['id', 'article'])
                            ->where(['article' => $productArticles])
                            ->indexBy('article')
                            ->asArray()
                            ->all();
                    }

                    // Вариации
                    $newVariations = [];

                    foreach ($data as $row) {
                        $article = trim($row[0]);
                        $article = mb_strtolower($article);

                        $productId = $existingProducts[$article]['id'];

                        $exists = ProductVariationTable::find()
                            ->where(['supplier' => $row[2], 'product_id' => $productId])
                            ->exists();

                        if (!$exists) {

                            $currencyCode = CurrencyHelper::detectCurrencyCodeByName($row[6]);
                            $currency = CurrencyTable::find()->where(['code' => $currencyCode])->all();

                            if (empty($currency) && !empty($currencyCode)) {
                                throw new Exception("Валюта - {$currencyCode}, не существует в БД. Обратитесь к администратору.");
                            }

                            if (count($currency) > 1) {
                                throw new Exception("По валюте - {$currencyCode}, больше одной записи в БД. Обратитесь к администратору.");
                            }

                            $brandId = BrandHelper::getBrandIdByName($row[3]);

                            $newVariations[] = [
                                'source' => 'Битрикс 24',
                                'currency' => $currency[0]->id ?? null,
                                'supplier' => $row[2],
                                'brand' => $brandId,
                                'price' => str_replace(',', '.', $row[5]),
                                'delivery_time' => $row[4],
                                'weight' => $row[7],
                                'product_id' => $productId,
                            ];
                        }
                    }

                    if (!empty($newVariations)) {
                        $db->createCommand()
                            ->batchInsert(ProductVariationTable::tableName(), [
                                'source', 'currency', 'supplier', 'brand',
                                'price', 'delivery_time', 'weight', 'product_id'
                            ], $newVariations)->execute();
                    }

                    $startRow += $chunkSize;
                }

            } while (!$finish);

            $transaction->commit();

        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw new Exception($e->getMessage());
        }
    }
}
