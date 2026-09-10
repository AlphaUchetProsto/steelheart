<?php

namespace app\models\bitrix;

use App\HTTP\HTTP;
use mysql_xdevapi\Exception;
use Yii;
use App\Bitrix24\Bitrix24APIException;
use app\models\logger\DebugLogger;

class BitrixNew
{
    protected $client;
    protected $rest_url;

    private $last_response;

    public function __construct($webhookUrl = '')
    {
        if (!empty($webhookUrl)) {
            $this->rest_url = $webhookUrl;
        } else {
            $this->rest_url = Yii::$app->params['bitrix']['webhook_url'];
        }

        $client = new HTTP();
        $client->throttle = 2;

        $this->client = $client;

        $action = Yii::$app->controller->action->id;
        $logger = DebugLogger::instance($action);
        $this->setLogger($logger);
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

    public function request($method, $params = [], $fullResponse = false)
    {
        $url = "{$this->rest_url}{$method}";

        // Логирование запроса
        if (isset($this->logger)) {
            $jsonParams = urldecode($this->toJSON($params, true));
            $this->logger->save("ЗАПРОС: {$method}" . PHP_EOL . $jsonParams, $this);
        }

        $this->last_response = $this->client->request($url, "POST", $params);

        // Логирование ответа
        if (isset($this->logger)) {
            $jsonResponse = $this->toJSON($this->last_response, true);
            $this->logger->save("ОТВЕТ: {$method}" . PHP_EOL . $jsonResponse, $this);
        }

        if(isset($this->last_response["error"]))
        {
            if (in_array($this->last_response['error_description'], ['Method is blocked due to operation time limit.', 'Too many requests'])) {
                sleep(2);
            } else {
                $jsonParams = $this->toJSON($params);
                $jsonResponse = $this->toJSON($this->last_response);

                throw new Bitrix24APIException("Ошибка при запросе '{$method}' ({$jsonParams}): {$jsonResponse}");
            }

//            dd($this->last_response);

            $this->last_response = $this->request($method, $params, true);
        }

        if ($fullResponse) {
            return $this->last_response;
        } else {
            return $this->last_response['result'];
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

    public function batchRequest($commands, $halt = true, $fullResponse = false)
    {
        $url = "{$this->rest_url}batch";

        // Логирование запроса
        if (isset($this->logger)) {
            $jsonParams = urldecode($this->toJSON($commands, true));
            $this->logger->save("ЗАПРОС: {$jsonParams}");
        }

        $this->last_response = $this->client->request($url, "POST", ["cmd" => $commands, "halt" => $halt]);

        // Логирование ответа
        if (isset($this->logger)) {
            $jsonResponse = $this->toJSON($this->last_response, true);
            $this->logger->save("ОТВЕТ: batch.json" . PHP_EOL . $jsonResponse, $this);
        }

        if(!empty($this->last_response['result']['result_error']))
        {
            if (in_array(array_values($this->last_response['result']['result_error'])[0]['error_description'], ['Method is blocked due to operation time limit.', 'Too many requests'])) {
                $timeNeedSleep = ($this->last_response['time']['operating_reset_at'] - strtotime('now')) / 1000;
                sleep($timeNeedSleep);

                $key = array_key_first($this->last_response['result']['result_error']);

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

                $this->last_response = $this->batchRequest($newCommands, $halt, true);
            } else {
                $jsonCommands = $this->toJSON($commands);
                $jsonResponse = $this->toJSON($this->last_response);

                throw new Bitrix24APIException("Ошибка при запросе batch ({$jsonCommands}): {$jsonResponse}");
            }
        }

        if(isset($this->last_response["error"]))
        {
            if (in_array($this->last_response['error_description'], ['Method is blocked due to operation time limit.', 'Too many requests'])) {
                sleep(2);
            } else {
                $jsonCommands = $this->toJSON($commands);
                $jsonResponse = $this->toJSON($this->last_response);

                throw new Bitrix24APIException("Ошибка при запросе batch ({$jsonCommands}): {$jsonResponse}");
            }

//            dd($this->last_response);

            $this->last_response = $this->batchRequest($commands, $halt, true);
        }

        if ($fullResponse) {
            return $this->last_response;
        } else {
            return $this->last_response['result']['result'];
        }
    }

    public function getLastResponse()
    {
        return $this->last_response;
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
