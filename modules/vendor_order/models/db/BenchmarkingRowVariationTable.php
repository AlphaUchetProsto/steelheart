<?php

namespace app\modules\vendor_order\models\db;

use yii\db\ActiveRecord;

class BenchmarkingRowVariationTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{benchmarking_row_variation}}';
    }

    public function getProduct()
    {
        return $this->hasOne(ProductTable::class, ['id' => 'product_id']);
    }

    public function getProductVariation()
    {
        return $this->hasOne(ProductVariationTable::class, ['id' => 'variation_id']);
    }
}