<?php

namespace app\modules\vendor_order\models\db;

use yii\db\ActiveRecord;

class ProductRowTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{deal_product_row}}';
    }

    public function getProduct()
    {
        return $this->hasOne(ProductTable::class, ['id' => 'product_id']);
    }

    public function getVariation()
    {
        return $this->hasMany(ProductRowVariationTable::class, ['product_row_id' => 'id']);
    }
}