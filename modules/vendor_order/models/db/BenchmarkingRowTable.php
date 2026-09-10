<?php

namespace app\modules\vendor_order\models\db;

use yii\db\ActiveRecord;

class BenchmarkingRowTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{benchmarking_row}}';
    }

    public function getProduct()
    {
        return $this->hasOne(ProductTable::class, ['id' => 'product_id']);
    }

    public function getProductRow()
    {
        return $this->hasOne(ProductRowTable::class, ['id' => 'product_row_id']);
    }

    public function getVariations()
    {
        return $this->hasMany(BenchmarkingRowVariationTable::class, ['benchmarking_row_id' => 'id']);
    }
}