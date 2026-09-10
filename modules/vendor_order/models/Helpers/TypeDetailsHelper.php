<?php

namespace app\modules\vendor_order\models\Helpers;

use app\modules\products\models\Product;
use app\modules\vendor_order\models\ProductRow;
use app\modules\vendor_order\models\db\PropertyOptionsTable;
use yii\helpers\ArrayHelper;

class TypeDetailsHelper
{
    protected static $_instance = [];

    /**
     * Определяет id бренда по текстовому описанию
     *
     * @param string $name Название бренда
     * @throws \Exception Если не удалось распознать бренд
     */

    public static function getCollection()
    {
        $items = PropertyOptionsTable::find()->all();
        $items = ArrayHelper::map($items, 'id', 'name');

        return $items;


//        throw new \Exception("Не удалось определить бренд: '{$name}, обратитесь к администратору.'");
    }
}
