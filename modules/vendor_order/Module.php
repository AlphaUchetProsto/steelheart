<?php

namespace app\modules\vendor_order;

/**
 * admin module definition class
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\vendor_order\controllers';

    public function init()
    {
        parent::init();

        \Yii::$app->params['modules']['vendor_order']['token'] = 'a00a618c-fb62-4462-9bcd-3e1c77e40396';
        \Yii::$app->params['modules']['vendor_order']['ocuser'] = 'Default';
        \Yii::$app->params['modules']['vendor_order']['octoken'] = 'lqph9GkXGjogx0iMgB5JXDTjArRPJAgQNpPPd9OD1noWBSc0URPSnbgvlyg4kPqCgPLpE4WlfU3zmb6zTuDGGaUSYjrUSGhBg2NJWtbAL6uF0zLXVYRhwNUZS2GFLVpn3L9on8MzmFhu9PjtDRUePr1OePtOuu2NTmPRGEGCELnUK9DO9HLa7coojM58l4sVGgrR6t76GGMuLeHpLUnha9lNraStAoAQDfcrO8bwktLTfz01azZZP4utNjLzzlrz';
    }
}
