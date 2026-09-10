<?php

namespace app\models\yandex\client;

use app\models\yandex\client\Request;
use app\models\yandex\Disk;

class Client
{
    protected $token;

    public static function instance($token): self
    {
        $model = new self();

        return $model->setToken($token);
    }

    public function setToken(string $token)
    {
        $this->token = $token;

        return $this;
    }


    public function disk()
    {
        $request = $this->buildRequest('https://cloud-api.yandex.net/v1/');

        return new Disk($request);
    }

    private function buildRequest(string $restUrl)
    {
        return new Request($restUrl, $this->token);
    }
}