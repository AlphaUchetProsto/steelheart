<?php

namespace app\modules\vendor_order\models\db;

use yii\db\ActiveRecord;

class ProductRowVariationTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{deal_product_row_variation}}';
    }

    public function getVariation()
    {
        return $this->hasOne(ProductVariationTable::class, ['id' => 'variation_id']);
    }
}