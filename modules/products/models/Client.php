<?php

namespace app\modules\products\models;

use app\models\bitrix\app\Client as App;

class Client extends App
{
    public static function getConfigPath()
    {
        return '/products/config/config.php';
    }
}