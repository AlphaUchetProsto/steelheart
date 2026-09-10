<?php

namespace app\modules\vendor_order\models\db;

use yii\db\ActiveRecord;

class PropertyOptionsTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{property_options}}';
    }
}