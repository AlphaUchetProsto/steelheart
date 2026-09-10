<?php

namespace app\models\bitrix\app;

use App\HTTP\HTTP;
use Yii;
use yii\base\Model;
use app\models\logger\DebugLogger;

abstract class Client extends Model
{
    protected $access_token;
    protected $refresh_token;
    protected $client_endpoint;
    protected $http;
    protected $client_id;
    protected $client_secret;

    protected static $config_path = '';

    public function __construct($params = [])
    {
        static::$config_path = Yii::getAlias("@modules") . static::getConfigPath();
        $appsConfig = static::getConfig();

        $params = collect($params)->mapWithKeys(function ($item, $key){
            return [mb_strtolower($key) => $item];
        });

        $this->http = new HTTP();
        $this->http->throttle = 2;
        $this->http->useCookies = false;

        if ($params->has("auth_id")) {
            $this->access_token = $params["auth_id"] ?? $appsConfig["Доступы"]["access_token"];
            $this->refresh_token = $params["refresh_id"] ?? $appsConfig["Доступы"]["refresh_token"];
        } else {
            $this->access_token = $params["access_token"] ?? $appsConfig["Доступы"]["access_token"];
            $this->refresh_token = $params["refresh_token"] ?? $appsConfig["Доступы"]["refresh_token"];
        }

        $this->client_id = $appsConfig["Доступы"]["client_id"];
        $this->client_secret = $appsConfig["Доступы"]["client_secret"];
        $this->client_endpoint = Yii::$app->params["bitrix"]["rest_url"];

        $action = Yii::$app->controller->action->id;
        $logger = DebugLogger::instance($action);
        $this->setLogger($logger);

        parent::__construct();
    }

    public function setLogger($logger)
    {
        if (!($logger instanceof \App\DebugLogger\DebugLoggerInterface)) {
            throw new Bitrix24APIException(
                "Объект класса логгера должен реализовывать интерфейс \App\DebugLogger\DebugLoggerInterface"
            );
        }
        $this->logger = $logger;
    }

    protected static function getConfig()
    {
        return require static::$config_path;
    }

    public function request($method, $params = [])
    {
        $finish = false;

        // Логирование запроса
        if (isset($this->logger)) {
            $jsonParams = urldecode($this->toJSON($params, true));
            $this->logger->save("ЗАПРОС: {$method}" . PHP_EOL . $jsonParams, $this);
        }

        while (!$finish) {
            $url = "{$this->client_endpoint}/{$method}.json";
            $params["auth"] = $this->access_token;
            $response = $this->http->request($url, "POST", $params);

            if (isset($response["error"]) && $response["error"] == "expired_token") {
                $this->refreshToken();
                $params["auth"] = $this->access_token;
            } elseif (isset($response['error']) && $response['error'] == 'QUERY_LIMIT_EXCEEDED') {
                sleep(1);
            } else {
                $finish = true;
            }
        }

        // Логирование ответа
        if (isset($this->logger)) {
            $jsonResponse = $this->toJSON($response, true);
            $this->logger->save("ОТВЕТ: {$method}" . PHP_EOL . $jsonResponse, $this);
        }

        return $response;
    }

    protected function refreshToken():void
    {
        $params = [
            "grant_type" => "refresh_token",
            "client_id" => $this->client_id,
            "client_secret" => $this->client_secret,
            "refresh_token" => $this->refresh_token,
        ];

        $response = $this->http->request("https://oauth.bitrix.info/oauth/token/", "POST", $params);

        $this->refresh_token = $response["refresh_token"];
        $this->access_token = $response["access_token"];
        $this->updateConfig();
    }

    public function updateConfig()
    {
        $appsConfig = static::getConfig();

        if(!empty($appsConfig))
        {
            foreach ($appsConfig["Доступы"] as $key => &$value)
            {
                if($this->canGetProperty($key))
                {
                    $appsConfig["Доступы"][$key] = $this->$key;
                }
            }

            $this->refresh_token = $appsConfig["Доступы"]["refresh_token"];
            $this->access_token = $appsConfig["Доступы"]["access_token"];

            $appsConfig = var_export($appsConfig, true);

            file_put_contents(static::$config_path, "<?php\n return {$appsConfig};\n");
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
        $finish = false;

        // Логирование запроса
        if (isset($this->logger)) {
            $jsonParams = urldecode($this->toJSON($commands, true));
            $this->logger->save("ЗАПРОС: {$jsonParams}");
        }

        while (!$finish) {
            $url = "{$this->client_endpoint}/batch";

            $response = $this->http->request($url, "POST", ["cmd" => $commands, "halt" => $halt, 'auth' => $this->access_token]);

            if (isset($response["error"]) && $response["error"] == "expired_token") {
                $this->refreshToken();

                $response = $this->batchRequest($commands, $halt);
            } elseif (!empty($this->last_response['result']['result_error']) && (in_array(array_values($response['result']['result_error'])[0]['error_description'], ['Method is blocked due to operation time limit.', 'Too many requests']))) {
                $timeNeedSleep = ($response['time']['operating_reset_at'] - strtotime('now')) / 1000;
                sleep($timeNeedSleep);

                $key = array_key_first($response['result']['result_error']);

                $newCommands = $commands;

                if ($key != array_key_first($commands)) {
                    foreach ($newCommands as $key1 => $command) {
                        if ($key1 != $key) {
                            unset($newCommands[$key1]);
                        } else {
                            break;
                        }
                    }
                }

                $response = $this->batchRequest($newCommands, $halt);
            } else {
                $finish = true;
            }
        }

        // Логирование ответа
        if (isset($this->logger)) {
            $jsonResponse = $this->toJSON($response, true);
            $this->logger->save("ОТВЕТ: batch.json" . PHP_EOL . $jsonResponse, $this);
        }

        return $response;
    }

    protected function toJSON($data, bool $prettyPrint = false): string
    {
        $encodeOptions = JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;

        if ($prettyPrint) {
            $encodeOptions |= JSON_PRETTY_PRINT;
        }

        $jsonParams = json_encode($data, $encodeOptions);
        if ($jsonParams === false) {
            $jsonParams = print_r($data, true);
        }

        return $jsonParams;
    }
}