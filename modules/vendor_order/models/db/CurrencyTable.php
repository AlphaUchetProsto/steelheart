<?php

namespace app\modules\vendor_order\models\db;

use yii\db\ActiveRecord;

class CurrencyTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{currency}}';
    }

    /*public function getCurrency()
    {
        return $this->hasMany(CurrencyTable::class);
    }*/
}