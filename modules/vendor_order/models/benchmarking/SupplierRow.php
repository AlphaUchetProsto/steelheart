<?php

namespace app\modules\vendor_order\models\benchmarking;

use app\models\logger\DebugLogger;
use app\modules\vendor_order\models\BenchmarkingRow;
use app\modules\vendor_order\models\Client;
use app\modules\vendor_order\models\db\BenchmarkingRowVariationTable;
use app\modules\vendor_order\models\db\PropertyOptionsTable;
use app\modules\vendor_order\models\db\ProductVariationTable;
use app\modules\vendor_order\models\db\CurrencyTable;
use app\modules\vendor_order\models\ProductModel;
use Tightenco\Collect\Support\Collection;
use yii\base\BaseObject;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class SupplierRow extends Model
{
    public $id;
    public $supplier;
    public $price;
    public $productName;
    public $productRowId;
    public $deliveryTime;
    public $source;
    public $productId;
    public $currency;
    public $variationId;
    public $brand;
    public $weight;
    public $typeDetailId;
    public $quantity;

    public function rules()
    {
        return [
            [['currency', 'quantity'], 'number'],
            [['supplier', 'price', 'productName', 'deliveryTime', 'source', 'brand'], 'string'],
            [['productRowId', 'productId', 'id', 'variationId', 'weight', 'typeDetailId'], 'number'],
            [['price', 'id', 'variationId'], 'default', 'value' => 0],
            [['source'], 'default', 'value' => 'База данных'],
        ];
    }

    public function getCollectionTypeDetail()
    {
        $items = PropertyOptionsTable::find()->all();
        $items = ArrayHelper::map($items, 'id', 'name');

        return $items;
    }

    public function getCurrency()
    {
        $items = CurrencyTable::find()->all();
        $items = ArrayHelper::map($items, 'id', 'code');

        return $items;
    }

    public static function getList(int $productRowId)
    {
        $items = new Collection();

        $variations = BenchmarkingRowVariationTable::find()->where(['=', 'benchmarking_row_id', $productRowId])->all();

        foreach ($variations as $benchmarkingVariation)
        {
            $model = new self();
            $model->id = $benchmarkingVariation->id;
            $model->supplier = $benchmarkingVariation->productVariation->supplier;
            $model->price = $benchmarkingVariation->productVariation->price;
            $model->productName = $benchmarkingVariation->product->name;
            $model->productRowId = $benchmarkingVariation->benchmarking_row_id;
            $model->deliveryTime = $benchmarkingVariation->productVariation->delivery_time;
            $model->source = $benchmarkingVariation->productVariation->source;
            $model->currency = $benchmarkingVariation->productVariation->currency;
            $model->brand = $benchmarkingVariation->productVariation->brand;
            $model->productId = $benchmarkingVariation->product->id;
            $model->variationId = $benchmarkingVariation->variation_id;
            $model->weight = $benchmarkingVariation->productVariation->weight;
            $model->typeDetailId = $benchmarkingVariation->productVariation->type_detail_id;
            $model->quantity = $benchmarkingVariation->productVariation->quantity;

            $items->push($model);
        }

        return $items->toArray();
    }

    public function save():void
    {
        if ($this->supplier && $this->deliveryTime && $this->currency) {
            $productRow = BenchmarkingRowVariationTable::findOne($this->id);

            if (!$productRow) {
                $productRow = new BenchmarkingRowVariationTable();
                $variation = ProductVariationTable::find()->where([
                                                                                  'delivery_time' => $this->deliveryTime,
                                                                                  'supplier' => $this->supplier,
                                                                                  'price' => $this->price,
                                                                                  'product_id' => $this->productId,
                                                                                  'brand' => $this->brand,
                                                                                  'currency' => $this->currency,
                                                                              ])->one();

                if (!$variation) {
                    $variation = new ProductVariationTable();
                }
            } else {
                $variation = $productRow->productVariation;
            }

            $variation->source = $this->source;
            $variation->supplier = $this->supplier;
            $variation->price = $this->price;
            $variation->delivery_time = $this->deliveryTime;
            $variation->brand = $this->brand;
            $variation->currency = $this->currency;
            $variation->product_id = $this->productId;
            $variation->weight = $this->weight;
            $variation->type_detail_id = $this->typeDetailId;
            $variation->quantity = $this->quantity;
            $variation->save();

            $productRow->product_id = $this->productId;
            $productRow->benchmarking_row_id = $this->productRowId;
            $productRow->variation_id = $variation->id;
            $productRow->save();
        }
    }

    public static function findOne($id)
    {
        $benchmarkingVariation = BenchmarkingRowVariationTable::findOne($id);

        $model = new self();
        $model->id = $benchmarkingVariation->id;
        $model->supplier = $benchmarkingVariation->productVariation->supplier;
        $model->price = $benchmarkingVariation->productVariation->price;
        $model->productName = $benchmarkingVariation->product->name;
        $model->productRowId = $benchmarkingVariation->benchmarking_row_id;
        $model->deliveryTime = $benchmarkingVariation->productVariation->delivery_time;
        $model->source = $benchmarkingVariation->productVariation->source;
        $model->currency = $benchmarkingVariation->productVariation->currency;
        $model->brand = $benchmarkingVariation->productVariation->brand;
        $model->productId = $benchmarkingVariation->product->id;
        $model->variationId = $benchmarkingVariation->variation_id;
        $model->weight = $benchmarkingVariation->productVariation->weight;
        $model->typeDetailId = $benchmarkingVariation->productVariation->type_detail_id;
        $model->quantity = $benchmarkingVariation->productVariation->quantity;

        return $model;
    }

    public static function instanceRow($id)
    {
        $productRow = BenchmarkingRow::getOne($id);

        $model = new self();
        $model->productName = $productRow->getProduct()->name;
        $model->productRowId = $productRow->id;
        $model->productId = $productRow->productId;

        if ($model->validate()) {
            return $model;
        }

        return false;
    }

    public static function collectFromArray(array $data)
    {
        $result = [];

        foreach ($data as $row)
        {
            $model = new static();
            if($model->load(['SupplierRow' => $row]) && $model->validate()) {
                $result[] = $model;
            }
        }

        return $result;
    }

    public function delete()
    {
        $productRow = BenchmarkingRowVariationTable::findOne($this->id);
        $productRow->delete();

        $variation = ProductVariationTable::findOne($this->variationId);
        $variation->delete();
    }
}