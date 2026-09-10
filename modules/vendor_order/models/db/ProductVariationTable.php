<?php

namespace app\modules\vendor_order\models\db;

use yii\db\ActiveRecord;

class ProductVariationTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{product_variation}}';
    }

    public function getProduct()
    {
        return $this->hasOne(ProductTable::class, ['id' => 'product_id']);
    }
}