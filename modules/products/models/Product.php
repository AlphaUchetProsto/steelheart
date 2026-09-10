<?php

namespace app\modules\products\models;

use app\models\bitrix\Bitrix;

class Product
{
    public static function getCurrency()
    {
        $bitrix = Bitrix::Bx24init();

        $response = $bitrix->request('crm.currency.list');

        return collect($response)->mapWithKeys(function ($item) {
            return [$item['CURRENCY'] => preg_replace('~(\#\s|\#$)~', '', $item['FORMAT_STRING'])];
        });
    }
}