<?php

namespace app\modules\import_product;

use app\models\BitrixCrm\Client\Client;
use app\models\BitrixCrm\Client\Config;

/**
 * import-product module definition class
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\import_product\controllers';

    public function init()
    {
        parent::init();

        \Yii::$app->params['modules']['import_product']['token'] = 'a00a618c-fb62-4462-9bcd-3e1c77e40396';
    }
}
