<?php

namespace app\modules\vendor_order\models;

use app\modules\vendor_order\models\Client;
use app\modules\vendor_order\models\db\ProductRowTable;
use app\modules\vendor_order\models\db\ProductRowVariationTable;
use app\modules\vendor_order\models\db\ProductVariationTable;
use app\modules\vendor_order\models\db\PropertyOptionsTable;
use app\modules\vendor_order\models\db\CurrencyTable;
use app\modules\vendor_order\models\ProductModel;
use Tightenco\Collect\Support\Collection;
use yii\base\BaseObject;
use yii\base\Model;
use yii\helpers\ArrayHelper;

use function Symfony\Component\String\u;

class SupplierRow extends Model
{
    public $id;
    public $supplier;
    public $price;
    public $brand;
    public $productName;
    public $productRowId;
    public $deliveryTime;
    public $source;
    public $manufacturer;
    public $status;
    public $weight;
    public $typeDetailId;
    public $currency;
    public $quantity;

    public function rules()
    {
        return [
            [['id', 'currency'], 'number'],
            [['supplier', 'productName', 'deliveryTime', 'source', 'manufacturer', 'status', 'brand'], 'string'],
            [['price'], 'double'],
            [['productRowId', 'weight', 'typeDetailId', 'quantity'], 'number'],
            [['id', 'price', 'weight'], 'default', 'value' => 0],
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

    
    public static function instanceRow($id)
    {
        $productRow = ProductRow::getOne($id);

        $model = new self();
        $model->productName = $productRow->getProduct()->name;
        $model->productRowId = $productRow->id;
        
        if ($model->validate()) {
            return $model;
        }

        return false;
    }

    public static function getList(int $productRowId)
    {
        $items = new Collection();

        $productRow = ProductRowTable::findOne($productRowId);

        foreach ($productRow->variation as $variation)
        {
            $attributes = $variation->getAttributes();
            $attributes = collect($attributes)->mapWithKeys(fn($value, $key) => [u($key)->camel()->toString() => $value])->toArray();

            $items->push($attributes);
        }

        return self::collectFromArray($items->toArray());
    }

    public static function collectFromArray(array $data)
    {
        $result = [];

        foreach ($data as $row)
        {
            if (!empty($row)) {
                $model = new self();

                if($model->load(['SupplierRow' => $row]) && $model->validate()) {
                    $result[] = $model;
                }
            }
        }

        return $result;
    }

    public function save()
    {
        if ($this->supplier && $this->deliveryTime) {
            if ($this->id > 0) {
                $productRowVariationTable = ProductRowVariationTable::findOne($this->id);
            } else  {
                $productRowVariationTable = new ProductRowVariationTable();
            }


            $productRowVariationTable->supplier = $this->supplier;
            $productRowVariationTable->price = $this->price;
            $productRowVariationTable->product_name = $this->productName;
            $productRowVariationTable->delivery_time = $this->deliveryTime;
            $productRowVariationTable->source = $this->source;
            $productRowVariationTable->manufacturer = $this->manufacturer;
            $productRowVariationTable->status = $this->status;
            $productRowVariationTable->product_row_id = $this->productRowId;
            $productRowVariationTable->weight = $this->weight;
            $productRowVariationTable->type_detail_id = $this->typeDetailId;
            $productRowVariationTable->currency = $this->currency;
            $productRowVariationTable->quantity = $this->quantity;
            $productRowVariationTable->brand = $this->brand;

            if ($productRowVariationTable->variation_id) {
                $variation = $productRowVariationTable->variation;
            } else {
                $variation = new ProductVariationTable();
            }

//            $variation->brand = $this->brand;
            $variation->source = $this->source;
            $variation->product_id = ProductRowTable::findOne($this->productRowId)->product_id;
            $variation->supplier = $this->supplier;
            $variation->price = $this->price;
            $variation->delivery_time = $this->deliveryTime;
            $variation->weight = $this->weight;
            $variation->type_detail_id = $this->typeDetailId;
            $variation->currency = $this->currency;
            $variation->quantity = $this->quantity;
            $variationSaved = $variation->save();

            $productRowVariationTable->variation_id = $variation->id;
            $productRowVariationSaved = $productRowVariationTable->save();

            return $variationSaved && $productRowVariationSaved;
        }else{
            return false;
        }
    }
}