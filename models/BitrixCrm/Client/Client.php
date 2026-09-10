<?php

namespace app\models\BitrixCrm\Client;

use app\models\BitrixCrm\Client\Request\Webhook;
use app\models\BitrixCrm\Client\Request\App;
use app\models\BitrixCrm\Client\Config;

class Client
{
    protected Config $config;

    public function __construct(Config $config = null)
    {
        if (!$config) {
            $config = new Config(['restUrl' => \Yii::$app->params['bitrix']['webhook_url']]);
        }

        $this->config = $config;
    }

    protected function buildRequest()
    {
        if ($this->config->accessToken) {
            return new App($this->config);
        }

        return new Webhook($this->config);
    }
    
    public function crm()
    {
        return new \app\models\BitrixCrm\Client\Crm\Client($this->config);
    }

    public function catalog()
    {
        return new \app\models\BitrixCrm\Client\Catalog\Client($this->config);
    }

    public function tasks()
    {
        return new \app\models\BitrixCrm\Client\Tasks\Tasks($this->config);
    }

    public function api()
    {
        return $this->buildRequest();
    }
}