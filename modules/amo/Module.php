<?php

namespace app\modules\amo;

/**
 * amo module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\amo\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        \Yii::$app->params['modules']['amo']['token'] = '53d78dda-e101-4577-b4cc-1b74b0cab994';

        // custom initialization code goes here
    }
}
