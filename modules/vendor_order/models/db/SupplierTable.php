<?php

namespace app\modules\vendor_order\models\db;

use yii\db\ActiveRecord;

class SupplierTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{supplier}}';
    }
}