<?php

namespace app\modules\vendor_order\models;

use app\models\logger\DebugLogger;
use app\modules\vendor_order\models\Client;
use app\modules\vendor_order\models\db\ProductTable;
use app\modules\vendor_order\models\db\ProductRowTable;
use app\modules\vendor_order\models\ProductModel;
use app\modules\vendor_order\models\db\BrandTable;
use Tightenco\Collect\Support\Collection;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class ProductRow extends Model
{
    public $id;
    public $name;
    public $article;
    public $productId;
    public $type;
    public $price;
    public $quantity;
    public $brand;
    public $typeMachine;
    public $dealId;

    protected $product;
    protected $variations = [];
    protected static $_static = [];

    public function rules()
    {
        return [
            [['id', 'productId', 'type', 'quantity', 'brand', 'typeMachine', 'price'], 'number'],
            [['id', 'productId', 'quantity', 'dealId'], 'default', 'value' => 0],
            [['name', 'article'], 'string'],
        ];
    }

    public function getVariations()
    {
        if (!$this->variations && $this->id) {
            $variations = \app\modules\vendor_order\models\SupplierRow::getList($this->id);

            $model = new \app\modules\vendor_order\models\SupplierRow();
            $model->productName = $this->getProduct()->name;
            $model->productRowId = $this->id;

            if ($model->validate()) {
                $variations[] = $model;
            }

            $this->variations = $variations;

        }

        return $this->variations;
    }

    public function getProductId()
    {
        return !empty($this->product) ? $this->product->id : $this->productId;
    }

    public function getProductName()
    {
        return !empty($this->product) ? $this->product->name : $this->name;
    }

    public function getProductArticle()
    {
        return !empty($this->product) ? $this->product->article : $this->article;
    }

    public function getProduct()
    {
        return $this->product ?? new ProductTable();
    }

    public static function findByDealID($dealId)
    {
        $collectionProductRows = new CollectionProductRow();

        $productRow = ProductRowTable::find()->where(['=', 'deal_id', $dealId])->all();

        foreach ($productRow as $index => $row)
        {
            $model = new self();
            $model->id = $row->id;
            $model->name = $row->product->name;
            $model->article = $row->product->article;
            $model->productId = $row->product_id;
            $model->type = $row->type;
            $model->quantity = $row->quantity;
            $model->brand = $row->brand;
            $model->price = $row->price;
            $model->typeMachine = $row->type_machine;
            $model->dealId = $row->deal_id;
            $model->setProduct($row->product);
            $model->validate();

            $collectionProductRows->push($model);
        }

        return $collectionProductRows;
    }

    public function isNewRow()
    {
        return $this->id == 0;
    }

    public static function collectFromArray(array $data)
    {
        $result = [];

        foreach ($data as $row)
        {
            $model = new self();

            if($model->load(['ProductRow' => $row]) && $model->validate()) {

                if (!empty($row['variations'])) {
                    $model->variations = SupplierRow::collectFromArray($row['variations']);
                }

                $result[] = $model;
            }
        }

        return $result;
    }

    public function multipleUpdate1(string $data):void
    {
//        $data = json_decode($data, true);
        $data = self::collectFromArray($data);

        $newProducts = collect($data)->filter(function ($item){return $item->productId == 0;});

        if ($newProducts->isNotEmpty()) {
            foreach ($newProducts as $index => $product)
            {
                $model = new ProductTable();
                $model->name = $product->name ?? $product->article;
                $model->article = $product->article;
                $model->save();

                $data[$index]->productId = $model->id;
            }
        }

        foreach ($data as $index => $row)
        {
            $model = $row->id > 0 ? ProductRowTable::findOne($row->id) : new ProductRowTable();

            $model->product_id = $row->productId;
            $model->type = $row->type;
            $model->quantity = $row->quantity;
            $model->brand = $row->brand;
            $model->type_machine = $row->typeMachine;
            $model->deal_id = $row->dealId;

            $model->save();

            $row->updateVariations();
        }
    }

    public function multipleUpdate(string $data)
    {
        $result = [];

        $data = json_decode($data, true);
        $result['$data_before'] = $data;
        $data = self::collectFromArray($data);
        $result['$data_after'] = $data;

//        $newProducts = collect($data)->filter(function ($item){return $item->productId == 0;});
        $newProducts = collect($data)->filter(function ($item) {
            $productId = $item['productId'] ?? null;
            $article = $item['article'] ?? null;

            return (empty($productId) || $productId == 0) && !empty($article);
        });

        if ($newProducts->isNotEmpty()) {
            foreach ($newProducts as $index => $product)
            {
                $article = $product['article'];
                $existingProduct = ProductTable::findOne(['article' => $article]);

                if ($existingProduct) {
                    $data[$index]['productId'] = $existingProduct->id;
                } else {
                    $model = new ProductTable();
                    $model->name = $product['name'] ?? $product['article'];
                    $model->article = $product['article'];
                    $model->save();

                    $data[$index]->productId = $model->id;
                }

            }
        }

        foreach ($data as $index => $row)
        {
            $model = $row->id > 0 ? ProductRowTable::findOne($row->id) : new ProductRowTable();

            $model->product_id = $row->productId;
            $model->type = $row->type;
            $model->quantity = $row->quantity;
            $model->brand = $row->brand;
            if ($row->price >= 0){
                $model->price = $row->price;
            }
            $model->type_machine = $row->typeMachine;
            $model->deal_id = $row->dealId;

            $result['savedItems'][] = $model->save();

            if (!empty($row->productId) && !empty($row->name)) {
                $product = ProductTable::findOne($row->productId);
                if ($product) {
                    // Обновляем название если оно изменилось
                    if ($product->name !== $row->name) {
                        $product->name = $row->name;
                        $product->save();
                        $result['updated_product_names'][] = [
                            'productId' => $row->productId,
                            'oldName' => $product->name,
                            'newName' => $row->name,
                            'saved' => true
                        ];
                    }
                }
            }

            $result['items'][] = $model->toArray();
            $result['row'][] = $row->toArray();
            $result['variations'][] = $row->variations;
            $result['updatedVariations'][] = $row->updateVariations();
        }
        return $result;
    }

    public function updateVariations()
    {
        $result = [];
        foreach ($this->variations as $variation)
        {
//            if (!$variation->isNewRecord) {
//                $result[] = ['already_saved' => true];
//                continue;
//            }
//
            if ($variation->save()) {
                $result[] = true;
            } else {
                $result[] = false;
            }
//            $result[] = [
//                'type' => gettype($variation),
//                'class' => is_object($variation) ? get_class($variation) : 'not object',
//                'is_array' => is_array($variation),
//                'attributes' => $variation // посмотрим что внутри
//            ];
        }
        return $result;
    }
    
    public function deleteRow(int $id)
    {
        return ProductRowTable::findOne($id)->delete();
    }

    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    public function getCollectionBrand()
    {
        if (!isset(static::$_static['brand'])) {
//            $bitrix = new \app\models\BitrixCrm\Client\Client();
//            $fields = $bitrix->api()->request('crm.company.fields')->getResponse();
//            static::$_static['brand'] = ArrayHelper::map($fields['UF_CRM_1724304462480']['items'], 'ID', 'VALUE');


            static::$_static['brand'] = BrandTable::getAllForSelect();
        }

        return static::$_static['brand'] ?? [];
    }

    public function getCollectionTypeMachine()
    {
        $bitrix = new \app\models\BitrixCrm\Client\Client();
        $fields = $bitrix->api()->request('crm.company.fields')->getResponse();

        return ArrayHelper::map($fields['UF_CRM_1724304514706']['items'], 'ID', 'VALUE');
    }

    public function getCollectionTypePar()
    {
        return ['Оригинал', 'Аналог'];
    }

    public static function getOne($id)
    {
        $row = ProductRowTable::findOne($id);

        $model = new self();

        if ($model->fromArray($row)) {
            return $model;
        }

        return false;
    }

    public function fromArray($row)
    {
        $this->name = $row->product->name;
        $this->article = $row->product->article;
        $this->productId = $row->product_id;
        $this->type = $row->type;
        $this->quantity = $row->quantity;
        $this->brand = $row->brand;
        $this->typeMachine = $row->type_machine;
        $this->dealId = $row->deal_id;
        $this->setProduct($row->product);

        return $this->validate();
    }
}