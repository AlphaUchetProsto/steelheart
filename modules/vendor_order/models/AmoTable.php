<?php

namespace app\modules\vendor_order\models;

use yii\db\ActiveRecord;

class AmoTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{deals}}';
    }

    public function getFullNote()
    {
        return trim($this->note_first . "\n" . $this->note_second  . "\n" . $this->note_third  . "\n" . $this->note_fourth  . "\n" . $this->note_fifth  . "\n");
    }
}