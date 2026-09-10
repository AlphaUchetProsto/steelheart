<?php

namespace app\modules\vendor_order\models\db;

use yii\db\ActiveRecord;

class ProductTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{product}}';
    }

    public function getProductRows()
    {
        return $this->hasMany(ProductRowTable::class, ['product_id' => 'id']);
    }

    public function getVariation()
    {
        return $this->hasMany(ProductVariationTable::class, ['product_id' => 'id']);
    }
}