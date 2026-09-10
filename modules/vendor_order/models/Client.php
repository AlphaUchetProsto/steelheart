<?php

namespace app\modules\vendor_order\models;

use App\HTTP\HTTP;
use app\models\BitrixCrm\Client\Response;
use Yii;

class Client
{
    public $access_token;
    public $refresh_token;
    public $client_endpoint;
    public $http;
    public $client_id;
    public $client_secret;

    public static function instance($params = [])
    {
        $model = new static();

        if(is_null(static::getConfigPath()))
        {
            throw new \Exception('Не указан путь до файла конфига');
        }

        $appsConfig = require static::getConfigPath();

        $model->http = new HTTP();
        $model->http->throttle = 2;
        $model->http->useCookies = false;

        $params = collect($params)->mapWithKeys(function ($item, $key){
            return [mb_strtolower($key) => $item];
        });

        if($params->has("auth_id"))
        {
            $model->access_token = $params["auth_id"] ?? $appsConfig["Доступы"]["access_token"];
            $model->refresh_token = $params["refresh_id"] ?? $appsConfig["Доступы"]["refresh_token"];
        }
        else
        {
            $model->access_token = $params["access_token"] ?? $appsConfig["Доступы"]["access_token"];
            $model->refresh_token = $params["refresh_token"] ?? $appsConfig["Доступы"]["refresh_token"];
        }

        $model->client_id = $appsConfig["Доступы"]["client_id"];
        $model->client_secret = $appsConfig["Доступы"]["client_secret"];
        $model->client_endpoint = 'https://b24-ri3oec.bitrix24.ru/rest/';

        return $model;
    }

    protected static function getConfigPath():?string
    {
        return \Yii::getAlias('@app') . '/modules/vendor_order/config/config.php';
    }

    public function request($method, $params = [])
    {
        $url = "{$this->client_endpoint}/{$method}.json";
        $params["auth"] = $this->access_token;

        $response = $this->http->request($url, "POST", $params);

        if(isset($response["error"]) && $response["error"] == "expired_token")
        {
            $this->refreshToken();

            $response = $this->request($method, $params);
        }

        return $response;
    }

    public function refreshToken():void
    {
        $params = [
            "grant_type" => "refresh_token",
            "client_id" => $this->client_id,
            "client_secret" => $this->client_secret,
            "refresh_token" => $this->refresh_token,
        ];

        $response = $this->http->request("https://oauth.bitrix.info/oauth/token/", "POST", $params);

        $this->access_token = $response['access_token'];
        $this->refresh_token = $response['refresh_token'];

        $this->updateConfig();
    }

    public function updateConfig()
    {
        $appsConfig = require static::getConfigPath();

        if(!empty($appsConfig))
        {
            foreach ($appsConfig["Доступы"] as $key => &$value)
            {
                if(property_exists($this, $key))
                {
                    $appsConfig["Доступы"][$key] = $this->$key;
                }
            }

            $appsConfig = var_export($appsConfig, true);

            file_put_contents(static::getConfigPath(), "<?php\n return {$appsConfig};\n");
        }
    }

    public function buildCommand($method, $params = [])
    {
        $command = "{$method}";

        if(!empty($params))
        {
            $command .= "?" . http_build_query($params);
        }

        return $command;
    }

    public function batchRequest($commands, $halt = true)
    {
        $url = "{$this->client_endpoint}/batch";

        $response = $this->http->request($url, "POST", ["cmd" => $commands, "halt" => $halt, 'auth' => $this->access_token]);

        if(isset($response["error"]) && $response["error"] == "expired_token")
        {
            $this->refreshToken();

            $response = $this->batchRequest($commands, $halt);
        }

        return $response;
    }

    public function multipleBatchRequest(array $batch, $chunkSize = 50)
    {
        $batch = collect($batch)->chunk($chunkSize)->toArray();

        foreach ($batch as $row)
        {
            $this->batchRequest($row);
        }

        return true;
    }

    public function install()
    {
        $commands[] = $this->buildCommand('placement.bind', [
            'PLACEMENT' => 'CRM_DEAL_DETAIL_TAB',
            'HANDLER' => 'https://steelheart.uchetprosto.ru/vendor-order/main/index?token=a00a618c-fb62-4462-9bcd-3e1c77e40396',
            'LANG_ALL' => [
                'ru' => [
                    'TITLE' => 'Заказ поставщику',
                ],
            ],
        ]);

        $commands[] = $this->buildCommand('placement.bind', [
            'PLACEMENT' => 'CRM_DEAL_DETAIL_TAB',
            'HANDLER' => 'https://steelheart.uchetprosto.ru/vendor-order/main/benchmarking?token=a00a618c-fb62-4462-9bcd-3e1c77e40396',
            'LANG_ALL' => [
                'ru' => [
                    'TITLE' => 'Проценка',
                ],
            ],
        ]);

        $commands[] = $this->buildCommand('entity.add', ['ENTITY' => 'productrow', 'NAME' => 'Заказ поставщика', 'ACCESS' => ['AU' => 'X']]);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'productrow', 'PROPERTY' => 'id', 'NAME' => 'ID', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'productrow', 'PROPERTY' => 'name', 'NAME' => 'NAME', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'productrow', 'PROPERTY' => 'productId', 'NAME' => 'productId', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'productrow', 'PROPERTY' => 'type', 'NAME' => 'type', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'productrow', 'PROPERTY' => 'quantity', 'NAME' => 'quantity', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'productrow', 'PROPERTY' => 'brand', 'NAME' => 'brand', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'productrow', 'PROPERTY' => 'typeMachine', 'NAME' => 'typeMachine', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'productrow', 'PROPERTY' => 'dealId', 'NAME' => 'dealId', 'TYPE' => 'S']);

        $commands[] = $this->buildCommand('entity.add', ['ENTITY' => 'supplier', 'NAME' => 'Поставщики', 'ACCESS' => ['AU' => 'X']]);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'supplier', 'PROPERTY' => 'productRowId', 'NAME' => 'productRowId', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'supplier', 'PROPERTY' => 'productName', 'NAME' => 'productName', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'supplier', 'PROPERTY' => 'price', 'NAME' => 'price', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'supplier', 'PROPERTY' => 'deliveryTime', 'NAME' => 'deliveryTime', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'supplier', 'PROPERTY' => 'supplier', 'NAME' => 'supplier', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'supplier', 'PROPERTY' => 'siteName', 'NAME' => 'supplier', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'supplier', 'PROPERTY' => 'manufacturer', 'NAME' => 'manufacturer', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'supplier', 'PROPERTY' => 'status', 'NAME' => 'status', 'TYPE' => 'S']);

        $commands[] = $this->buildCommand('entity.add', ['ENTITY' => 'benchmarking', 'NAME' => 'Просчет', 'ACCESS' => ['AU' => 'X']]);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'benchmarking', 'PROPERTY' => 'name', 'NAME' => 'NAME', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'benchmarking', 'PROPERTY' => 'productId', 'NAME' => 'productId', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'benchmarking', 'PROPERTY' => 'brand', 'NAME' => 'brand', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'benchmarking', 'PROPERTY' => 'typeMachine', 'NAME' => 'typeMachine', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'benchmarking', 'PROPERTY' => 'dealId', 'NAME' => 'dealId', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'benchmarking', 'PROPERTY' => 'productRowId', 'NAME' => 'productRowId', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'benchmarking', 'PROPERTY' => 'status', 'NAME' => 'status', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'benchmarking', 'PROPERTY' => 'supplier', 'NAME' => 'supplier', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'benchmarking', 'PROPERTY' => 'request', 'NAME' => 'request', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'benchmarking', 'PROPERTY' => 'ssuppliers', 'NAME' => 'ssuppliers', 'TYPE' => 'S']);

        $commands[] = $this->buildCommand('entity.add', ['ENTITY' => 'b_supplier', 'NAME' => 'Поставщики', 'ACCESS' => ['AU' => 'X']]);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'b_supplier', 'PROPERTY' => 'productRowId', 'NAME' => 'productRowId', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'b_supplier', 'PROPERTY' => 'productName', 'NAME' => 'productName', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'b_supplier', 'PROPERTY' => 'price', 'NAME' => 'price', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'b_supplier', 'PROPERTY' => 'deliveryTime', 'NAME' => 'deliveryTime', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'b_supplier', 'PROPERTY' => 'supplier', 'NAME' => 'supplier', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'b_supplier', 'PROPERTY' => 'siteName', 'NAME' => 'supplier', 'TYPE' => 'S']);
        $commands[] = $this->buildCommand('entity.item.property.add', ['ENTITY' => 'b_supplier', 'PROPERTY' => 'productId', 'NAME' => 'productId', 'TYPE' => 'S']);

        return $this->batchRequest($commands);
    }
}