<?php

namespace app\modules\products\controllers;

use Yii;
use yii\web\Controller;
use app\models\logger\DebugLogger;
use app\modules\products\models\Client;
use app\modules\products\models\Product;
use app\modules\products\models\Entity;

/**
 * Main controller for the `products` module
 */
class MainController extends Controller
{
    public $layout = 'main';

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $currency = Product::getCurrency();

        $dealId = json_decode(Yii::$app->request->post('PLACEMENT_OPTIONS'), true)['ID'];
//        $dealId = 10083;

        $products = Entity::getProducts($dealId);

        return $this->render('index', ['currency' => $currency, 'dealId' => $dealId, 'products' => $products]);
    }

    public function actionSaveProducts()
    {
        $post = Yii::$app->request->post();

        $logger = DebugLogger::instance("save-products");
        $logger->save($post, $post, "Товары");

        if (!isset($post['products'])) {
            $post['products'] = [];
        }

        Entity::saveProducts($post);

        return 200;
    }

    public function actionInstall()
    {
        $auth = collect(Yii::$app->request->post());

        $logger = DebugLogger::instance("install");
        $logger->save($auth, $auth, "Данные приложения");

        if(Yii::$app->request->isPost)
        {
            $app = new Client($auth);
            $app->updateConfig();

            $result = $app->request('placement.bind', [
                'PLACEMENT' => 'CRM_DEAL_DETAIL_TAB',
                'HANDLER' => '',
                'LANG_ALL' => [
                    'ru' => [
                        'TITLE' => 'Товары',
                    ],
                ],
            ]);

            $logger->save($result, $result, 'Результат встраивания приложения CRM_DEAL_DETAIL_TAB');
        }

        return $this->render("install");
    }
}
