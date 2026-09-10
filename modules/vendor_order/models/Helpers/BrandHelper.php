<?php

namespace app\modules\vendor_order\models\Helpers;

use app\modules\products\models\Product;
use app\modules\vendor_order\models\ProductRow;

class BrandHelper
{
    protected static $_instance = [];

    /**
     * Определяет id бренда по текстовому описанию
     *
     * @param string $name Название бренда
     * @throws \Exception Если не удалось распознать бренд
     */

    public static function getBrandIdByName(string $name = null): ?string
    {
        if (!$name) {
            return null;
        }

        $productRow = new ProductRow();
        $brands = $productRow->getCollectionBrand();
        
        foreach ($brands as $brandId => $brandName)
        {
            $brandName = trim($brandName);
            $brandName = mb_strtolower($brandName);

            $name = trim($name);
            $name = mb_strtolower($brandName);

            if ($brandName == $name) {
                return $brandId;
            }
        }


        throw new \Exception("Не удалось определить бренд: '{$name}, обратитесь к администратору.'");
    }
}
