<?php

namespace app\modules\amo\models;

use yii\db\ActiveRecord;

class AmoTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{deals}}';
    }
}