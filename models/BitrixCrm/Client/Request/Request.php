<?php

namespace app\models\BitrixCrm\Client\Request;

use App\HTTP\HTTP;
use app\models\BitrixCrm\Client\Response;
use app\models\logger\DebugLogger;
use yii\base\Exception;
use app\models\BitrixCrm\Client\Config;

abstract class Request
{
    protected const THROTTLE = 0.5;
    protected const CONNECT_TIMEOUT = 60;
    protected const REQUEST_TIMEOUT = 60;
    protected const MAX_COUNTER = 5;

    protected $httpClient;
    protected $config;
    protected $counter = 0;

    public function __construct(Config $config)
    {
        $this->httpClient = new HTTP();
        $this->httpClient->throttle = self::THROTTLE;
        $this->httpClient->curlConnectTimeout = self::CONNECT_TIMEOUT;
        $this->httpClient->curlTimeout = self::REQUEST_TIMEOUT;
        $this->config = $config;
    }

    protected function isAllowCounter() :bool
    {
        $this->counter++;

        return $this->counter < self::MAX_COUNTER;
    }

    protected function clearCounter() :self
    {
        $this->counter = 0;

        return $this;
    }

    public function buildCommand(string $method, array $params = []) :string
    {
        $command = "{$method}";

        if(!empty($params))
        {
            $command .= "?" . http_build_query($params);
        }

        return $command;
    }

    public function batchRequest(array $batch, $halt = false) :Response
    {
        return $this->request("batch", ["cmd" => $batch, "halt" => $halt]);
    }

    public function multipleBatchRequest(array $batch, $chunkSize = 50) :Response
    {
        $response = new Response();

        $batch = collect($batch)->chunk($chunkSize)->toArray();

        foreach ($batch as $row)
        {
            $response->merge($this->batchRequest($row));
        }

        return $response;
    }

    protected function sendError($message) :void
    {
        throw new Exception($message);
    }

    abstract public function request(string $method, array $params = []);
}